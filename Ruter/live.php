<?php
// Get the stop IDs from the query parameters
$currentLocationID = htmlspecialchars($_GET['current_location_id']);
$destinationID = htmlspecialchars($_GET['destination_id']);

// Validate the stop IDs
if (!$currentLocationID || !$destinationID) {
    echo "<p>Invalid stop information provided. Please go back and try again.</p>";
    exit;
}

// Now you can use the stop IDs to fetch live data from Ruter's API
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Departures</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js" defer></script>
</head>
<body>
    <div class="container">
        <h1>Live Departures</h1>
        <div id="departure-container">
            <p>Loading live departures...</p>
        </div>
    </div>

    <script>
    const currentLocationID = <?php echo $currentLocationID; ?>;
    const apiUrl = `https://reisapi.ruter.no/StopVisit/GetDepartures/${currentLocationID}`;

    async function fetchDepartures() {
        try {
            const response = await fetch(apiUrl);
            const data = await response.json();
            displayDepartures(data);
        } catch (error) {
            console.error("Error fetching departure times:", error);
            document.getElementById('departure-container').innerHTML = `<p>Unable to fetch departure times. Please try again later.</p>`;
        }
    }

    function displayDepartures(data) {
        const container = document.getElementById('departure-container');
        container.innerHTML = ''; // Clear old data
        if (data.length === 0) {
            container.innerHTML = `<p>No departures available.</p>`;
            return;
        }

        data.forEach(departure => {
            const line = departure.MonitoredVehicleJourney.LineRef;
            const destination = departure.MonitoredVehicleJourney.DestinationName;
            const time = new Date(departure.MonitoredVehicleJourney.MonitoredCall.ExpectedDepartureTime).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

            const departureElement = document.createElement('div');
            departureElement.classList.add('departure');
            departureElement.innerHTML = `<strong>Line ${line}</strong> to ${destination} departs at ${time}`;
            container.appendChild(departureElement);
        });
    }

    fetchDepartures();
    setInterval(fetchDepartures, 10000); // Refresh every 10 seconds
    </script>
</body>
</html>
