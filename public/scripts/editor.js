document.addEventListener('DOMContentLoaded', () => {
    const mapWrapper = document.getElementById('editor-map');
    const panel = document.getElementById('editor-panel');
    const backdrop = document.getElementById('side-panel-backdrop');
    const grid = document.getElementById('editor-grid');
    const closeBtn = document.getElementById('close-panel-btn');
    const markers = document.querySelectorAll('.desk-marker');
    
    const panelTitle = document.getElementById('panel-title');
    const panelSubtitle = document.getElementById('panel-subtitle');
    const form = document.getElementById('desk-editor-form');
    
    // Inputs
    const inputId = document.getElementById('edit-id');
    const inputPosX = document.getElementById('edit-pos-x');
    const inputPosY = document.getElementById('edit-pos-y');
    const inputIdentifier = document.getElementById('edit-identifier');
    const inputDescription = document.getElementById('edit-description');
    
    // Features UI
    const featureSelect = document.getElementById('feature-select');
    const addFeatureBtn = document.getElementById('add-feature-btn');
    const selectedFeaturesContainer = document.getElementById('selected-features-container');
    const hiddenFeaturesInputs = document.getElementById('hidden-features-inputs');

    // Buttons
    const saveBtn = document.getElementById('save-desk-btn');
    const deactivateBtn = document.getElementById('deactivate-desk-btn');
    const reactivateBtn = document.getElementById('reactivate-desk-btn');
    const hardDeleteBtn = document.getElementById('hard-delete-desk-btn');

    let currentMarker = null;
    let ghostMarker = null;
    let activeFeatures = new Map(); // Store id -> {name, icon}

    // --- Drag and Drop Logic ---
// ... (keep drag and drop logic unchanged) ...
    let isDragging = false;
    let draggedMarker = null;

    const calculatePercentage = (clientX, clientY) => {
        const rect = mapWrapper.getBoundingClientRect();
        let x = ((clientX - rect.left) / rect.width) * 100;
        let y = ((clientY - rect.top) / rect.height) * 100;
        
        // Constrain to map boundaries
        x = Math.max(0, Math.min(100, x));
        y = Math.max(0, Math.min(100, y));
        
        return { posX: x.toFixed(2), posY: y.toFixed(2) };
    };

    const updatePanelPosition = (posX, posY) => {
        inputPosX.value = posX;
        inputPosY.value = posY;
        panelSubtitle.textContent = `Position: ${posX}% / ${posY}%`;
    };

    if (mapWrapper) {
        mapWrapper.addEventListener('mousemove', (e) => {
            if (!isDragging || !draggedMarker) return;
            e.preventDefault();
            
            const { posX, posY } = calculatePercentage(e.clientX, e.clientY);
            draggedMarker.style.left = `${posX}%`;
            draggedMarker.style.top = `${posY}%`;
            updatePanelPosition(posX, posY);
        });

        mapWrapper.addEventListener('mouseup', () => {
            if (isDragging) {
                isDragging = false;
                draggedMarker.style.cursor = 'grab';
                draggedMarker = null;
            }
        });
        
        mapWrapper.addEventListener('mouseleave', () => {
            if (isDragging) {
                isDragging = false;
                draggedMarker.style.cursor = 'grab';
                draggedMarker = null;
            }
        });
    }

    // --- Filtering Logic ---
    const filterBtns = document.querySelectorAll('#desk-filters .filter-btn');
    // Default to active only if no URL params.
    let activeFilters = ['active']; 

    const applyFilters = () => {
        filterBtns.forEach(btn => {
            const status = btn.getAttribute('data-status');
            if (activeFilters.includes('all') || activeFilters.includes(status)) {
                btn.classList.add('is-toggled');
            } else {
                btn.classList.remove('is-toggled');
            }
        });

        markers.forEach(marker => {
            const status = marker.getAttribute('data-status');
            if (activeFilters.includes('all') || activeFilters.includes(status)) {
                marker.style.display = 'flex';
            } else {
                marker.style.display = 'none';
            }
        });
    };

    if (filterBtns.length > 0) {
        filterBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const status = btn.getAttribute('data-status');
                
                if (status === 'all') {
                    activeFilters = ['all'];
                } else {
                    activeFilters = activeFilters.filter(f => f !== 'all');
                    
                    if (activeFilters.includes(status)) {
                        activeFilters = activeFilters.filter(f => f !== status);
                    } else {
                        activeFilters.push(status);
                    }
                    
                    // If all custom statuses (active, deactivated) are selected, or none are selected, revert to 'all'
                    if (activeFilters.length === 2 || activeFilters.length === 0) {
                        activeFilters = ['all'];
                    }
                }
                
                if (currentMarker && currentMarker.style.display === 'none') {
                    closePanel();
                }
                applyFilters();
            });
        });
        applyFilters();
    }

    // --- Features Rendering ---
    const renderFeatureTags = () => {
        selectedFeaturesContainer.innerHTML = '';
        hiddenFeaturesInputs.innerHTML = '';

        if (activeFeatures.size === 0) {
            selectedFeaturesContainer.innerHTML = '<span style="color: var(--color-text-muted); font-size: 0.875rem;">No features added yet.</span>';
            return;
        }

        activeFeatures.forEach((data, id) => {
            const tag = document.createElement('div');
            tag.style.display = 'inline-flex';
            tag.style.alignItems = 'center';
            tag.style.gap = '0.25rem';
            tag.style.padding = '0.25rem 0.5rem';
            tag.style.background = 'var(--color-primary-light)';
            tag.style.border = '1px solid var(--color-primary)';
            tag.style.borderRadius = 'var(--radius-md)';
            tag.style.color = 'var(--color-primary)';
            tag.style.fontSize = '0.75rem';
            tag.style.fontWeight = '600';

            tag.innerHTML = `
                <span class="material-symbols-outlined" style="font-size: 1rem;">${data.icon}</span>
                ${data.name}
                <span class="material-symbols-outlined remove-feat-btn" data-id="${id}" style="font-size: 1rem; cursor: pointer; margin-left: 0.25rem;">close</span>
            `;

            selectedFeaturesContainer.appendChild(tag);

            const hiddenInput = document.createElement('input');
            hiddenInput.type = 'hidden';
            hiddenInput.name = 'features[]';
            hiddenInput.value = id;
            hiddenFeaturesInputs.appendChild(hiddenInput);
        });

        document.querySelectorAll('.remove-feat-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const featId = e.currentTarget.getAttribute('data-id');
                activeFeatures.delete(featId);
                renderFeatureTags();
            });
        });
    };

    if (addFeatureBtn) {
        addFeatureBtn.addEventListener('click', () => {
            const selectedOption = featureSelect.options[featureSelect.selectedIndex];
            if (!selectedOption || selectedOption.disabled) return;

            const id = selectedOption.value;
            const name = selectedOption.getAttribute('data-name');
            const icon = selectedOption.getAttribute('data-icon');

            if (!activeFeatures.has(id)) {
                activeFeatures.set(id, { name, icon });
                renderFeatureTags();
            }
            
            featureSelect.selectedIndex = 0;
        });
    }

    // --- Panel & Interaction Logic ---
    const clearSelection = () => {
        if (currentMarker) {
            // Revert to original position if closed without saving
            const origX = currentMarker.getAttribute('data-posx');
            const origY = currentMarker.getAttribute('data-posy');
            if (origX && origY) {
                currentMarker.style.left = `${origX}%`;
                currentMarker.style.top = `${origY}%`;
            }
            
            currentMarker.classList.remove('desk-marker--selected');
            currentMarker = null;
        }
        if (ghostMarker) {
            ghostMarker.remove();
            ghostMarker = null;
        }
        inputId.value = '';
        inputIdentifier.value = '';
        inputDescription.value = '';
        activeFeatures.clear();
        renderFeatureTags();
        if (featureSelect) featureSelect.selectedIndex = 0;
    };

    const closePanel = () => {
        clearSelection();
        if (panel) panel.classList.remove('side-panel--active');
        if (backdrop) backdrop.classList.remove('side-panel-backdrop--active');
        if (grid) grid.classList.remove('dashboard-grid--with-sidebar');
    };

    const openPanel = (x, y, isNew) => {
        if (panel) panel.classList.add('side-panel--active');
        if (backdrop) backdrop.classList.add('side-panel-backdrop--active');
        if (grid) grid.classList.add('dashboard-grid--with-sidebar');

        updatePanelPosition(x, y);

        if (isNew) {
            panelTitle.textContent = 'Add New Desk';
            deactivateBtn.style.display = 'none';
            reactivateBtn.style.display = 'none';
        } else {
            panelTitle.textContent = 'Edit Desk';
            
            // Check status of current marker to toggle buttons
            const status = currentMarker.getAttribute('data-status');
            if (status === 'deactivated') {
                deactivateBtn.style.display = 'none';
                reactivateBtn.style.display = 'flex';
            } else {
                deactivateBtn.style.display = 'flex';
                reactivateBtn.style.display = 'none';
            }
        }
    };

    markers.forEach(marker => {
        marker.style.cursor = 'grab'; // Indicate draggability

        marker.addEventListener('mousedown', (e) => {
            e.stopPropagation();
            if (e.button !== 0) return; // Only left click
            isDragging = true;
            draggedMarker = marker;
            marker.style.cursor = 'grabbing';
            
            // Only clear selection and re-fetch if a DIFFERENT marker is clicked
            if (currentMarker !== marker) {
                clearSelection();
                currentMarker = marker;
                currentMarker.classList.add('desk-marker--selected');
                
                const id = marker.getAttribute('data-id');
                const posX = parseFloat(marker.style.left).toFixed(2);
                const posY = parseFloat(marker.style.top).toFixed(2);
                
                inputIdentifier.value = 'Loading...';
                openPanel(posX, posY, false);

                fetch(`/deskDetails?id=${id}`)
                    .then(r => r.json())
                    .then(deskData => {
                        inputId.value = deskData.id;
                        inputIdentifier.value = deskData.identifier;
                        inputDescription.value = deskData.description || '';
                        
                        // Toggle Hard Delete button based on booking history
                        if (deskData.has_bookings) {
                            hardDeleteBtn.style.display = 'none';
                        } else {
                            hardDeleteBtn.style.display = 'flex';
                        }
                        
                        activeFeatures.clear();
                        if (deskData.features && featureSelect) {
                            deskData.features.forEach(f => {
                                const options = Array.from(featureSelect.options);
                                const match = options.find(opt => opt.getAttribute('data-name') === f.name);
                                if (match) {
                                    activeFeatures.set(match.value, { name: f.name, icon: f.icon });
                                }
                            });
                        }
                        renderFeatureTags();
                    }).catch(e => {
                        Toast.show('Failed to load desk details.', 'error');
                    });
            }
        });
        
        // Prevent click from bubbling since mousedown handles opening
        marker.addEventListener('click', (e) => e.stopPropagation());
    });

    if (mapWrapper) {
        mapWrapper.addEventListener('click', (e) => {
            if (isDragging) return; // Don't trigger click if we just finished dragging

            // Only trigger if clicking exactly on the wrapper or the image
            if (e.target.classList.contains('desk-marker') || e.target.closest('.desk-marker')) {
                return;
            }

            clearSelection();

            const { posX, posY } = calculatePercentage(e.clientX, e.clientY);

            ghostMarker = document.createElement('div');
            ghostMarker.className = 'desk-marker desk-marker--ghost';
            ghostMarker.style.left = `${posX}%`;
            ghostMarker.style.top = `${posY}%`;
            ghostMarker.innerHTML = '<span class="material-symbols-outlined desk-marker__icon">add</span>';
            mapWrapper.appendChild(ghostMarker);

            openPanel(posX, posY, true);
        });
    }

    if (saveBtn) {
        saveBtn.addEventListener('click', () => {
            if (!inputIdentifier.value) {
                Toast.show('Identifier is required.', 'error');
                return;
            }

            const formData = new FormData(form);
            const params = new URLSearchParams();
            
            for (const pair of formData.entries()) {
                if (pair[0] === 'features[]') continue;
                params.append(pair[0], pair[1]);
            }
            
            const featureIds = [];
            document.querySelectorAll('input[name="features[]"]').forEach(hidden => {
                featureIds.push(hidden.value);
            });
            params.append('features', featureIds.join(','));

            fetch('/saveDesk', {
                method: 'POST',
                body: params
            }).then(async response => {
                if (response.ok) {
                    Toast.show('Desk saved successfully!');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    const result = await response.json();
                    Toast.show(result.message || 'Failed to save desk.', 'error');
                }
            }).catch(error => {
                Toast.show('Network error.', 'error');
            });
        });
    }

    if (deactivateBtn) {
        deactivateBtn.addEventListener('click', () => {
            Modal.confirm('Deactivate Desk', 'Are you sure? This desk will be hidden from the map and all its active future bookings will be CANCELLED.', () => {
                const params = new URLSearchParams();
                params.append('id', inputId.value);

                fetch('/deactivateDesk', {
                    method: 'POST',
                    body: params
                }).then(async response => {
                    if (response.ok) {
                        Toast.show('Desk deactivated successfully!');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        const result = await response.json();
                        Toast.show(result.message || 'Failed to deactivate desk.', 'error');
                    }
                }).catch(error => {
                    Toast.show('Network error.', 'error');
                });
            });
        });
    }

    if (reactivateBtn) {
        reactivateBtn.addEventListener('click', () => {
            const params = new URLSearchParams();
            params.append('id', inputId.value);

            fetch('/reactivateDesk', {
                method: 'POST',
                body: params
            }).then(async response => {
                if (response.ok) {
                    Toast.show('Desk reactivated successfully!');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    const result = await response.json();
                    Toast.show(result.message || 'Failed to reactivate desk.', 'error');
                }
            }).catch(error => {
                Toast.show('Network error.', 'error');
            });
        });
    }

    if (hardDeleteBtn) {
        hardDeleteBtn.addEventListener('click', () => {
            Modal.confirm('Delete Desk Permanently', 'Are you absolutely sure? This desk has no booking history and will be PERMANENTLY deleted from the database. This action cannot be undone.', () => {
                const params = new URLSearchParams();
                params.append('id', inputId.value);

                fetch('/hardDeleteDesk', {
                    method: 'POST',
                    body: params
                }).then(async response => {
                    if (response.ok) {
                        Toast.show('Desk deleted permanently!');
                        setTimeout(() => window.location.reload(), 1000);
                    } else {
                        const result = await response.json();
                        Toast.show(result.message || 'Failed to delete desk.', 'error');
                    }
                }).catch(error => {
                    Toast.show('Network error.', 'error');
                });
            });
        });
    }

    if (closeBtn) closeBtn.addEventListener('click', closePanel);
    if (backdrop) backdrop.addEventListener('click', closePanel);
});
