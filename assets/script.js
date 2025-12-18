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
            
            if (hiddenItems.length === 0) {
                return;
            }
            
            // Show next 10 items
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
                // Hide button and container
                const container = viewMoreBtn.closest('.stc-view-more-container');
                if (container) {
                    container.style.display = 'none';
                } else {
                    viewMoreBtn.style.display = 'none';
                }
            }
        });
    }

    const salesRankingViewMore = document.getElementById('sales-ranking-view-more');
    
    if (salesRankingViewMore) {
        salesRankingViewMore.addEventListener('click', function() {
            const hiddenItems = document.querySelectorAll('.rankings_item-hidden');
            
            let showCount = 0;
            hiddenItems.forEach(function(item) {
                if (showCount < 5) {
                    item.classList.remove('rankings_item-hidden');
                    showCount++;
                }
            });
            
            const remainingHidden = document.querySelectorAll('.rankings_item-hidden');
            if (remainingHidden.length === 0) {
                salesRankingViewMore.style.display = 'none';
            }
        });
    }

    const monthlyHoursViewMore = document.getElementById('monthly-hours-ranking-view-more');
    
    if (monthlyHoursViewMore) {
        monthlyHoursViewMore.addEventListener('click', function() {
            const hiddenItems = document.querySelectorAll('.rankings_item-hidden-monthly');
            
            let showCount = 0;
            hiddenItems.forEach(function(item) {
                if (showCount < 5) {
                    item.classList.remove('rankings_item-hidden-monthly');
                    showCount++;
                }
            });
            
            const remainingHidden = document.querySelectorAll('.rankings_item-hidden-monthly');
            if (remainingHidden.length === 0) {
                monthlyHoursViewMore.style.display = 'none';
            }
        });
    }

    const totalHoursViewMore = document.getElementById('total-hours-ranking-view-more');
    
    if (totalHoursViewMore) {
        totalHoursViewMore.addEventListener('click', function() {
            const hiddenItems = document.querySelectorAll('.rankings_item-hidden-total');
            
            let showCount = 0;
            hiddenItems.forEach(function(item) {
                if (showCount < 5) {
                    item.classList.remove('rankings_item-hidden-total');
                    showCount++;
                }
            });
            
            const remainingHidden = document.querySelectorAll('.rankings_item-hidden-total');
            if (remainingHidden.length === 0) {
                totalHoursViewMore.style.display = 'none';
            }
        });
    }

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
