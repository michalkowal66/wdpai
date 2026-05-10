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
