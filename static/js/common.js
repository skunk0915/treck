document.addEventListener('DOMContentLoaded', function () {
	// Back to Top Button Logic
	const backToTopBtn = document.getElementById('backToTop');

	if (backToTopBtn) {
		// Toggle visibility based on scroll position
		window.addEventListener('scroll', function () {
			if (window.scrollY > 300) {
				backToTopBtn.classList.add('visible');
			} else {
				backToTopBtn.classList.remove('visible');
			}
		});

		// Smooth scroll to top
		backToTopBtn.addEventListener('click', function (e) {
			e.preventDefault();
			window.scrollTo({
				top: 0,
				behavior: 'smooth'
			});
		});
	}
});
