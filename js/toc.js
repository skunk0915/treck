/* TOC Interactions */
document.addEventListener('DOMContentLoaded', function () {
	const tocContainer = document.querySelector('.toc-container');
	if (!tocContainer) return;

	// ----------------------------------------------------
	// 1. Inline Toggle Logic (Original)
	// ----------------------------------------------------
	const toggleBtn = tocContainer.querySelector('.toc-toggle');
	const tocList = tocContainer.querySelector('ul');

	if (toggleBtn && tocList) {
		toggleBtn.addEventListener('click', function () {
			tocContainer.classList.toggle('closed');
			if (tocContainer.classList.contains('closed')) {
				toggleBtn.textContent = '[+]';
				tocList.style.display = 'none';
			} else {
				toggleBtn.textContent = '[-]';
				tocList.style.display = 'block';
			}
		});
	}

	// ----------------------------------------------------
	// 2. Mobile: FAB and Modal (Original)
	// ----------------------------------------------------
	const fab = document.createElement('button');
	fab.className = 'toc-fab';
	fab.textContent = '目次';
	fab.title = '目次を開く';
	document.body.appendChild(fab);

	const modal = document.createElement('div');
	modal.className = 'toc-modal';

	const modalOverlay = document.createElement('div');
	modalOverlay.className = 'toc-modal-overlay';

	const modalContent = document.createElement('div');
	modalContent.className = 'toc-modal-content';

	const modalHeader = document.createElement('div');
	modalHeader.className = 'toc-modal-header';

	const modalTitle = document.createElement('h3');
	modalTitle.textContent = '目次';

	const closeBtn = document.createElement('button');
	closeBtn.className = 'toc-modal-close';
	closeBtn.textContent = '×';

	modalHeader.appendChild(modalTitle);
	modalHeader.appendChild(closeBtn);

	const tocCloneForModal = tocList.cloneNode(true);
	tocCloneForModal.style.display = 'block';

	const modalLinks = tocCloneForModal.querySelectorAll('a');
	modalLinks.forEach(link => {
		link.addEventListener('click', function () {
			closeModal();
		});
	});

	modalContent.appendChild(modalHeader);
	modalContent.appendChild(tocCloneForModal);
	modal.appendChild(modalOverlay);
	modal.appendChild(modalContent);
	document.body.appendChild(modal);

	function openModal() {
		modal.classList.add('visible');
		document.body.style.overflow = 'hidden';
	}

	function closeModal() {
		modal.classList.remove('visible');
		document.body.style.overflow = '';
	}

	fab.addEventListener('click', openModal);
	closeBtn.addEventListener('click', closeModal);
	modalOverlay.addEventListener('click', closeModal);


	// ----------------------------------------------------
	// 3. Desktop: Sidebar TOC (New)
	// ----------------------------------------------------
	const sidebarInner = document.querySelector('.toc-sidebar-inner');
	if (sidebarInner && tocList) {
		// Create Sidebar Header
		const sidebarTitle = document.createElement('div');
		sidebarTitle.className = 'toc-title';
		sidebarTitle.textContent = '目次';
		sidebarInner.appendChild(sidebarTitle);

		// Clone TOC
		const tocCloneForSidebar = tocList.cloneNode(true);
		tocCloneForSidebar.style.display = 'block';
		sidebarInner.appendChild(tocCloneForSidebar);

		// Scroll Highlighting
		const sidebarLinks = tocCloneForSidebar.querySelectorAll('a');
		const sections = [];

		// Collect all target sections
		sidebarLinks.forEach(link => {
			const href = link.getAttribute('href');
			if (href && href.startsWith('#')) {
				const targetId = href.substring(1);
				const targetSection = document.getElementById(targetId);
				if (targetSection) {
					sections.push({
						id: targetId,
						link: link,
						element: targetSection
					});
				}
			}
		});

		// Highlight logic
		function highlightTOC() {
			// Trigger highlighting when section is near top (e.g., 100px from top or 20% of viewport)
			const threshold = 150;

			// Default: clear all
			sidebarLinks.forEach(link => link.classList.remove('active'));

			let currentSection = null;

			for (const section of sections) {
				const rect = section.element.getBoundingClientRect();

				// If the section's top is above the threshold (meaning we scrolled past it or are viewing it)
				// AND it is still somewhat visible or the last one passed
				// A simple approach: find the last section whose top is <= threshold
				if (rect.top <= threshold) {
					currentSection = section;
				}
			}

			if (currentSection) {
				currentSection.link.classList.add('active');
			}
		}

		// Throttled scroll listener
		let isScrolling = false;
		window.addEventListener('scroll', function () {
			if (!isScrolling) {
				window.requestAnimationFrame(function () {
					highlightTOC();
					isScrolling = false;
				});
				isScrolling = true;
			}
		});

		// Initial check
		highlightTOC();
	}
});
