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

    // AJAX View More Handler for my-page and profile
    function handleDeliveryViewMore(button) {
        // Check if stcAjax is available
        if (typeof stcAjax === 'undefined' || typeof jQuery === 'undefined') {
            console.error('stcAjax or jQuery is not defined');
            return;
        }
        
        const currentPage = parseInt(button.getAttribute('data-page')) || 1;
        const perPage = parseInt(button.getAttribute('data-per-page')) || 10;
        const userId = button.getAttribute('data-user-id');
        const type = button.getAttribute('data-type') || 'mypage';
        const container = button.closest('.stc-view-more-container');
        const historyBody = document.querySelector('.stc-history-body');
        
        if (!historyBody || !userId) {
            console.error('History body or user ID not found');
            return;
        }
        
        // Disable button during loading
        button.disabled = true;
        const originalText = button.textContent;
        button.textContent = 'Loading...';
        
        // Calculate next page
        const nextPage = currentPage + 1;
        
        // AJAX request
        jQuery.ajax({
            url: stcAjax.ajax_url,
            type: 'POST',
            data: {
                action: 'stc_load_more_deliveries',
                nonce: stcAjax.nonce,
                page: nextPage,
                per_page: perPage,
                user_id: userId,
                type: type
            },
            success: function(response) {
                if (response.success && response.data && response.data.html) {
                    // Append new items
                    historyBody.insertAdjacentHTML('beforeend', response.data.html);
                    
                    // Update button
                    if (response.data.has_more) {
                        button.setAttribute('data-page', nextPage);
                        button.disabled = false;
                        button.textContent = originalText;
                    } else {
                        // Hide button and container
                        if (container) {
                            container.style.display = 'none';
                        } else {
                            button.style.display = 'none';
                        }
                    }
                } else {
                    button.disabled = false;
                    button.textContent = originalText;
                    console.error('AJAX response error:', response);
                }
            },
            error: function(xhr, status, error) {
                button.disabled = false;
                button.textContent = originalText;
                console.error('AJAX error:', status, error);
            }
        });
    }
    
    // Show/Hide View More Handler for rankings
    function handleRankingsViewMore(button) {
        const itemsPerLoad = parseInt(button.getAttribute('data-items-per-load')) || 5;
        const hiddenClass = button.getAttribute('data-hidden-class') || 'rankings_item-hidden';
        const containerClass = button.getAttribute('data-container-class') || 'rankings-view-more-container';
        
        const hiddenItems = document.querySelectorAll('.' + hiddenClass);
        
        if (hiddenItems.length === 0) {
            return;
        }
        
        // Show next batch of items
        let showCount = 0;
        hiddenItems.forEach(function(item) {
            if (showCount < itemsPerLoad) {
                item.classList.remove(hiddenClass);
                showCount++;
            }
        });
        
        // Check if there are more hidden items
        const remainingHidden = document.querySelectorAll('.' + hiddenClass);
        if (remainingHidden.length === 0) {
            // Hide button and container
            const container = button.closest('.' + containerClass);
            if (container) {
                container.style.display = 'none';
            } else {
                button.style.display = 'none';
            }
        }
    }
    
    // Attach event listeners separately
    const deliveryViewMoreButtons = document.querySelectorAll('.stc-view-more-btn');
    deliveryViewMoreButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            handleDeliveryViewMore(this);
        });
    });
    
    const rankingsViewMoreButtons = document.querySelectorAll('.rankings-view-more-btn');
    rankingsViewMoreButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            handleRankingsViewMore(this);
        });
    });

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

    // Avatar upload handler
    const avatarInput = document.getElementById('stc-avatar-input');
    const avatarForm = document.querySelector('.stc-avatar-upload-form');
    
    if (avatarInput && avatarForm) {
        avatarInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                // Show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    const avatarImage = document.getElementById('stc-avatar-image');
                    if (avatarImage) {
                        avatarImage.src = e.target.result;
                    }
                };
                reader.readAsDataURL(this.files[0]);
                
                // Auto submit form
                avatarForm.submit();
            }
        });
    }

    // User name edit handler
    const nameEditBtn = document.getElementById('stc-name-edit-btn');
    const nameSaveBtn = document.getElementById('stc-name-save-btn');
    const nameDisplay = document.getElementById('stc-user-name-display');
    const nameInput = document.getElementById('stc-user-name-input');
    const nameSaveForm = document.getElementById('stc-name-save-form');
    const nameSaveInput = document.getElementById('stc-name-save-input');
    
    if (nameEditBtn && nameDisplay && nameInput && nameSaveForm && nameSaveBtn) {
        nameEditBtn.addEventListener('click', function() {
            // Switch to edit mode
            nameDisplay.style.display = 'none';
            nameEditBtn.style.display = 'none';
            nameInput.style.display = 'block';
            nameSaveForm.style.display = 'inline-block';
            nameInput.focus();
            nameInput.select();
        });
        
        nameSaveForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const newName = nameInput.value.trim();
            if (newName) {
                nameSaveInput.value = newName;
                nameSaveForm.submit();
            }
        });
        
        // Also allow Enter key to save
        nameInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                nameSaveForm.dispatchEvent(new Event('submit'));
            } else if (e.key === 'Escape') {
                // Cancel edit
                nameInput.style.display = 'none';
                nameSaveForm.style.display = 'none';
                nameDisplay.style.display = 'block';
                nameEditBtn.style.display = 'inline-block';
                nameInput.value = nameDisplay.textContent;
            }
        });
    }

    // Delete delivery form handler with confirm
    function attachDeleteFormListeners() {
        const deleteForms = document.querySelectorAll('.stc-delete-form');
        deleteForms.forEach(function(form) {
            if (!form.hasAttribute('data-delete-attached')) {
                form.setAttribute('data-delete-attached', 'true');
                form.addEventListener('submit', function(e) {
                    // Confirm before submitting
                    if (!confirm('この配信記録を削除してもよろしいですか？')) {
                        e.preventDefault();
                        return false;
                    }
                    // Form will submit normally and page will reload
                });
            }
        });
    }
    
    // Initial attach
    attachDeleteFormListeners();
    
    // Re-attach when new items are loaded via AJAX
    const deleteFormObserver = new MutationObserver(function(mutations) {
        attachDeleteFormListeners();
    });
    
    deleteFormObserver.observe(document.body, {
        childList: true,
        subtree: true
    });

    // Monthly stats selector handler (modal with year and month)
    const monthSelectorBtn = document.getElementById('stc-month-selector-btn');
    const monthSelectorDropdown = document.getElementById('stc-month-selector-dropdown');
    const yearSelect = document.getElementById('stc-year-select');
    const monthSelect = document.getElementById('stc-month-select');
    const monthSelectorText = document.getElementById('stc-month-selector-text');
    const monthSelectorApply = document.getElementById('stc-month-selector-apply');
    const selectedMonthSales = document.getElementById('stc-selected-month-sales');
    const selectedMonthHours = document.getElementById('stc-selected-month-hours');
    const monthlyStatsData = document.getElementById('stc-monthly-stats-data');
    
    let currentSelectedYear = yearSelect ? yearSelect.value : '';
    let currentSelectedMonth = monthSelect ? monthSelect.value : '';
    
    function updateMonthlyStats(year, month) {
        if (!selectedMonthSales || !selectedMonthHours) {
            return;
        }
        
        // Format month with leading zero if needed
        const monthKey = year + '-' + (parseInt(month) < 10 ? '0' : '') + month;
        
        let sales = 0;
        let hours = 0;
        
        // Get data from JSON if available
        if (monthlyStatsData) {
            try {
                const statsData = JSON.parse(monthlyStatsData.textContent);
                if (statsData[monthKey]) {
                    sales = parseFloat(statsData[monthKey].sales) || 0;
                    hours = parseFloat(statsData[monthKey].hours) || 0;
                }
            } catch (e) {
                console.error('Error parsing monthly stats data:', e);
            }
        }
        
        // Format and update sales
        selectedMonthSales.textContent = '¥' + Math.floor(sales).toLocaleString('ja-JP');
        
        // Format and update hours
        selectedMonthHours.textContent = hours.toLocaleString('ja-JP', {
            minimumFractionDigits: 1,
            maximumFractionDigits: 1
        });
    }
    
    function updateSelectorText(year, month) {
        if (monthSelectorText) {
            monthSelectorText.textContent = year + '年' + month + '月 実績';
        }
    }
    
    // Toggle dropdown
    if (monthSelectorBtn && monthSelectorDropdown) {
        monthSelectorBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            const isVisible = monthSelectorDropdown.style.display !== 'none';
            monthSelectorDropdown.style.display = isVisible ? 'none' : 'block';
            
            // Update arrow rotation
            const arrow = monthSelectorBtn.querySelector('.stc-month-selector-arrow');
            if (arrow) {
                arrow.style.transform = isVisible ? 'rotate(0deg)' : 'rotate(180deg)';
            }
        });
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            if (monthSelectorDropdown && 
                !monthSelectorBtn.contains(e.target) && 
                !monthSelectorDropdown.contains(e.target)) {
                monthSelectorDropdown.style.display = 'none';
                const arrow = monthSelectorBtn.querySelector('.stc-month-selector-arrow');
                if (arrow) {
                    arrow.style.transform = 'rotate(0deg)';
                }
            }
        });
    }
    
    // Apply button handler
    if (monthSelectorApply && yearSelect && monthSelect) {
        monthSelectorApply.addEventListener('click', function(e) {
            e.stopPropagation();
            const selectedYear = yearSelect.value;
            const selectedMonth = monthSelect.value;
            
            currentSelectedYear = selectedYear;
            currentSelectedMonth = selectedMonth;
            
            // Update text and stats
            updateSelectorText(selectedYear, selectedMonth);
            updateMonthlyStats(selectedYear, selectedMonth);
            
            // Close dropdown
            if (monthSelectorDropdown) {
                monthSelectorDropdown.style.display = 'none';
                const arrow = monthSelectorBtn.querySelector('.stc-month-selector-arrow');
                if (arrow) {
                    arrow.style.transform = 'rotate(0deg)';
                }
            }
        });
    }

});
