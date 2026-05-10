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
    }
};