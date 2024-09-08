document.addEventListener('DOMContentLoaded', function () {
    const useGPSButton = document.getElementById('use-gps');
    const currentLocationInput = document.getElementById('current-location');

    // Function to get user's current location
    function getLocation() {
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(async (position) => {
                const lat = position.coords.latitude;
                const lon = position.coords.longitude;

                // Fetch nearest station using Ruter's reverse geocoding API
                const apiUrl = `https://api.entur.io/geocoder/v1/reverse?point.lat=${lat}&point.lon=${lon}&size=1`;
                const response = await fetch(apiUrl);
                const data = await response.json();

                if (data.features && data.features.length > 0) {
                    const stopName = data.features[0].properties.name;
                    currentLocationInput.value = stopName; // Auto-fill the input with the stop name
                } else {
                    alert('No nearby stops found.');
                }
            }, (error) => {
                alert('Unable to retrieve your location.');
            });
        } else {
            alert('Geolocation is not supported by this browser.');
        }
    }

    // Attach event listener to GPS button
    useGPSButton.addEventListener('click', getLocation);
});
