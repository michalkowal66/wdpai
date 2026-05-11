function checkInBooking(id) {
    const data = new URLSearchParams();
    data.append('id', id);
    
    fetch('/checkInBooking', {
        method: 'POST',
        body: data
    }).then(async response => {
        if (response.ok) {
            Toast.show('Checked in successfully!');
            setTimeout(() => window.location.reload(), 1000);
        } else {
            const result = await response.json();
            Toast.show(result.message || 'Failed to check in.', 'error');
        }
    });
}

function cancelBooking(id) {
    Modal.confirm('Cancel Reservation', 'Are you sure you want to cancel this reservation?', () => {
        const data = new URLSearchParams();
        data.append('id', id);
        
        fetch('/cancelBooking', {
            method: 'POST',
            body: data
        }).then(async response => {
            if (response.ok) {
                Toast.show('Booking cancelled.');
                setTimeout(() => window.location.reload(), 1000);
            } else {
                const result = await response.json();
                Toast.show(result.message || 'Failed to cancel booking.', 'error');
            }
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const loadMoreBtn = document.getElementById('load-more-btn');
    const loadMoreContainer = document.getElementById('load-more-container');
    const bookingsList = document.getElementById('bookings-list-container');

    if (loadMoreBtn) {
        loadMoreBtn.addEventListener('click', async () => {
            const offset = parseInt(loadMoreBtn.getAttribute('data-offset'));
            const tab = loadMoreBtn.getAttribute('data-tab');
            const limit = 5;

            loadMoreBtn.disabled = true;
            loadMoreBtn.innerHTML = `Loading... <span class="material-symbols-outlined" style="animation: spin 1s linear infinite;">sync</span>`;

            try {
                const response = await fetch(`/getBookingsData?tab=${tab}&limit=${limit}&offset=${offset}`);
                if (!response.ok) throw new Error('Network response was not ok');
                
                const data = await response.json();
                
                // Render new cards
                data.bookings.forEach(b => {
                    const isPast = b.booking_date < data.today;
                    
                    let badgeClass = '';
                    let badgeText = '';
                    let statusDotClass = '';
                    let statusText = '';
                    
                    if (b.status === 'CANCELLED' || b.status === 'NO_SHOW') {
                        badgeClass = 'booking-card__badge--neutral';
                        const friendlyText = b.status === 'CANCELLED' ? 'Cancelled' : 'No Show';
                        badgeText = friendlyText;
                        statusDotClass = 'status-dot--neutral';
                        statusText = friendlyText;
                    } else if (b.status === 'CHECKED_IN') {
                        badgeClass = isPast ? 'booking-card__badge--neutral' : 'booking-card__badge--success';
                        badgeText = isPast ? 'Completed' : 'Checked In';
                        statusDotClass = isPast ? 'status-dot--neutral' : 'status-dot--success';
                        statusText = isPast ? 'Completed' : 'Checked In';
                    } else { // ACTIVE
                        badgeClass = 'booking-card__badge--info';
                        badgeText = 'Upcoming';
                        statusDotClass = 'status-dot--warning';
                        statusText = 'Pending Check-in';
                    }

                    const article = document.createElement('article');
                    article.className = `booking-card ${isPast ? 'booking-card--past' : ''}`;
                    
                    // Format date
                    const dateObj = new Date(b.booking_date);
                    const formattedDate = dateObj.toLocaleDateString('en-US', { weekday: 'long', month: 'short', day: 'numeric', year: 'numeric' });

                    let actionsHtml = '';
                    if (!isPast && b.status === 'ACTIVE') {
                        if (b.booking_date === data.today) {
                            actionsHtml += `
                                <button class="btn btn--primary btn--small" onclick="checkInBooking(${b.id})">
                                    <span class="material-symbols-outlined desktop-only">check_circle</span> Check In
                                </button>
                            `;
                        }
                        const cancelBtnClass = b.booking_date === data.today ? 'btn--small' : 'btn--full';
                        actionsHtml += `
                            <button class="btn btn--secondary btn--danger ${cancelBtnClass}" onclick="cancelBooking(${b.id})">
                                <span class="material-symbols-outlined desktop-only">cancel</span> Cancel
                            </button>
                        `;
                    } else if (isPast || b.status === 'CHECKED_IN' || b.status === 'CANCELLED' || b.status === 'NO_SHOW') {
                        const nextDay = new Date(data.today);
                        nextDay.setDate(nextDay.getDate() + 1);
                        const nextDayStr = nextDay.toISOString().split('T')[0];
                        actionsHtml += `
                            <a href="/map?floor=${b.floor_level}&date=${nextDayStr}" class="btn btn--link">
                                Book Again <span class="material-symbols-outlined">arrow_forward</span>
                            </a>
                        `;
                    }

                    article.innerHTML = `
                        <figure class="booking-card__image-wrapper">
                            <img src="${b.floor_map_url}" alt="${b.floor_name}" class="booking-card__image" style="object-position: top center;">
                            <span class="booking-card__badge ${badgeClass} desktop-only">${badgeText}</span>
                        </figure>
                        <div class="booking-card__content">
                            <header class="booking-card__header">
                                <div class="booking-card__date" ${isPast ? 'style="color: var(--color-text-muted);"' : ''}>
                                    <span class="material-symbols-outlined">calendar_month</span>
                                    <span>${formattedDate}</span>
                                </div>
                                <h3 class="booking-card__title">Desk ${b.desk_identifier}</h3>
                                <div class="booking-card__details">
                                    <span class="feature-tag"><span class="material-symbols-outlined">schedule</span> All Day</span>
                                    <span class="feature-tag"><span class="material-symbols-outlined">location_on</span> ${b.floor_name}</span>
                                </div>
                            </header>
                            <footer class="booking-card__footer">
                                <div class="booking-card__status">
                                    <span class="status-dot ${statusDotClass}"></span>
                                    <span class="status-text">${statusText}</span>
                                </div>
                                <div class="booking-card__actions" ${isPast ? 'style="margin-left: auto;"' : ''}>
                                    ${actionsHtml}
                                </div>
                            </footer>
                        </div>
                    `;
                    
                    // Append to the list
                    bookingsList.appendChild(article);
                });

                // Update button state
                loadMoreBtn.setAttribute('data-offset', offset + limit);
                
                if (data.remainingCount > 0) {
                    loadMoreBtn.disabled = false;
                    loadMoreBtn.innerHTML = `Load more history (<span id="remaining-count">${data.remainingCount}</span> remaining) <span class="material-symbols-outlined">expand_more</span>`;
                } else {
                    loadMoreContainer.style.display = 'none';
                }

            } catch (error) {
                console.error('Error fetching bookings:', error);
                Toast.show('Failed to load more bookings.', 'error');
                loadMoreBtn.disabled = false;
                const prevRemaining = loadMoreBtn.getAttribute('data-remaining') || '';
                loadMoreBtn.innerHTML = `Load more history (<span id="remaining-count">${prevRemaining}</span> remaining) <span class="material-symbols-outlined">expand_more</span>`;
            }
        });
    }
});