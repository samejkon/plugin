document.addEventListener("DOMContentLoaded", function () {
    const modal = document.querySelector(".stc-record-modal");
    const openBtn = document.querySelector(".js-open-record-modal");
    const closeBtns = document.querySelectorAll(".js-close-record-modal");

    if (!modal || !openBtn) return;

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

    // Auto open calendar picker when click on date input
    const dateInputs = document.querySelectorAll('input[type="date"]');
    
    dateInputs.forEach(function(input) {
        input.addEventListener('click', function() {
            // Use showPicker() if available (modern browsers)
            if (typeof this.showPicker === 'function') {
                try {
                    this.showPicker();
                } catch (error) {
                    // Fallback: just focus on the input
                    this.focus();
                }
            } else {
                // Fallback for older browsers
                this.focus();
            }
        });
    });
});

