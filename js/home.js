document.addEventListener('DOMContentLoaded', function () {
	const searchInput = document.getElementById('searchInput');
	const tagButtons = document.querySelectorAll('.tag-btn');
	const articleGrid = document.getElementById('articleGrid');
	const articles = document.querySelectorAll('.article-card');
	const noResults = document.getElementById('noResults');

	let currentTag = 'all';
	let currentSearch = '';

	function filterArticles() {
		let visibleCount = 0;

		articles.forEach(article => {
			const title = article.dataset.title.toLowerCase();
			const tags = JSON.parse(article.dataset.tags);

			const matchesSearch = title.includes(currentSearch);
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

	// Search Input Event
	searchInput.addEventListener('input', function (e) {
		currentSearch = e.target.value.toLowerCase();
		filterArticles();
	});

	// Tag Button Click Event
	tagButtons.forEach(btn => {
		btn.addEventListener('click', function () {
			// Update Active State
			tagButtons.forEach(b => b.classList.remove('active'));
			this.classList.add('active');

			// Update Filter
			currentTag = this.dataset.tag;
			filterArticles();
		});
	});
});
