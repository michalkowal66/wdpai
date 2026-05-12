document.addEventListener('DOMContentLoaded', () => {
    const editButtons = document.querySelectorAll('.js-edit-maintenance-btn');
    const panel = document.getElementById('maintenance-panel');
    const backdrop = document.getElementById('side-panel-backdrop');
    const closeBtn = document.getElementById('close-panel-btn');
    const cancelBtn = document.getElementById('cancel-panel-btn');
    const saveBtn = document.getElementById('save-mnt-btn');

    // Form inputs
    const inputStart = document.getElementById('mnt-start');
    const inputEnd = document.getElementById('mnt-end');
    const inputReason = document.getElementById('mnt-reason');
    const titleDeskName = document.getElementById('panel-title');

    let currentDeskId = null;

    // Set min date to today for start date
    const today = new Date().toISOString().split('T')[0];
    if (inputStart) inputStart.min = today;
    if (inputEnd) inputEnd.min = today;

    if (inputStart && inputEnd) {
        inputStart.addEventListener('change', (e) => {
            inputEnd.min = e.target.value;
            if (inputEnd.value && inputEnd.value < e.target.value) {
                inputEnd.value = e.target.value;
            }
        });
    }

    const openPanel = (event) => {
        const btn = event.currentTarget;
        currentDeskId = btn.getAttribute('data-id');
        const deskIdentifier = btn.getAttribute('data-identifier');

        // Populate title
        if (titleDeskName) {
            titleDeskName.textContent = `Set Maintenance: Desk ${deskIdentifier}`;
        }

        // Reset form
        if (inputStart) inputStart.value = today;
        if (inputEnd) inputEnd.value = today;
        if (inputReason) inputReason.value = '';

        // Show the panel
        if (panel) panel.classList.add('side-panel--active');
        if (backdrop) backdrop.classList.add('side-panel-backdrop--active');
    };

    const closePanel = () => {
        currentDeskId = null;
        if (panel) panel.classList.remove('side-panel--active');
        if (backdrop) backdrop.classList.remove('side-panel-backdrop--active');
    };

    const saveMaintenance = () => {
        if (!currentDeskId || !inputStart.value || !inputEnd.value) {
            Toast.show('Please fill in all required fields.', 'error');
            return;
        }

        if (new Date(inputStart.value) > new Date(inputEnd.value)) {
            Toast.show('End date cannot be before start date.', 'error');
            return;
        }

        const data = new URLSearchParams();
        data.append('desk_id', currentDeskId);
        data.append('start_date', inputStart.value);
        data.append('end_date', inputEnd.value);
        data.append('reason', inputReason.value);

        fetch('/setMaintenance', {
            method: 'POST',
            body: data
        }).then(async response => {
            if (response.ok) {
                Toast.show('Maintenance scheduled successfully!');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                Toast.show(result.message || 'Failed to schedule maintenance.', 'error');
            }
        });
    };

    // Attach events
    editButtons.forEach(btn => {
        btn.addEventListener('click', openPanel);
    });

    if (saveBtn) saveBtn.addEventListener('click', saveMaintenance);
    if (closeBtn) closeBtn.addEventListener('click', closePanel);
    if (cancelBtn) cancelBtn.addEventListener('click', closePanel);
    if (backdrop) backdrop.addEventListener('click', closePanel);
});
