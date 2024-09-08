const departureContainer = document.getElementById('departure-container');
const useGPSButton = document.getElementById('use-gps');
const setHomeButton = document.getElementById('set-home');
const homeStationInput = document.getElementById('home-station');

// Ruter API URLs
const ruterPlaceLookupURL = 'https://api.entur.io/geocoder/v1/autocomplete?text=';
const ruterDeparturesURL = 'https://reisapi.ruter.no/StopVisit/GetDepartures/';

// Function to fetch live departure times by station ID
async function fetchDepartures(stopID) {
    try {
        const response = await fetch(ruterDeparturesURL + stopID);
        const data = await response.json();
        updateDepartures(data);
    } catch (error) {
        console.error("Error fetching departure times:", error);
        departureContainer.innerHTML = `<p>Unable to fetch departure times. Please try again later.</p>`;
    }
}

// Function to update the DOM with departure times
function updateDepartures(data) {
    departureContainer.innerHTML = ''; // Clear previous data
    if (data.length === 0) {
        departureContainer.innerHTML = `<p>No departures available at this time.</p>`;
        return;
    }

    data.forEach(departure => {
        const line = departure.MonitoredVehicleJourney.LineRef;
        const destination = departure.MonitoredVehicleJourney.DestinationName;
        const departureTime = new Date(departure.MonitoredVehicleJourney.MonitoredCall.ExpectedDepartureTime);
        const formattedTime = departureTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

        const departureElement = document.createElement('div');
        departureElement.classList.add('departure');
        departureElement.innerHTML = `<strong>Line ${line}</strong> to ${destination} departs at ${formattedTime}`;
        departureContainer.appendChild(departureElement);
    });
}

// Function to get the user's current location using the Geolocation API
function getLocation() {
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(async position => {
            const latitude = position.coords.latitude;
            const longitude = position.coords.longitude;
            findNearestStop(latitude, longitude);
        }, error => {
            console.error("Error getting location:", error);
            departureContainer.innerHTML = `<p>Unable to get location. Please try again later.</p>`;
        });
    } else {
        console.error("Geolocation is not supported by this browser.");
    }
}

// Function to find the nearest station using Ruter's API based on GPS coordinates
async function findNearestStop(lat, lon) {
    try {
        const response = await fetch(`https://api.entur.io/geocoder/v1/reverse?point.lat=${lat}&point.lon=${lon}&size=1`);
        const data = await response.json();
        if (data.features && data.features.length > 0) {
            const stopID = data.features[0].properties.id;
            fetchDepartures(stopID);
        } else {
            departureContainer.innerHTML = `<p>No nearby stops found.</p>`;
        }
    } catch (error) {
        console.error("Error fetching nearest stop:", error);
    }
}

// Function to set the home station based on user input
async function setHomeStation() {
    const stationName = homeStationInput.value;
    if (!stationName) return;
    
    try {
        const response = await fetch(ruterPlaceLookupURL + stationName);
        const data = await response.json();
        if (data.features && data.features.length > 0) {
            const stopID = data.features[0].properties.id;
            localStorage.setItem('homeStationID', stopID); // Save to localStorage
            fetchDepartures(stopID); // Fetch departures immediately
        } else {
            departureContainer.innerHTML = `<p>Station not found. Try again.</p>`;
        }
    } catch (error) {
        console.error("Error setting home station:", error);
    }
}

// Check if the user already has a home station set
function checkHomeStation() {
    const homeStationID = localStorage.getItem('homeStationID');
    if (homeStationID) {
        fetchDepartures(homeStationID);
    }
}

// Event Listeners
useGPSButton.addEventListener('click', getLocation);
setHomeButton.addEventListener('click', setHomeStation);

// Check if there's a home station already set when the page loads
checkHomeStation();
