document.addEventListener('DOMContentLoaded', function () {
	console.log('home.js loaded');
	const searchInput = document.getElementById('searchInput');
	const articleGrid = document.getElementById('articleGrid');
	const articles = articleGrid.querySelectorAll('.article-card');
	const noResults = document.getElementById('noResults');
	const tagButtons = document.querySelectorAll('.tag-btn');
	let currentTag = 'all';

	function filterArticles() {
		const query = searchInput.value.toLowerCase();
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

		if (visibleCount === 0) {
			noResults.style.display = 'block';
		} else {
			noResults.style.display = 'none';
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
			tagAccordion.classList.toggle('open');
			if (tagAccordion.classList.contains('open')) {
				showMoreBtn.textContent = '閉じる';
			} else {
				showMoreBtn.textContent = 'もっと見る';
			}
		});
	}
});
