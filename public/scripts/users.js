document.addEventListener('DOMContentLoaded', () => {
    const editButtons = document.querySelectorAll('.js-edit-user-btn');
    const panel = document.getElementById('edit-user-panel');
    const backdrop = document.getElementById('side-panel-backdrop');
    const grid = document.getElementById('users-grid');
    const closeBtn = document.getElementById('close-panel-btn');
    const cancelBtn = document.getElementById('cancel-panel-btn');

    // Form inputs
    const inputFullName = document.getElementById('edit-fullname');
    const inputJobTitle = document.getElementById('edit-jobtitle');
    const inputEmail = document.getElementById('edit-email');
    const inputRole = document.getElementById('edit-role');
    const inputActive = document.getElementById('edit-active');
    const saveBtn = document.getElementById('save-user-btn');
    const deleteBtn = document.getElementById('delete-user-btn');

    let currentUserId = null;

    const openPanel = (event) => {
        // Find the button that was clicked
        const btn = event.currentTarget;

        // Extract data from the button's data- attributes
        currentUserId = btn.getAttribute('data-id');
        const fullName = btn.getAttribute('data-fullname');
        const jobTitle = btn.getAttribute('data-jobtitle');
        const email = btn.getAttribute('data-email');
        const role = btn.getAttribute('data-role');
        const isActive = btn.getAttribute('data-active') === '1';

        // Populate the form
        if (inputFullName) inputFullName.value = fullName || '';
        if (inputJobTitle) inputJobTitle.value = jobTitle || '';
        if (inputEmail) inputEmail.value = email || '';
        if (inputRole) inputRole.value = role || 'EMPLOYEE';
        if (inputActive) inputActive.checked = isActive;

        // Show the panel
        if (panel) panel.classList.add('side-panel--active');
        if (backdrop) backdrop.classList.add('side-panel-backdrop--active');
        if (grid) grid.classList.add('dashboard-grid--with-sidebar');
    };

    const closePanel = () => {
        currentUserId = null;
        if (panel) panel.classList.remove('side-panel--active');
        if (backdrop) backdrop.classList.remove('side-panel-backdrop--active');
        if (grid) grid.classList.remove('dashboard-grid--with-sidebar');
    };

    const saveChanges = () => {
        const data = new URLSearchParams();
        data.append('id', currentUserId);
        data.append('email', inputEmail.value);
        data.append('full_name', inputFullName.value);
        data.append('job_title', inputJobTitle.value);
        data.append('role', inputRole.value);
        data.append('is_active', inputActive.checked);

        fetch('/updateUser', {
            method: 'POST',
            body: data
        }).then(async response => {
            if (response.ok) {
                Toast.show('User updated successfully!');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                Toast.show(result.message || 'Failed to update user.', 'error');
            }
        });
    };

    const deleteUser = () => {
        Modal.confirm('Delete User', 'Are you sure you want to delete this user? This action cannot be undone.', () => {
            const data = new URLSearchParams();
            data.append('id', currentUserId);

            fetch('/deleteUser', {
                method: 'POST',
                body: data
            }).then(async response => {
                if (response.ok) {
                    Toast.show('User deleted successfully!');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    const result = await response.json();
                    Toast.show(result.message || 'Failed to delete user.', 'error');
                }
            });
        });
    };

    // Attach event to all edit buttons
    editButtons.forEach(btn => {
        btn.addEventListener('click', openPanel);
    });

    // Reset Password Logic
    const resetPasswordButtons = document.querySelectorAll('.js-reset-password-btn');
    resetPasswordButtons.forEach(btn => {
        btn.addEventListener('click', (e) => {
            const userId = btn.getAttribute('data-id');
            const userName = btn.getAttribute('data-name');
            
            Modal.confirm('Reset Password', `Are you sure you want to reset the password for ${userName}? A new temporary password will be generated.`, () => {
                const data = new URLSearchParams();
                data.append('id', userId);

                fetch('/resetPassword', {
                    method: 'POST',
                    body: data
                }).then(async response => {
                    const result = await response.json();
                    if (response.ok) {
                        Toast.show(`Password reset successfully!`);
                        
                        // Show the new password in a modal
                        Modal.alert('Temporary Password', `The new password for ${userName} is: <strong>${result.newPassword}</strong><br><br>Please copy this and give it to the user.`);
                    } else {
                        Toast.show(result.message || 'Failed to reset password.', 'error');
                    }
                }).catch(err => {
                    Toast.show('Network error occurred.', 'error');
                });
            });
        });
    });

    // Attach save and delete events
    if (saveBtn) saveBtn.addEventListener('click', saveChanges);
    if (deleteBtn) deleteBtn.addEventListener('click', deleteUser);

    // Attach close events
    if (closeBtn) closeBtn.addEventListener('click', closePanel);
    if (cancelBtn) cancelBtn.addEventListener('click', closePanel);
    if (backdrop) backdrop.addEventListener('click', closePanel);
});