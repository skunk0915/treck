document.addEventListener('DOMContentLoaded', function () {
	console.log('home.js loaded');
	const searchInput = document.getElementById('searchInput');
	const articleGrid = document.getElementById('articleGrid');

	// Safety check for articleGrid
	if (!articleGrid) {
		// PWA logic moved to pwa.js, nothing else to do here if grid is missing
		return;
	}

	let articles = articleGrid.querySelectorAll('.article-card');
	const noResults = document.getElementById('noResults');
	const tagButtons = document.querySelectorAll('.tag-btn');
	let currentTag = 'all';

	// Sort articles
	function sortArticles(order) {
		const articlesArray = Array.from(articles);
		let sorted;

		if (order === 'random') {
			sorted = articlesArray.sort(() => 0.5 - Math.random());
		} else if (order === 'newest') {
			sorted = articlesArray.sort((a, b) => {
				const dateA = new Date(a.getAttribute('data-date') || 0);
				const dateB = new Date(b.getAttribute('data-date') || 0);
				return dateB - dateA;
			});
		}

		// Clear and re-append in sorted order
		articleGrid.innerHTML = '';
		sorted.forEach(article => {
			articleGrid.appendChild(article);
		});

		// Update articles NodeList after sort/shuffle to keep track of valid DOM elements
		// although the elements themselves are the same, order logic relies on array.
		// Re-querying is not strictly necessary if we rely on articlesArray for filtering, 
		// but existing filter logic uses `articles` NodeList (or we can update it).
		// Let's keep `articles` consistent with the DOM order or just use the filtered list.
		// Actually, standard practice: filter uses `articles` (all cards). Order doesn't change `articles` content, just DOM position.
		// So `articles` variable (NodeList) might be stale in terms of order, but it still contains all elements.
		// Filter logic iterates `articles` and toggles display. That works regardless of valid DOM order.

		// However, if we want filter results to appear sorted, we need to ensure filter logic respects the new DOM order?
		// Actually, CSS order is what matters. Since we appended them in order, it's fine.
	}

	// Sort Tabs
	const sortButtons = document.querySelectorAll('.sort-btn');
	sortButtons.forEach(btn => {
		btn.addEventListener('click', function () {
			sortButtons.forEach(b => b.classList.remove('active'));
			this.classList.add('active');
			const sortType = this.getAttribute('data-sort');
			sortArticles(sortType);

			// Re-apply filter if needed (to maintain search/tag state)
			filterArticles();
		});
	});

	// Initial Sort (Random by default)
	sortArticles('random');

	// Check for initially active tag
	const activeBtn = document.querySelector('.tag-btn.active');
	if (activeBtn) {
		currentTag = activeBtn.getAttribute('data-tag');
	}

	// Initial filter
	filterArticles();

	function filterArticles() {
		const query = searchInput ? searchInput.value.toLowerCase() : '';
		let visibleCount = 0;

		articles.forEach(article => {
			const title = article.getAttribute('data-title').toLowerCase();
			const tags = JSON.parse(article.getAttribute('data-tags'));
			const matchesSearch = title.includes(query);
			const matchesTag = currentTag === 'all' || tags.includes(currentTag);

			if (matchesSearch && matchesTag) {
				article.style.display = '';
				visibleCount++;
			} else {
				article.style.display = 'none';
			}
		});

		if (noResults) {
			if (visibleCount === 0) {
				noResults.style.display = 'block';
			} else {
				noResults.style.display = 'none';
			}
		}
	}

	if (searchInput) {
		searchInput.addEventListener('input', filterArticles);
	}

	tagButtons.forEach(btn => {
		btn.addEventListener('click', function () {
			tagButtons.forEach(b => b.classList.remove('active'));
			this.classList.add('active');
			currentTag = this.getAttribute('data-tag');
			filterArticles();
		});
	});

	// Tag Accordion
	const showMoreBtn = document.getElementById('showMoreTags');
	const tagAccordion = document.getElementById('tagFilter');

	if (showMoreBtn && tagAccordion) {
		showMoreBtn.addEventListener('click', function () {
			if (tagAccordion.classList.contains('open')) {
				// Closing: Set explicitly to scrollHeight first to enable transition
				tagAccordion.style.maxHeight = tagAccordion.scrollHeight + 'px';
				// Force reflow
				tagAccordion.offsetHeight;

				requestAnimationFrame(() => {
					tagAccordion.classList.remove('open');
					tagAccordion.style.maxHeight = null;
					showMoreBtn.textContent = 'もっと見る';
				});
			} else {
				// Opening
				tagAccordion.classList.add('open');
				tagAccordion.style.maxHeight = tagAccordion.scrollHeight + 'px';
				showMoreBtn.textContent = '閉じる';
			}
		});
	}
});
