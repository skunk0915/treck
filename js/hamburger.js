document.addEventListener('DOMContentLoaded', function () {
    const hamburgerBtn = document.getElementById('hamburger-btn');
    const menuModal = document.getElementById('menu-modal');
    const closeBtn = document.getElementById('menu-close-btn');
    const overlay = document.querySelector('.menu-modal-overlay');
    const searchInput = document.getElementById('menu-search-input');
    const relatedListEl = document.getElementById('menu-related-list');
    const relatedSection = document.getElementById('menu-related-section');
    const allListEl = document.getElementById('menu-all-list');
    const tagsToggle = document.getElementById('menu-tags-toggle');
    const tagsListEl = document.getElementById('menu-tags-list');

    if (!hamburgerBtn || !menuModal) return;

    // Toggle Modal
    function openMenu() {
        menuModal.classList.add('visible');
        document.body.style.overflow = 'hidden';
        renderAllArticles(); // Initial render
        renderTags();
        // Check for related articles logic is mostly static, but data is dynamic
        renderRelated();
        searchInput.focus();
    }

    function closeMenu() {
        menuModal.classList.remove('visible');
        document.body.style.overflow = '';
    }

    hamburgerBtn.addEventListener('click', openMenu);
    closeBtn.addEventListener('click', closeMenu);
    overlay.addEventListener('click', closeMenu);

    // Render Function Helper
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

    // Render Related Logic
    function renderRelated() {
        relatedListEl.innerHTML = '';
        if (typeof relatedArticlesData !== 'undefined' && Object.keys(relatedArticlesData).length > 0) {
            let hasRelated = false;
            // Flatten related articles or show by tag? 
            // Requirement: "Show related article list"
            // Let's flatten to avoid dupes/complexity in small list
            const seen = new Set();

            for (const [tag, articles] of Object.entries(relatedArticlesData)) {
                articles.forEach(art => {
                    if (!seen.has(art.filename)) {
                        seen.add(art.filename);
                        relatedListEl.appendChild(createCard(art));
                        hasRelated = true;
                    }
                });
            }

            if (hasRelated) {
                relatedSection.style.display = 'block';
            } else {
                relatedSection.style.display = 'none';
            }
        } else {
            relatedSection.style.display = 'none';
        }
    }

    // Render All/Search Logic
    let isRandomized = false;
    let randomizedArticles = [];

    function renderAllArticles(filterText = '') {
        const sourceData = (typeof allArticlesData !== 'undefined') ? allArticlesData : [];
        if (sourceData.length === 0) {
            console.warn('No articles data found.');
            return;
        }

        const lowerFilter = filterText.toLowerCase().trim();

        if (!lowerFilter) {
            // If no filter, show randomized logic
            if (!isRandomized || randomizedArticles.length === 0) {
                randomizedArticles = [...sourceData].sort(() => 0.5 - Math.random());
                isRandomized = true;
            }
        }

        allListEl.innerHTML = '';

        const targetList = lowerFilter ? sourceData : randomizedArticles;
        let count = 0;

        targetList.forEach(article => {
            const titleMatch = article.title.toLowerCase().includes(lowerFilter);
            const tagsMatch = article.tags && article.tags.some(t => t.toLowerCase().includes(lowerFilter));

            if (!lowerFilter || titleMatch || tagsMatch) {
                allListEl.appendChild(createCard(article));
                count++;
            }
        });

        if (count === 0) {
            const emptyMsg = document.createElement('div');
            emptyMsg.textContent = '記事が見つかりませんでした。';
            emptyMsg.style.padding = '10px';
            emptyMsg.style.color = '#888';
            allListEl.appendChild(emptyMsg);
        }
    }

    searchInput.addEventListener('input', (e) => {
        renderAllArticles(e.target.value);
    });

    // Tags Logic
    let tagsRendered = false;
    function renderTags() {
        if (tagsRendered) return;
        const sourceData = (typeof allArticlesData !== 'undefined') ? allArticlesData : [];
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
