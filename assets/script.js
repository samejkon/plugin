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
        let currentVisible = 10;
        const itemsPerLoad = 10;
        
        viewMoreBtn.addEventListener('click', function() {
            const hiddenItems = document.querySelectorAll('.stc-history-item-hidden');
            const totalHidden = hiddenItems.length;
            
            let showCount = 0;
            hiddenItems.forEach(function(item) {
                if (showCount < itemsPerLoad) {
                    item.classList.remove('stc-history-item-hidden');
                    showCount++;
                }
            });
            
            currentVisible += showCount;
            
            const remainingHidden = document.querySelectorAll('.stc-history-item-hidden');
            if (remainingHidden.length === 0) {
                viewMoreBtn.style.display = 'none';
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

});
