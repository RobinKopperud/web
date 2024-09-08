const apiKey = 'your-entur-api-key'; // If Entur API requires a key, add it here

// Function to fetch the T-bane stop ID from the Ruter Place Lookup API
async function fetchStopID(stopName) {
    const url = `https://api.entur.io/geocoder/v1/autocomplete?text=${encodeURIComponent(stopName)}&size=1`;

    try {
        const response = await fetch(url, {
            headers: {
                'Content-Type': 'application/json',
                'ET-Client-Name': 'your-client-name' // Ruter or Entur might require a client name header for requests
            }
        });

        const data = await response.json();
        if (data.features && data.features.length > 0) {
            const stopID = data.features[0].properties.id; // The ID of the stop
            return stopID;
        } else {
            alert('No stop found with the provided name.');
            return null;
        }
    } catch (error) {
        console.error('Error fetching stop ID:', error);
        alert('Error fetching stop ID. Please try again later.');
        return null;
    }
}

// Function to handle form submission
async function handleFormSubmission(event) {
    event.preventDefault(); // Prevent default form submission

    const currentLocationName = document.getElementById('current-location').value;
    const destinationName = document.getElementById('destination').value;

    if (!currentLocationName || !destinationName) {
        alert('Please fill in both the current location and destination.');
        return;
    }

    // Fetch stop IDs for both the current location and destination
    const currentLocationID = await fetchStopID(currentLocationName);
    const destinationID = await fetchStopID(destinationName);

    if (currentLocationID && destinationID) {
        // Redirect to the live.php page with the stop IDs as query parameters
        const liveURL = `live.php?current_location_id=${currentLocationID}&destination_id=${destinationID}`;
        window.location.href = liveURL;
    }
}

// Attach the form submission event listener
document.getElementById('locationForm').addEventListener('submit', handleFormSubmission);
