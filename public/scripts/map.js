document.addEventListener('DOMContentLoaded', () => {
    const markers = document.querySelectorAll('.desk-marker');
    const panel = document.getElementById('booking-panel');
    const closeBtn = document.getElementById('close-panel-btn');
    const cancelBtn = document.getElementById('cancel-panel-btn');
    const confirmBtn = document.getElementById('confirm-booking-btn');
    const grid = document.querySelector('.dashboard-grid');

    // UI elements to populate
    const emptyState = document.getElementById('desk-empty-state');
    const detailsContainer = document.getElementById('desk-details-container');
    const panelActions = document.getElementById('panel-actions');
    
    const uiDeskId = document.getElementById('detail-desk-id');
    const uiDeskName = document.getElementById('detail-desk-name');
    const uiDeskBadge = document.getElementById('detail-desk-badge');
    const uiDeskFeatures = document.getElementById('detail-desk-features');
    
    // Booking Alerts elements
    const uiAlertBox = document.getElementById('detail-desk-alert');
    const uiAlertTitle = document.getElementById('alert-title');
    const uiAlertText = document.getElementById('alert-text');
    const uiAlertIcon = document.querySelector('.alert-box__icon');

    const mapContainer = document.getElementById('map-container');
    const hasBookingToday = mapContainer ? mapContainer.getAttribute('data-has-booking') === 'true' : false;
    const bookedDeskId = mapContainer ? mapContainer.getAttribute('data-booked-desk') : null;

    let currentSelectedMarker = null;
    let currentDeskId = null;
    let isDeskAvailable = false;

    // Get selected date from the UI
    const dateInput = document.querySelector('.control-input__select[type="date"]');
    const selectedDate = dateInput ? dateInput.value : new Date().toISOString().split('T')[0];

    // Filter Logic
    const filterBtns = document.querySelectorAll('#desk-filters .filter-btn');
    const urlParams = new URLSearchParams(window.location.search);
    let activeFilters = urlParams.get('status') ? urlParams.get('status').split(',') : ['all'];

    const updateURL = () => {
        const newUrl = new URL(window.location);
        if (activeFilters.includes('all') || activeFilters.length === 3 || activeFilters.length === 0) {
            newUrl.searchParams.delete('status');
        } else {
            newUrl.searchParams.set('status', activeFilters.join(','));
        }
        window.history.replaceState({}, '', newUrl);
    };

    const applyFilters = () => {
        // update button visual states
        filterBtns.forEach(btn => {
            const status = btn.getAttribute('data-status');
            if (activeFilters.includes('all') || activeFilters.includes(status)) {
                btn.classList.add('is-toggled');
            } else {
                btn.classList.remove('is-toggled');
            }
        });

        // update markers
        markers.forEach(marker => {
            const status = marker.getAttribute('data-status');
            if (activeFilters.includes('all') || activeFilters.includes(status)) {
                marker.classList.remove('is-hidden');
            } else {
                marker.classList.add('is-hidden');
            }
        });
        updateURL();
    };

    if (filterBtns.length > 0) {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const status = btn.getAttribute('data-status');
                
                if (status === 'all') {
                    activeFilters = ['all'];
                } else {
                    // Remove 'all' if present
                    activeFilters = activeFilters.filter(f => f !== 'all');
                    
                    // Toggle current status
                    if (activeFilters.includes(status)) {
                        activeFilters = activeFilters.filter(f => f !== status);
                    } else {
                        activeFilters.push(status);
                    }
                    
                    // If all 3 are selected or none are selected, revert to 'all'
                    if (activeFilters.length === 3 || activeFilters.length === 0) {
                        activeFilters = ['all'];
                    }
                }
                
                // clear selection if we hide the selected marker
                if (currentSelectedMarker && currentSelectedMarker.classList.contains('is-hidden')) {
                    closePanel();
                }

                applyFilters();
            });
        });
        
        // Initialize filters on load
        applyFilters();
    }

    // --- Mobile Map Navigation (Shared Utility) ---
    if (mapContainer) {
        MapNavigation.init('map-container', 'map-zoom-toggle', 'map-placeholder--zoomed');
    }

    const clearSelection = () => {
        if (currentSelectedMarker) {
            currentSelectedMarker.classList.remove('desk-marker--selected');
            currentSelectedMarker = null;
        }
        currentDeskId = null;
        isDeskAvailable = false;
        if (confirmBtn) confirmBtn.disabled = true;
    };

    const populatePanel = (deskData) => {
        // Hide empty state, show details
        emptyState.classList.add('is-hidden');
        detailsContainer.classList.remove('is-hidden');
        panelActions.classList.remove('is-hidden');

        // Basic info
        uiDeskId.textContent = `Desk ${deskData.identifier}`;
        uiDeskName.textContent = deskData.description || 'Standard Workspace';

        // Status badge
        let status = 'Available';
        let badgeClass = 'badge--success';
        isDeskAvailable = true;
        
        if (currentSelectedMarker) {
            const titleStr = currentSelectedMarker.getAttribute('title');
            if (titleStr.includes('occupied')) {
                status = 'Occupied';
                badgeClass = '';
                uiDeskBadge.style.background = 'var(--color-danger-bg)';
                uiDeskBadge.style.color = 'var(--color-danger)';
                isDeskAvailable = false;
            } else if (titleStr.includes('maintenance')) {
                status = 'Maintenance';
                badgeClass = '';
                uiDeskBadge.style.background = 'var(--color-warning)';
                uiDeskBadge.style.color = '#fff';
                isDeskAvailable = false;
            } else {
                uiDeskBadge.removeAttribute('style');
            }
        }
        
        uiDeskBadge.textContent = status;
        uiDeskBadge.className = `badge desktop-only ${badgeClass}`;

        // Setup Alert Box based on booking status
        if (hasBookingToday) {
            uiAlertBox.classList.remove('is-hidden');
            if (String(currentDeskId) === bookedDeskId) {
                uiAlertBox.style.background = '#f0fdf4';
                uiAlertBox.style.borderColor = '#bbf7d0';
                uiAlertIcon.style.color = 'var(--color-success)';
                uiAlertIcon.textContent = 'check_circle';
                uiAlertTitle.style.color = 'var(--color-success)';
                uiAlertTitle.textContent = 'Your Workspace';
                uiAlertText.textContent = 'You have successfully booked this desk for the selected date.';
            } else {
                uiAlertBox.style.background = '#fff7ed';
                uiAlertBox.style.borderColor = '#ffedd5';
                uiAlertIcon.style.color = 'var(--color-warning)';
                uiAlertIcon.textContent = 'warning';
                uiAlertTitle.style.color = 'var(--color-warning)';
                uiAlertTitle.textContent = 'Booking Exists';
                uiAlertText.textContent = 'You already have a desk reserved for this day. Manage your bookings to change it.';
            }
        } else {
            uiAlertBox.classList.add('is-hidden');
        }

        // Enable or disable the confirm button based on availability
        if (confirmBtn) {
            confirmBtn.disabled = !isDeskAvailable;
            if (!isDeskAvailable) {
                confirmBtn.style.opacity = '0.5';
                confirmBtn.style.cursor = 'not-allowed';
                confirmBtn.textContent = 'Desk Unavailable';
            } else {
                confirmBtn.style.opacity = '1';
                confirmBtn.style.cursor = 'pointer';
                confirmBtn.textContent = 'Confirm Booking';
            }
        }

        // Features
        uiDeskFeatures.innerHTML = '';
        if (deskData.features && deskData.features.length > 0) {
            deskData.features.forEach(feature => {
                const tag = document.createElement('span');
                tag.className = 'feature-tag';
                tag.innerHTML = `<span class="material-symbols-outlined">${feature.icon_name}</span> ${feature.name}`;
                uiDeskFeatures.appendChild(tag);
            });
        } else {
            uiDeskFeatures.innerHTML = '<span class="feature-tag">Standard Setup</span>';
        }
    };

    const fetchDeskDetails = async (id) => {
        // Show loading state
        emptyState.classList.add('is-hidden');
        detailsContainer.classList.remove('is-hidden');
        uiDeskName.textContent = 'Loading...';
        uiDeskFeatures.innerHTML = '';
        
        try {
            const response = await fetch(`/deskDetails?id=${id}`);
            if (!response.ok) throw new Error('Failed to fetch data');
            const data = await response.json();
            populatePanel(data);
        } catch (error) {
            console.error(error);
            uiDeskName.textContent = 'Error loading details.';
        }
    };

    const bookDesk = async () => {
        if (!currentDeskId || !isDeskAvailable || !confirmBtn) return;

        confirmBtn.disabled = true;
        confirmBtn.textContent = 'Booking...';

        const data = new URLSearchParams();
        data.append('desk_id', currentDeskId);
        data.append('date', selectedDate);

        try {
            const response = await fetch('/bookDesk', {
                method: 'POST',
                body: data
            });
            const result = await response.json();

            if (response.ok) {
                Toast.show('Booking successful!');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                Toast.show(result.message || 'Failed to book desk.', 'error');
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirm Booking';
            }
        } catch (error) {
            console.error(error);
            Toast.show('An error occurred while booking the desk.', 'error');
            confirmBtn.disabled = false;
            confirmBtn.textContent = 'Confirm Booking';
        }
    };

    const openPanel = (marker, id) => {
        clearSelection();
        currentSelectedMarker = marker;
        currentDeskId = id;
        currentSelectedMarker.classList.add('desk-marker--selected');

        panel.classList.add('side-panel--active');
        grid.classList.add('dashboard-grid--with-sidebar');
        
        fetchDeskDetails(id);
    };

    const closePanel = () => {
        clearSelection();
        panel.classList.remove('side-panel--active');
        grid.classList.remove('dashboard-grid--with-sidebar');
        
        // Reset panel view
        setTimeout(() => {
            emptyState.classList.remove('is-hidden');
            detailsContainer.classList.add('is-hidden');
            panelActions.classList.add('is-hidden');
        }, 300); // Wait for transition
    };

    markers.forEach(marker => {
        marker.addEventListener('click', (e) => {
            const id = marker.getAttribute('data-id');
            openPanel(e.currentTarget, id);
        });
    });

    if (closeBtn) closeBtn.addEventListener('click', closePanel);
    if (cancelBtn) cancelBtn.addEventListener('click', closePanel);
    if (confirmBtn) confirmBtn.addEventListener('click', bookDesk);
});
