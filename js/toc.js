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

	// 4. Related Articles FAB
	if (typeof relatedArticlesData !== 'undefined' && Object.keys(relatedArticlesData).length > 0) {
		// Create FAB
		const relatedFab = document.createElement('button');
		relatedFab.className = 'related-fab';
		relatedFab.innerHTML = '関連<br>記事';
		relatedFab.title = '関連記事を開く';
		document.body.appendChild(relatedFab);

		// Create Modal
		const relatedModal = document.createElement('div');
		relatedModal.className = 'toc-modal related-mode';

		const rOverlay = document.createElement('div');
		rOverlay.className = 'toc-modal-overlay';

		const rContent = document.createElement('div');
		rContent.className = 'toc-modal-content';

		// Header
		const rHeader = document.createElement('div');
		rHeader.className = 'toc-modal-header';

		const rTitle = document.createElement('h3');
		rTitle.textContent = '関連記事';

		const rClose = document.createElement('button');
		rClose.className = 'toc-modal-close';
		rClose.textContent = '×';

		rHeader.appendChild(rTitle);
		rHeader.appendChild(rClose);
		rContent.appendChild(rHeader);

		// Content
		const rBody = document.createElement('div');

		for (const [tag, articles] of Object.entries(relatedArticlesData)) {
			if (!articles || articles.length === 0) continue;

			const section = document.createElement('div');
			section.className = 'related-tag-section';

			const h3 = document.createElement('h3');
			h3.textContent = tag + 'に関連する記事';
			section.appendChild(h3);

			const list = document.createElement('div');
			list.className = 'related-list';

			articles.forEach(article => {
				const card = document.createElement('div');
				card.className = 'related-card';

				const link = document.createElement('a');
				const linkHref = (typeof siteBaseUrl !== 'undefined' ? siteBaseUrl : '') + '/' + article.filename;
				link.href = linkHref;
				link.className = 'related-card-link';

				const imgDiv = document.createElement('div');
				imgDiv.className = 'related-card-image';

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
					noImg.className = 'no-image';
					noImg.textContent = 'No Image';
					imgDiv.appendChild(noImg);
				}

				const contentDiv = document.createElement('div');
				contentDiv.className = 'related-card-content';
				const titleEl = document.createElement('h4');
				titleEl.className = 'related-card-title';
				titleEl.textContent = article.title;
				contentDiv.appendChild(titleEl);

				link.appendChild(imgDiv);
				link.appendChild(contentDiv);
				card.appendChild(link);
				list.appendChild(card);
			});

			section.appendChild(list);
			rBody.appendChild(section);
		}
		rContent.appendChild(rBody);

		relatedModal.appendChild(rOverlay);
		relatedModal.appendChild(rContent);
		document.body.appendChild(relatedModal);

		function openRelated() {
			relatedModal.classList.add('visible');
			document.body.style.overflow = 'hidden';
		}
		function closeRelated() {
			relatedModal.classList.remove('visible');
			document.body.style.overflow = '';
		}

		relatedFab.addEventListener('click', openRelated);
		rClose.addEventListener('click', closeRelated);
		rOverlay.addEventListener('click', closeRelated);
	}

});
