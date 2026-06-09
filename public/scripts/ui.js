window.Toast = {
    show: function(message, type = 'success') {
        let container = document.querySelector('.toast-container');
        if (!container) {
            container = document.createElement('div');
            container.className = 'toast-container';
            document.body.appendChild(container);
        }

        const toast = document.createElement('div');
        toast.className = `toast toast--${type}`;
        
        const icon = type === 'success' ? 'check_circle' : 'error';
        
        toast.innerHTML = `
            <span class="material-symbols-outlined toast__icon">${icon}</span>
            <span class="toast__text">${message}</span>
        `;
        
        container.appendChild(toast);
        
        // Trigger reflow
        toast.offsetHeight;
        toast.classList.add('toast--show');
        
        setTimeout(() => {
            toast.classList.remove('toast--show');
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
};

window.Modal = {
    confirm: function(title, message, onConfirm) {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        
        overlay.innerHTML = `
            <div class="modal">
                <h3 class="modal__title">${title}</h3>
                <p class="modal__text">${message}</p>
                <div class="modal__actions">
                    <button class="btn btn--text" id="modal-cancel">Cancel</button>
                    <button class="btn btn--primary btn--danger" id="modal-confirm">Confirm</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        // Trigger reflow
        overlay.offsetHeight;
        overlay.classList.add('modal-overlay--show');
        
        const close = () => {
            overlay.classList.remove('modal-overlay--show');
            setTimeout(() => overlay.remove(), 200);
        };

        overlay.querySelector('#modal-cancel').addEventListener('click', close);
        overlay.querySelector('#modal-confirm').addEventListener('click', () => {
            close();
            onConfirm();
        });
    },
    alert: function(title, message, onOk) {
        const overlay = document.createElement('div');
        overlay.className = 'modal-overlay';
        
        overlay.innerHTML = `
            <div class="modal">
                <h3 class="modal__title">${title}</h3>
                <p class="modal__text">${message}</p>
                <div class="modal__actions">
                    <button class="btn btn--primary" id="modal-ok">OK</button>
                </div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        // Trigger reflow
        overlay.offsetHeight;
        overlay.classList.add('modal-overlay--show');
        
        const close = () => {
            overlay.classList.remove('modal-overlay--show');
            setTimeout(() => overlay.remove(), 200);
            if (onOk) onOk();
        };

        overlay.querySelector('#modal-ok').addEventListener('click', close);
    }
};

window.MapNavigation = {
    init: function(containerId, toggleBtnId, zoomClass) {
        const container = document.getElementById(containerId);
        const toggleBtn = document.getElementById(toggleBtnId);
        
        if (!container || !toggleBtn) return;

        toggleBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            const isZoomed = container.classList.toggle(zoomClass);
            const icon = toggleBtn.querySelector('.material-symbols-outlined');
            
            if (isZoomed) {
                icon.textContent = 'zoom_out';
                toggleBtn.classList.add('map-zoom-btn--active');
                Toast.show('Zoom enabled. Swipe or drag to pan the map.', 'success');
                container.style.cursor = 'grab';
            } else {
                icon.textContent = 'zoom_in';
                toggleBtn.classList.remove('map-zoom-btn--active');
                container.style.cursor = ''; // Revert to default for that view
            }
        });

        // Drag-to-scroll implementation
        let isPanning = false;
        let pStartX, pStartY, pScrollLeft, pScrollTop;

        container.addEventListener('mousedown', (e) => {
            if (!container.classList.contains(zoomClass)) return;
            isPanning = true;
            container.style.cursor = 'grabbing';
            pStartX = e.pageX - container.offsetLeft;
            pStartY = e.pageY - container.offsetTop;
            pScrollLeft = container.scrollLeft;
            pScrollTop = container.scrollTop;
        });

        const stopPanning = () => {
            if (isPanning) {
                isPanning = false;
                container.style.cursor = 'grab';
            }
        };

        container.addEventListener('mouseleave', stopPanning);
        container.addEventListener('mouseup', stopPanning);

        container.addEventListener('mousemove', (e) => {
            if (!isPanning) return;
            e.preventDefault();
            const x = e.pageX - container.offsetLeft;
            const y = e.pageY - container.offsetTop;
            const walkX = (x - pStartX) * 1.5; 
            const walkY = (y - pStartY) * 1.5;
            container.scrollLeft = pScrollLeft - walkX;
            container.scrollTop = pScrollTop - walkY;
        });
    }
};

// --- Mobile Navigation Logic ---
document.addEventListener('DOMContentLoaded', () => {
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileNavOverlay = document.getElementById('mobile-nav-overlay');
    const mobileMenuCloseBtn = document.getElementById('mobile-menu-close');

    if (mobileMenuBtn && mobileNavOverlay && mobileMenuCloseBtn) {
        const openNav = () => {
            mobileNavOverlay.classList.add('mobile-nav-overlay--active');
        };

        const closeNav = (e) => {
            // Close if X button clicked OR click was on the overlay background (not the menu itself)
            if (e.currentTarget === mobileMenuCloseBtn || e.target === mobileNavOverlay) {
                mobileNavOverlay.classList.remove('mobile-nav-overlay--active');
            }
        };

        mobileMenuBtn.addEventListener('click', openNav);
        mobileMenuCloseBtn.addEventListener('click', closeNav);
        mobileNavOverlay.addEventListener('click', closeNav);
    }

    // --- Password Visibility Toggle ---
    const toggleButtons = document.querySelectorAll('.js-toggle-password');
    toggleButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            e.preventDefault();
            const targetId = btn.getAttribute('data-target');
            const passwordInput = document.getElementById(targetId);
            const icon = btn.querySelector('.material-symbols-outlined');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.textContent = 'visibility_off';
            } else {
                passwordInput.type = 'password';
                icon.textContent = 'visibility';
            }
        });
    });

    // --- Change Password Logic ---
    const cpBtnDesktop = document.getElementById('nav-change-password-btn');
    const cpBtnMobile = document.getElementById('mobile-change-password-btn');
    const cpModal = document.getElementById('change-password-modal');
    const cpForm = document.getElementById('change-password-form');
    const cpCancelBtn = document.getElementById('cp-cancel-btn');

    if (cpModal && cpForm) {
        const openCpModal = (e) => {
            e.preventDefault();
            cpForm.reset();
            if (mobileNavOverlay) mobileNavOverlay.classList.remove('mobile-nav-overlay--active');
            cpModal.classList.add('modal-overlay--show');
        };

        const closeCpModal = () => {
            cpModal.classList.remove('modal-overlay--show');
        };

        if (cpBtnDesktop) cpBtnDesktop.addEventListener('click', openCpModal);
        if (cpBtnMobile) cpBtnMobile.addEventListener('click', openCpModal);
        if (cpCancelBtn) cpCancelBtn.addEventListener('click', closeCpModal);

        cpForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = document.getElementById('cp-submit-btn');
            const originalText = submitBtn.textContent;
            
            const formData = new FormData(cpForm);
            const data = new URLSearchParams();
            for (const pair of formData) {
                data.append(pair[0], pair[1]);
            }

            submitBtn.disabled = true;
            submitBtn.textContent = 'Updating...';

            fetch('/changePassword', {
                method: 'POST',
                body: data
            }).then(async response => {
                const result = await response.json();
                if (response.ok) {
                    Toast.show(result.message || 'Password updated successfully!');
                    closeCpModal();
                } else {
                    Toast.show(result.message || 'Failed to update password.', 'error');
                }
            }).catch(err => {
                Toast.show('Network error occurred.', 'error');
            }).finally(() => {
                submitBtn.disabled = false;
                submitBtn.textContent = originalText;
            });
        });
    }
});