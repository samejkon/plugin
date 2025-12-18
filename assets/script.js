document.addEventListener("DOMContentLoaded", function () {
    const modal = document.querySelector(".stc-record-modal");
    const openBtn = document.querySelector(".js-open-record-modal");
    const closeBtns = document.querySelectorAll(".js-close-record-modal");

    if (modal && openBtn) {
        const openModal = () => {
            modal.classList.add("is-open");
            modal.setAttribute("aria-hidden", "false");
        };

        const closeModal = () => {
            modal.classList.remove("is-open");
            modal.setAttribute("aria-hidden", "true");
        };

        openBtn.addEventListener("click", function (e) {
            e.preventDefault();
            openModal();
        });

        closeBtns.forEach(function (btn) {
            btn.addEventListener("click", function () {
                closeModal();
            });
        });

        document.addEventListener("keyup", function (e) {
            if (e.key === "Escape" && modal.classList.contains("is-open")) {
                closeModal();
            }
        });
    }

    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(function(input) {
        input.addEventListener('click', function(e) {
            const self = this;
            
            if (typeof self.showPicker === 'function') {
                setTimeout(function() {
                    try {
                        self.showPicker();
                    } catch (error) {
                        console.log('showPicker not available:', error);
                    }
                }, 10);
            }
        });
        
        input.addEventListener('focus', function(e) {
            const self = this;
            if (typeof self.showPicker === 'function') {
                setTimeout(function() {
                    try {
                        self.showPicker();
                    } catch (error) {
                        console.log('showPicker not available:', error);
                    }
                }, 10);
            }
        });
    });

    const uploadInputs = document.querySelectorAll('.stc-upload__input[data-upload-input]');
    
    uploadInputs.forEach(function(input) {
        const uploadName = input.getAttribute('data-upload-input');
        const previewContainer = document.querySelector('[data-preview="' + uploadName + '"]');
        const uploadLabel = document.querySelector('[data-upload-target="' + uploadName + '"]');
        const removeBtn = document.querySelector('[data-remove="' + uploadName + '"]');
        
        if (!previewContainer || !uploadLabel || !removeBtn) return;
        
        const previewImage = previewContainer.querySelector('.stc-upload-preview__image');
        
        input.addEventListener('change', function(e) {
            const file = e.target.files[0];
            
            if (file && file.type.startsWith('image/')) {
                const reader = new FileReader();
                
                reader.onload = function(event) {
                    previewImage.src = event.target.result;
                    previewContainer.style.display = 'block';
                    
                    const removeInput = document.getElementById('remove-' + uploadName.replace('_', '-'));
                    if (removeInput) {
                        removeInput.value = '';
                    }
                };
                
                reader.readAsDataURL(file);
            }
        });
        
        removeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            input.value = '';
            
            previewContainer.style.display = 'none';
            previewImage.src = '';
            
            const removeInput = document.getElementById('remove-' + uploadName.replace('_', '-'));
            if (removeInput) {
                removeInput.value = '1';
            }
        });
    });

    const viewMoreBtn = document.getElementById('stc-view-more-btn');
    
    if (viewMoreBtn) {
        const itemsPerLoad = 10;

        viewMoreBtn.addEventListener('click', function() {
            const hiddenItems = document.querySelectorAll('.stc-history-item-hidden');

            if (hiddenItems.length > 0) {
                // Show next 10 items (existing fallback behavior)
                let showCount = 0;
                hiddenItems.forEach(function(item) {
                    if (showCount < itemsPerLoad) {
                        item.classList.remove('stc-history-item-hidden');
                        showCount++;
                    }
                });

                // Check if there are more hidden items
                const remainingHidden = document.querySelectorAll('.stc-history-item-hidden');
                if (remainingHidden.length === 0) {
                    const container = viewMoreBtn.closest('.stc-view-more-container');
                    if (container) {
                        container.style.display = 'none';
                    } else {
                        viewMoreBtn.style.display = 'none';
                    }
                }

                return;
            }

            // No hidden items present => request next page via AJAX
            const ajaxUrl = viewMoreBtn.getAttribute('data-ajax-url');
            const userId = viewMoreBtn.getAttribute('data-user-id');
            const totalCount = parseInt(viewMoreBtn.getAttribute('data-total') || '0', 10);
            let currentPage = parseInt(viewMoreBtn.getAttribute('data-current-page') || '1', 10);
            const nextPage = currentPage + 1;

            if (!ajaxUrl || !userId) {
                console.warn('stc: missing ajax url or user id');
                return;
            }

            // Prevent double-clicks
            if (viewMoreBtn.getAttribute('data-loading') === '1') {
                return;
            }
            viewMoreBtn.setAttribute('data-loading', '1');
            viewMoreBtn.disabled = true;

            const form = new FormData();
            form.append('action', 'stc_load_more_deliveries');
            form.append('page', nextPage);
            form.append('user_id', userId);

            fetch(ajaxUrl, {
                method: 'POST',
                body: form,
                credentials: 'same-origin'
            })
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('Network response was not ok: ' + response.status);
                }
                return response.text();
            })
            .then(function(html) {
                if (!html || html.trim() === '') {
                    const container = viewMoreBtn.closest('.stc-view-more-container');
                    if (container) container.style.display = 'none';
                    viewMoreBtn.style.display = 'none';
                    viewMoreBtn.removeAttribute('data-loading');
                    viewMoreBtn.disabled = false;
                    return;
                }

                const container = document.querySelector('.stc-history-body');
                if (container) {
                    container.insertAdjacentHTML('beforeend', html);
                }

                // Update page counter
                currentPage = nextPage;
                viewMoreBtn.setAttribute('data-current-page', String(currentPage));

                // Re-attach image click events in case any images were added
                attachImageClickEvents();

                // Hide button if we've loaded all items
                if (currentPage * itemsPerLoad >= totalCount) {
                    const parent = viewMoreBtn.closest('.stc-view-more-container');
                    if (parent) parent.style.display = 'none';
                    viewMoreBtn.style.display = 'none';
                }

                viewMoreBtn.removeAttribute('data-loading');
                viewMoreBtn.disabled = false;
            })
            .catch(function(err) {
                console.error('Error loading more items:', err);
                viewMoreBtn.removeAttribute('data-loading');
                viewMoreBtn.disabled = false;
            });
        });
    }

    const salesRankingViewMore = document.getElementById('sales-ranking-view-more');
    
    function handleRankingsViewMore(btn) {
        if (!btn) return;

        const perLoad = 5;

        btn.addEventListener('click', function() {
            const targetSelector = btn.getAttribute('data-target');
            const ajaxUrl = btn.getAttribute('data-ajax-url');
            const sort = btn.getAttribute('data-sort');
            const total = parseInt(btn.getAttribute('data-total') || '0', 10);
            let currentPage = parseInt(btn.getAttribute('data-current-page') || '1', 10);
            const nextPage = currentPage + 1;

            if (!ajaxUrl || !targetSelector) {
                console.warn('stc: missing ajax url or target for rankings');
                return;
            }

            // Prevent double clicks
            if (btn.getAttribute('data-loading') === '1') return;
            btn.setAttribute('data-loading', '1');
            btn.disabled = true;

            const form = new FormData();
            form.append('action', 'stc_load_more_rankings');
            form.append('page', nextPage);
            form.append('sort', sort);

            fetch(ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin' })
                .then(function(response) {
                    if (!response.ok) throw new Error('Network response not ok');
                    return response.text();
                })
                .then(function(html) {
                    if (!html || html.trim() === '') {
                        const parent = btn.closest('.rankings-view-more-container');
                        if (parent) parent.style.display = 'none';
                        btn.style.display = 'none';
                        return;
                    }

                    const target = document.querySelector(targetSelector);
                    if (target) target.insertAdjacentHTML('beforeend', html);

                    currentPage = nextPage;
                    btn.setAttribute('data-current-page', String(currentPage));

                    if (currentPage * perLoad >= total) {
                        const parent = btn.closest('.rankings-view-more-container');
                        if (parent) parent.style.display = 'none';
                        btn.style.display = 'none';
                    }

                    btn.removeAttribute('data-loading');
                    btn.disabled = false;
                })
                .catch(function(err) {
                    console.error('Error loading rankings:', err);
                    btn.removeAttribute('data-loading');
                    btn.disabled = false;
                });
        });
    }

    handleRankingsViewMore(salesRankingViewMore);

    const monthlyHoursViewMore = document.getElementById('monthly-hours-ranking-view-more');
    
    handleRankingsViewMore(monthlyHoursViewMore);

    const totalHoursViewMore = document.getElementById('total-hours-ranking-view-more');
    
    handleRankingsViewMore(totalHoursViewMore);

    // Image Preview Modal
    let imageModal = document.getElementById('stc-image-modal');
    
    // Create modal if it doesn't exist
    if (!imageModal) {
        const modalHTML = `
            <div id="stc-image-modal" class="stc-image-modal">
                <div class="stc-image-modal__backdrop"></div>
                <div class="stc-image-modal__content">
                    <button id="stc-image-modal-close" class="stc-image-modal__close" aria-label="Close">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                    <img id="stc-image-modal-img" class="stc-image-modal__image" src="" alt="Preview">
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        imageModal = document.getElementById('stc-image-modal');
    }
    
    const modalImg = document.getElementById('stc-image-modal-img');
    const modalClose = document.getElementById('stc-image-modal-close');
    const modalBackdrop = imageModal.querySelector('.stc-image-modal__backdrop');
    
    // Function to open modal
    function openImageModal(imageSrc) {
        if (modalImg && imageModal) {
            modalImg.src = imageSrc;
            imageModal.classList.add('stc-image-modal--active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    // Function to close modal
    function closeImageModal() {
        if (imageModal) {
            imageModal.classList.remove('stc-image-modal--active');
            document.body.style.overflow = '';
            setTimeout(function() {
                if (modalImg) {
                    modalImg.src = '';
                }
            }, 300);
        }
    }
    
    // Add click event to all images (including dynamically loaded ones)
    function attachImageClickEvents() {
        const allImages = document.querySelectorAll('.stc-confirm-image, .stc-upload-preview__image');
        
        allImages.forEach(function(img) {
            if (!img.hasAttribute('data-preview-attached')) {
                img.style.cursor = 'pointer';
                img.setAttribute('data-preview-attached', 'true');
                img.addEventListener('click', function(e) {
                    // Don't open modal if clicking on remove button
                    const removeBtn = this.closest('.stc-upload-preview')?.querySelector('.stc-upload-preview__remove');
                    if (removeBtn && removeBtn.contains(e.target)) {
                        return;
                    }
                    
                    e.stopPropagation();
                    const imageSrc = this.src;
                    if (imageSrc && imageSrc.trim() !== '') {
                        openImageModal(imageSrc);
                    }
                });
            }
        });
    }
    
    // Initial attach
    attachImageClickEvents();
    
    // Re-attach when new images are loaded (for upload preview)
    const observer = new MutationObserver(function(mutations) {
        attachImageClickEvents();
    });
    
    observer.observe(document.body, {
        childList: true,
        subtree: true
    });
    
    // Close modal events
    if (modalClose) {
        modalClose.addEventListener('click', function(e) {
            e.stopPropagation();
            closeImageModal();
        });
    }
    
    if (modalBackdrop) {
        modalBackdrop.addEventListener('click', closeImageModal);
    }
    
    // Close on Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && imageModal && imageModal.classList.contains('stc-image-modal--active')) {
            closeImageModal();
        }
    });

});
