document.addEventListener('DOMContentLoaded', () => {

    // --- Pagination Helper ---
    const createPaginationUI = (container, currentPage, totalPages, onPageChange) => {
        if (totalPages <= 1) return;

        const prevBtn = document.createElement('button');
        prevBtn.className = 'btn btn--secondary btn--small';
        prevBtn.innerHTML = '<span class="material-symbols-outlined">chevron_left</span>';
        if (currentPage <= 1) {
            prevBtn.disabled = true;
            prevBtn.style.opacity = '0.5';
            prevBtn.style.pointerEvents = 'none';
        }
        prevBtn.addEventListener('click', () => onPageChange(-1));

        const nextBtn = document.createElement('button');
        nextBtn.className = 'btn btn--secondary btn--small';
        nextBtn.innerHTML = '<span class="material-symbols-outlined">chevron_right</span>';
        if (currentPage >= totalPages) {
            nextBtn.disabled = true;
            nextBtn.style.opacity = '0.5';
            nextBtn.style.pointerEvents = 'none';
        }
        nextBtn.addEventListener('click', () => onPageChange(1));

        const infoText = document.createElement('span');
        infoText.style.margin = '0 1rem';
        infoText.style.fontSize = '0.875rem';
        infoText.style.color = 'var(--color-text-muted)';
        infoText.textContent = `Page ${currentPage} of ${totalPages}`;

        container.appendChild(prevBtn);
        container.appendChild(infoText);
        container.appendChild(nextBtn);
    };

    // --- Features Management & Pagination ---
    const featuresContainer = document.getElementById('features-list-container');
    const featuresPagination = document.getElementById('features-pagination');
    let currentFeaturePage = 1;
    const featuresPerPage = 5;
    let featuresData = typeof INITIAL_FEATURES !== 'undefined' ? INITIAL_FEATURES : [];

    const renderFeatures = () => {
        if (!featuresContainer || !featuresPagination) return;
        featuresContainer.innerHTML = '';
        featuresPagination.innerHTML = '';

        if (featuresData.length === 0) {
            featuresContainer.innerHTML = '<p style="color: var(--color-text-muted);">No features defined.</p>';
            return;
        }

        const totalPages = Math.ceil(featuresData.length / featuresPerPage);
        if (currentFeaturePage > totalPages && totalPages > 0) currentFeaturePage = totalPages;

        const startIdx = (currentFeaturePage - 1) * featuresPerPage;
        const pageData = featuresData.slice(startIdx, startIdx + featuresPerPage);

        pageData.forEach(f => {
            const item = document.createElement('div');
            item.className = 'feature-item';
            item.innerHTML = `
                <div class="feature-item__info">
                    <span class="material-symbols-outlined" style="color: var(--color-primary);">${f.icon_name}</span>
                    <span>${f.name}</span>
                </div>
                <button class="btn-icon text-error js-delete-feature" data-id="${f.id}" data-name="${f.name}">
                    <span class="material-symbols-outlined">delete</span>
                </button>
            `;
            featuresContainer.appendChild(item);
        });

        createPaginationUI(featuresPagination, currentFeaturePage, totalPages, (dir) => {
            currentFeaturePage += dir;
            renderFeatures();
        });

        attachDeleteListeners();
    };

    // --- Floors Pagination ---
    const floorsContainer = document.getElementById('floors-list-container');
    const floorsPagination = document.getElementById('floors-pagination');
    let currentFloorPage = 1;
    const floorsPerPage = 3;
    let floorsData = typeof INITIAL_FLOORS !== 'undefined' ? INITIAL_FLOORS : [];

    const renderFloors = () => {
        if (!floorsContainer || !floorsPagination) return;
        floorsContainer.innerHTML = '';
        floorsPagination.innerHTML = '';

        if (floorsData.length === 0) {
            floorsContainer.innerHTML = '<p style="color: var(--color-text-muted);">No floors defined.</p>';
            return;
        }

        const totalPages = Math.ceil(floorsData.length / floorsPerPage);
        if (currentFloorPage > totalPages && totalPages > 0) currentFloorPage = totalPages;

        const startIdx = (currentFloorPage - 1) * floorsPerPage;
        const pageData = floorsData.slice(startIdx, startIdx + floorsPerPage);

        pageData.forEach(f => {
            const item = document.createElement('div');
            item.className = 'feature-item';
            item.innerHTML = `
                <div class="feature-item__info">
                    <span class="badge badge--info">Level ${f.level}</span>
                    <span class="font-bold">${f.name}</span>
                </div>
                <a href="${f.map_image_url}" target="_blank" class="btn-icon" aria-label="View Map">
                    <span class="material-symbols-outlined">visibility</span>
                </a>
            `;
            floorsContainer.appendChild(item);
        });

        createPaginationUI(floorsPagination, currentFloorPage, totalPages, (dir) => {
            currentFloorPage += dir;
            renderFloors();
        });
    };

    // --- Delete Feature Logic ---
    const attachDeleteListeners = () => {
        document.querySelectorAll('.js-delete-feature').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.getAttribute('data-id');
                const name = btn.getAttribute('data-name');

                Modal.confirm('Delete Feature', `Are you sure you want to delete the feature "${name}"?`, () => {
                    const data = new URLSearchParams();
                    data.append('id', id);

                    fetch('/deleteFeature', {
                        method: 'POST',
                        body: data
                    }).then(async response => {
                        if (response.ok) {
                            Toast.show('Feature deleted successfully!');
                            featuresData = featuresData.filter(f => f.id != id);
                            renderFeatures();
                        } else {
                            const result = await response.json();
                            Toast.show(result.message || 'Failed to delete feature.', 'error');
                        }
                    }).catch(error => {
                        Toast.show('Network error occurred.', 'error');
                    });
                });
            });
        });
    };

    // --- Add Feature Logic ---
    const addFeatureForm = document.getElementById('add-feature-form');
    if (addFeatureForm) {
        addFeatureForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const nameInput = document.getElementById('feature-name');
            const iconInput = document.getElementById('feature-icon');

            const data = new URLSearchParams();
            data.append('name', nameInput.value);
            data.append('icon', iconInput.value);

            fetch('/addFeature', {
                method: 'POST',
                body: data
            }).then(async response => {
                if (response.ok) {
                    Toast.show('Feature added successfully!');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    const result = await response.json();
                    Toast.show(result.message || 'Failed to add feature.', 'error');
                }
            }).catch(error => {
                Toast.show('Network error occurred.', 'error');
            });
        });
    }

    const addFloorForm = document.getElementById('add-floor-form');
    if (addFloorForm) {
        addFloorForm.addEventListener('submit', (e) => {
            e.preventDefault();
            const submitBtn = addFloorForm.querySelector('button[type="submit"]');
            const origText = submitBtn.textContent;
            submitBtn.disabled = true;
            submitBtn.textContent = 'Uploading...';

            const formData = new FormData(addFloorForm);

            fetch('/addFloor', {
                method: 'POST',
                body: formData
            }).then(async response => {
                if (response.ok) {
                    Toast.show('Floor map uploaded successfully!');
                    setTimeout(() => window.location.reload(), 1000);
                } else {
                    const result = await response.json();
                    Toast.show(result.message || 'Failed to upload floor map.', 'error');
                    submitBtn.disabled = false;
                    submitBtn.textContent = origText;
                }
            }).catch(error => {
                Toast.show('Network error occurred.', 'error');
                submitBtn.disabled = false;
                submitBtn.textContent = origText;
            });
        });
    }

    // Initial render
    renderFeatures();
    renderFloors();
});
