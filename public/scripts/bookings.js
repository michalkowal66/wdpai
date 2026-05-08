function checkInBooking(id) {
    const data = new URLSearchParams();
    data.append('id', id);
    
    fetch('/checkInBooking', {
        method: 'POST',
        body: data
    }).then(async response => {
        if (response.ok) {
            window.location.reload();
        } else {
            const result = await response.json();
            alert(result.message || 'Failed to check in.');
        }
    });
}

function cancelBooking(id) {
    if (!confirm('Are you sure you want to cancel this reservation?')) return;
    
    const data = new URLSearchParams();
    data.append('id', id);
    
    fetch('/cancelBooking', {
        method: 'POST',
        body: data
    }).then(async response => {
        if (response.ok) {
            window.location.reload();
        } else {
            const result = await response.json();
            alert(result.message || 'Failed to cancel booking.');
        }
    });
}
