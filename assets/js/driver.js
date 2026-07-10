/* 
============================================================
RideEase – Driver Dashboard Polling & Status Management
============================================================
*/

let requestPollInterval = null;

// Toggle availability status API Call
function toggleAvailability(checkbox) {
    const status = checkbox.checked ? 1 : 0;
    const formData = new FormData();
    formData.append('is_available', status);

    fetch('../api/toggle_availability.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            const statusLabel = document.getElementById('availability-status-label');
            if (statusLabel) {
                statusLabel.innerText = status ? 'Online & Available' : 'Offline';
                statusLabel.className = status ? 'badge badge-completed' : 'badge badge-cancelled';
            }
            
            if (status) {
                startPollingForRides();
            } else {
                stopPollingForRides();
                const container = document.getElementById('incoming-ride-container');
                if (container) container.innerHTML = '<p class='text-muted'>Go online to receive ride bookings.</p>';
            }
        } else {
            showToast(data.message, 'danger');
            checkbox.checked = !checkbox.checked; // Revert
        }
    })
    .catch(err => {
        console.error("Availability toggle error:", err);
        checkbox.checked = !checkbox.checked;
    });
}

// Start polling for new matching ride request (assigned state)
function startPollingForRides() {
    if (requestPollInterval) clearInterval(requestPollInterval);
    
    // Poll every 4 seconds
    requestPollInterval = setInterval(checkIncomingRides, 4000);
    checkIncomingRides(); // Initial check
}

function stopPollingForRides() {
    if (requestPollInterval) {
        clearInterval(requestPollInterval);
        requestPollInterval = null;
    }
}

// Fetch ride alerts
function checkIncomingRides() {
    fetch('../api/accept_ride.php?check=1')
    .then(res => res.json())
    .then(data => {
        const container = document.getElementById('incoming-ride-container');
        if (!container) return;

        if (data.success && data.ride) {
            // Found a pending matching ride
            const ride = data.ride;
            container.innerHTML = `
                <div class="card" style="border: 2px solid var(--accent-cyan); animation: slideDown 0.4s ease;">
                    <h3 class="gradient-text">New Ride Request!</h3>
                    <p><strong>Passenger:</strong> ${ride.passenger_name}</p>
                    <p><strong>Pickup:</strong> ${ride.pickup_location}</p>
                    <p><strong>Dropoff:</strong> ${ride.destination}</p>
                    <p><strong>Distance:</strong> ${ride.distance_km} km</p>
                    <p><strong>Estimated Earning:</strong> ৳ ${(parseFloat(ride.final_fare) * 0.8).toFixed(2)} (80%)</p>
                    
                    <div style="margin-top: 1rem; display: flex; gap: 10px;">
                        <button class="btn btn-success" onclick="respondToRide(${ride.id}, 'accept')" style="flex:1;">Accept Ride</button>
                        <button class="btn btn-danger" onclick="respondToRide(${ride.id}, 'reject')" style="flex:1;">Reject</button>
                    </div>
                </div>
            `;
            // Trigger browser sound notification if possible
            playRequestSound();
        } else {
            container.innerHTML = `
                <div class="text-center" style="padding: 2rem;">
                    <div class="spinner" style="margin: 0 auto 1rem;"></div>
                    <p class="text-muted">Listening for incoming booking requests...</p>
                </div>
            `;
        }
    })
    .catch(err => console.error("Error polling incoming rides:", err));
}

// Accept or Reject Request
function respondToRide(rideId, action) {
    const formData = new FormData();
    formData.append('ride_id', rideId);
    
    const url = action === 'accept' ? '../api/accept_ride.php' : '../api/reject_ride.php';

    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            showToast(data.message, 'success');
            // Refresh dashboard
            location.reload();
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(err => console.error("Error responding to ride request:", err));
}

// Play notification sound
function playRequestSound() {
    try {
        const audioCtx = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioCtx.createOscillator();
        const gainNode = audioCtx.createGain();
        
        oscillator.type = 'sine';
        oscillator.frequency.setValueAtTime(440, audioCtx.currentTime); // A4
        oscillator.frequency.exponentialRampToValueAtTime(880, audioCtx.currentTime + 0.3);
        
        gainNode.gain.setValueAtTime(0.1, audioCtx.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioCtx.currentTime + 0.3);
        
        oscillator.connect(gainNode);
        gainNode.connect(audioCtx.destination);
        
        oscillator.start();
        oscillator.stop(audioCtx.currentTime + 0.3);
    } catch (e) {
        // Fallback silently if blocked
    }
}
