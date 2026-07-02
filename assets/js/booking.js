/* 
============================================================
RideEase – Booking Interface & Map Integration Scripts
============================================================
*/

let bookingMap = null;
let pickupMarker = null;
let destinationMarker = null;

// Initialize Leaflet Map
function initBookingMap(elementId = 'map') {
    const mapElement = document.getElementById(elementId);
    if (!mapElement) return;

    // Dhaka Coordinates default
    const defaultLat = 23.8103;
    const defaultLng = 90.4125;

    bookingMap = L.map(elementId).setView([defaultLat, defaultLng], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap contributors'
    }).addTo(bookingMap);

    // Click events to place markers
    bookingMap.on('click', function(e) {
        if (!pickupMarker) {
            pickupMarker = L.marker(e.latlng, { draggable: true }).addTo(bookingMap);
            pickupMarker.bindPopup("Pickup Point").openPopup();
            document.getElementById('pickup_coords').value = `${e.latlng.lat.toFixed(6)},${e.latlng.lng.toFixed(6)}`;
            pickupMarker.on('dragend', updateDirections);
        } else if (!destinationMarker) {
            destinationMarker = L.marker(e.latlng, { draggable: true }).addTo(bookingMap);
            destinationMarker.bindPopup("Destination Point").openPopup();
            document.getElementById('dest_coords').value = `${e.latlng.lat.toFixed(6)},${e.latlng.lng.toFixed(6)}`;
            destinationMarker.on('dragend', updateDirections);
            calculateRouteDistance();
        } else {
            // Reset markers on 3rd click
            bookingMap.removeLayer(pickupMarker);
            bookingMap.removeLayer(destinationMarker);
            pickupMarker = null;
            destinationMarker = null;
            document.getElementById('pickup_coords').value = '';
            document.getElementById('dest_coords').value = '';
            document.getElementById('fare_estimate_display').innerText = '৳ 0.00';
            document.getElementById('distance_display').innerText = '0.00 km';
        }
    });
}

function updateDirections() {
    if (pickupMarker) {
        const pLatlng = pickupMarker.getLatLng();
        document.getElementById('pickup_coords').value = `${pLatlng.lat.toFixed(6)},${pLatlng.lng.toFixed(6)}`;
    }
    if (destinationMarker) {
        const dLatlng = destinationMarker.getLatLng();
        document.getElementById('dest_coords').value = `${dLatlng.lat.toFixed(6)},${dLatlng.lng.toFixed(6)}`;
    }
    if (pickupMarker && destinationMarker) {
        calculateRouteDistance();
    }
}

// Calculate straight distance as simulation
function calculateRouteDistance() {
    if (!pickupMarker || !destinationMarker) return;

    const pCoords = pickupMarker.getLatLng();
    const dCoords = destinationMarker.getLatLng();

    // Haversine formula calculation for KM distance
    const R = 6371; // Earth radius
    const dLat = (dCoords.lat - pCoords.lat) * Math.PI / 180;
    const dLon = (dCoords.lng - pCoords.lng) * Math.PI / 180;
    const a = 
        Math.sin(dLat/2) * Math.sin(dLat/2) +
        Math.cos(pCoords.lat * Math.PI / 180) * Math.cos(dCoords.lat * Math.PI / 180) * 
        Math.sin(dLon/2) * Math.sin(dLon/2);
    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    let distance = R * c;

    // Minimum simulation distance if click same spot
    if (distance < 0.5) distance = 1.2; 

    document.getElementById('distance_km').value = distance.toFixed(2);
    document.getElementById('distance_display').innerText = `${distance.toFixed(2)} km`;

    estimateFare(distance);
}

// Estimate Fare Endpoint Call
function estimateFare(distanceKm) {
    const formData = new FormData();
    formData.append('distance_km', distanceKm);

    fetch('../api/fare_estimate.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('estimated_fare_val').value = data.estimated_fare;
            document.getElementById('fare_estimate_display').innerText = `৳ ${data.estimated_fare}`;
            document.getElementById('peak_multiplier').value = data.peak_multiplier;
            if (parseFloat(data.peak_multiplier) > 1.0) {
                showToast(`Peak Pricing Active! x${data.peak_multiplier}`, 'warning');
            }
        } else {
            showToast(data.message, 'danger');
        }
    })
    .catch(err => {
        console.error("Fare estimate fetch error:", err);
    });
}

// Apply Discount Coupon Code
function applyCouponCode() {
    const code = document.getElementById('coupon_code').value.trim();
    const fare = document.getElementById('estimated_fare_val').value;

    if (!code) {
        showToast("Enter a coupon code first", "warning");
        return;
    }

    const formData = new FormData();
    formData.append('coupon_code', code);
    formData.append('estimated_fare', fare);

    fetch('../api/apply_coupon.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            document.getElementById('discount_amount').value = data.discount;
            document.getElementById('coupon_id').value = data.coupon_id;
            
            const newFare = Math.max(0, parseFloat(fare) - parseFloat(data.discount));
            document.getElementById('fare_estimate_display').innerText = `৳ ${newFare.toFixed(2)}`;
            showToast(`Coupon applied! Saved ৳ ${data.discount}`, 'success');
        } else {
            showToast(data.message, 'danger');
        }
    });
}

// SOS Trigger Button action
function triggerSOS(rideId) {
    if (!confirm("Are you sure you want to trigger SOS? This will alert emergency responders and the Admin dashboard!")) {
        return;
    }

    const formData = new FormData();
    formData.append('ride_id', rideId);
    formData.append('message', 'Emergency SOS button clicked by passenger!');

    fetch('../api/sos_alert.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("⚠️ SOS ALERT TRIGGERED SUCCESSFUL. EMERGENCY CONTACTS NOTIFIED.");
        } else {
            alert("Failed to send SOS: " + data.message);
        }
    });
}
