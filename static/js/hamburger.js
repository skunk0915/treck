document.addEventListener('DOMContentLoaded', function () {
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const menuModal = document.getElementById('menu-modal');
    const closeBtn = document.getElementById('menu-close-btn');
    const overlay = document.querySelector('.menu-modal-overlay');
    const searchInput = document.getElementById('menu-search-input');
    const searchResultsEl = document.getElementById('menu-search-results');

    // Sections
    const relatedListEl = document.getElementById('menu-related-list'); // This might need to be cleared or used as container
    const relatedSection = document.getElementById('menu-related-section');
    const allSection = document.getElementById('menu-all-section');
    const allListEl = document.getElementById('menu-all-list');
    const tagsToggle = document.getElementById('menu-tags-toggle');
    const tagsListEl = document.getElementById('menu-tags-list');

    if (!hamburgerBtn || !menuModal) return;

    // Toggle Modal
    function openMenu() {
        menuModal.classList.add('visible');
        document.body.style.overflow = 'hidden';

        // Check if data is loaded, if not fetch it
        if (typeof allArticlesData === 'undefined' || allArticlesData.length === 0) {
            fetch((typeof siteBaseUrl !== 'undefined' ? siteBaseUrl : '') + '/js/articles.json?v=' + new Date().getTime())
                .then(response => response.json())
                .then(data => {
                    allArticlesData = data;
                    renderAllRandom();
                    renderTags();
                })
                .catch(err => console.error('Error loading articles:', err));
        } else {
            // Already loaded
            if (allListEl.children.length === 0) renderAllRandom();
            renderTags();
        }

        renderRelated(); // Renders grouped by tag (relies on relatedArticlesData injected in page)
        searchInput.focus();
    }

    function closeMenu() {
        menuModal.classList.remove('visible');
        document.body.style.overflow = '';
    }

    hamburgerBtn.addEventListener('click', openMenu);
    closeBtn.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);

    // Helper: Create Article Card
    function createCard(article) {
        const div = document.createElement('div');
        div.className = 'menu-article-card';

        const link = document.createElement('a');
        link.href = (typeof siteBaseUrl !== 'undefined' ? siteBaseUrl : '') + '/' + article.filename;

        // Image
        const imgDiv = document.createElement('div');
        imgDiv.className = 'menu-article-image';
        if (article.thumbnail) {
            let imgSrc = article.thumbnail;
            if (!imgSrc.startsWith('http')) {
                imgSrc = (typeof siteBaseUrl !== 'undefined' ? siteBaseUrl : '') + imgSrc;
            }
            const img = document.createElement('img');
            img.src = imgSrc;
            img.alt = article.title;
            img.loading = 'lazy';
            imgDiv.appendChild(img);
        } else {
            const noImg = document.createElement('div');
            noImg.className = 'menu-no-image';
            noImg.textContent = 'No Image';
            imgDiv.appendChild(noImg);
        }

        // Content
        const contentDiv = document.createElement('div');
        contentDiv.className = 'menu-article-content';
        const title = document.createElement('h4');
        title.textContent = article.title;
        contentDiv.appendChild(title);

        // Tags
        if (article.tags && article.tags.length > 0) {
            const tagSpan = document.createElement('span');
            tagSpan.className = 'menu-article-tags';
            tagSpan.textContent = '#' + article.tags.join(' #');
            contentDiv.appendChild(tagSpan);
        }

        link.appendChild(imgDiv);
        link.appendChild(contentDiv);
        div.appendChild(link);
        return div;
    }

    // 1. Render Related Articles (Grouped by Tag)
    function renderRelated() {
        relatedListEl.innerHTML = ''; // Clear container
        if (typeof relatedArticlesData !== 'undefined' && Object.keys(relatedArticlesData).length > 0) {
            let hasRelated = false;

            // Iterate object keys (tags)
            for (const [tag, articles] of Object.entries(relatedArticlesData)) {
                if (articles && articles.length > 0) {
                    hasRelated = true;

                    // Create Sub-section for Tag
                    const tagContainer = document.createElement('div');
                    tagContainer.className = 'menu-related-group';

                    const tagHeader = document.createElement('h4');
                    tagHeader.textContent = tag;
                    tagHeader.style.fontSize = '0.95rem';
                    tagHeader.style.marginBottom = '8px';
                    tagHeader.style.color = '#555';
                    tagContainer.appendChild(tagHeader);

                    // List container (horizontal scroll)
                    const listRow = document.createElement('div');
                    listRow.className = 'menu-list'; // Re-use horizontal scroll style

                    articles.forEach(art => {
                        listRow.appendChild(createCard(art));
                    });

                    tagContainer.appendChild(listRow);
                    relatedListEl.appendChild(tagContainer);
                }
            }

            relatedSection.style.display = hasRelated ? 'block' : 'none';
        } else {
            relatedSection.style.display = 'none';
        }
    }

    // 2. Render All Articles (Randomized) - for the "All Articles" section
    function renderAllRandom() {
        const sourceData = (typeof allArticlesData !== 'undefined') ? allArticlesData : [];
        if (sourceData.length === 0) return;

        allListEl.innerHTML = '';
        // Shuffle
        const randomized = [...sourceData].sort(() => 0.5 - Math.random());

        randomized.forEach(article => {
            allListEl.appendChild(createCard(article));
        });
    }

    // 3. Search Logic
    function handleSearch(filterText) {
        const lowerFilter = filterText.toLowerCase().trim();

        if (!lowerFilter) {
            // Empty Search: Hide ID search results, Show default sections
            searchResultsEl.style.display = 'none';
            searchResultsEl.innerHTML = '';

            // Show others
            if (relatedSection.children.length > 0 && relatedListEl.innerHTML !== '') {
                // Re-check display based on content (simple toggle depends on valid data)
                // We rely on renderRelated() setting display block/none. 
                // Here we just unhide the section element itself if it has content
                if (relatedListEl.children.length > 0) relatedSection.style.display = 'block';
            }
            allSection.style.display = 'block';
            return;
        }

        // Active Search: Show Results ID, Hide others? 
        // Usually search takes over, or pushes down?
        // Let's keep it simple: Show search results at top. Hide other sections to avoid clutter?
        // "menu-search-inputのすぐ下に専用記事リストで表示して" -> Display dedicated list.
        // If I hide others, it focuses user. Let's hide others for clarity.

        // Active Search: Show Results ID
        // User Request: Keep related and all sections visible.

        searchResultsEl.style.display = 'flex'; // It is .menu-list (flex)
        searchResultsEl.innerHTML = '';

        const sourceData = (typeof allArticlesData !== 'undefined') ? allArticlesData : [];
        let count = 0;

        sourceData.forEach(article => {
            const titleMatch = article.title.toLowerCase().includes(lowerFilter);
            const tagsMatch = article.tags && article.tags.some(t => t.toLowerCase().includes(lowerFilter));

            if (titleMatch || tagsMatch) {
                searchResultsEl.appendChild(createCard(article));
                count++;
            }
        });

        if (count === 0) {
            const emptyMsg = document.createElement('div');
            emptyMsg.textContent = '記事が見つかりませんでした。';
            emptyMsg.style.padding = '10px';
            emptyMsg.style.color = '#888';
            searchResultsEl.appendChild(emptyMsg); // Flex container handles div okay? 
            // .menu-list is display:flex; flex-direction:row; 
            // Text div might look weird. Let's wrap or styling?
            // Actually .menu-list expects cards. 
            // If message, maybe override style?
            searchResultsEl.style.display = 'block'; // Block for message
        } else {
            searchResultsEl.style.display = 'flex'; // Back to flex for cards
        }
    }

    searchInput.addEventListener('input', (e) => {
        handleSearch(e.target.value);
    });

    // Tags Logic
    let tagsRendered = false;
    function renderTags() {
        if (tagsRendered) return;
        const sourceData = (typeof allArticlesData !== 'undefined') ? allArticlesData : [];
        if (sourceData.length === 0) return; // Wait for data

        const allTags = new Set();
        sourceData.forEach(a => {
            if (a.tags) a.tags.forEach(t => allTags.add(t));
        });

        const sortedTags = Array.from(allTags).sort();
        tagsListEl.innerHTML = '';
        sortedTags.forEach(tag => {
            const btn = document.createElement('a');
            btn.href = (typeof siteBaseUrl !== 'undefined' ? siteBaseUrl : '') + '/tag/' + encodeURIComponent(tag);
            btn.className = 'menu-tag-link';
            btn.textContent = tag;
            tagsListEl.appendChild(btn);
        });
        tagsRendered = true;
    }

    tagsToggle.addEventListener('click', () => {
        const isVisible = tagsListEl.style.display === 'flex';
        tagsListEl.style.display = isVisible ? 'none' : 'flex';
        tagsToggle.querySelector('span').textContent = isVisible ? '+' : '-';
    });

});
