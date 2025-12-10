/* TOC Interactions */
document.addEventListener('DOMContentLoaded', function () {
	const tocContainer = document.querySelector('.toc-container');
	if (!tocContainer) return;

	// 1. Inline Toggle Logic
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

	// 2. Creating FAB and Modal
	// Create FAB
	const fab = document.createElement('button');
	fab.className = 'toc-fab';
	fab.textContent = '目次';
	fab.title = '目次を開く';
	document.body.appendChild(fab);

	// Create Modal
	const modal = document.createElement('div');
	modal.className = 'toc-modal';

	const modalOverlay = document.createElement('div');
	modalOverlay.className = 'toc-modal-overlay';

	const modalContent = document.createElement('div');
	modalContent.className = 'toc-modal-content';

	// Header for Modal
	const modalHeader = document.createElement('div');
	modalHeader.className = 'toc-modal-header';

	const modalTitle = document.createElement('h3');
	modalTitle.textContent = '目次';

	const closeBtn = document.createElement('button');
	closeBtn.className = 'toc-modal-close';
	closeBtn.textContent = '×';

	modalHeader.appendChild(modalTitle);
	modalHeader.appendChild(closeBtn);

	// Clone TOC list
	const tocClone = tocList.cloneNode(true);
	tocClone.style.display = 'block'; // Ensure it's visible in modal even if inline is closed

	// Add click event to anchors in modal to close modal on click
	const modalLinks = tocClone.querySelectorAll('a');
	modalLinks.forEach(link => {
		link.addEventListener('click', function () {
			closeModal();
		});
	});

	modalContent.appendChild(modalHeader);
	modalContent.appendChild(tocClone);
	modal.appendChild(modalOverlay);
	modal.appendChild(modalContent);
	document.body.appendChild(modal);

	// 3. Modal Interactions
	function openModal() {
		modal.classList.add('visible');
		document.body.style.overflow = 'hidden'; // Prevent background scrolling
	}

	function closeModal() {
		modal.classList.remove('visible');
		document.body.style.overflow = '';
	}

	fab.addEventListener('click', openModal);
	closeBtn.addEventListener('click', closeModal);
	modalOverlay.addEventListener('click', closeModal);

	// 4. Related Articles FAB - (Removed, moved to Hamburger Menu)
});
