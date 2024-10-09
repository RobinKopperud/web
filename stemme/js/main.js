document.addEventListener("DOMContentLoaded", () => {
    loadDestinations();
  
    // Load all destinations and the most upvoted one
    function loadDestinations() {
      fetch('fetch_destinations.php')
        .then(response => response.json())
        .then(data => {
          displayTopDestination(data.topDestination);
          displayDestinations(data.destinations);
        })
        .catch(error => console.error('Error:', error));
    }
  
    // Display the most upvoted destination
    function displayTopDestination(destination) {
      const topDestinationContent = document.getElementById('top-destination-content');
      if (destination) {
        topDestinationContent.innerHTML = `
          <div class="destination-card">
            <h3>${destination.name}</h3>
            <p>Votes: <span id="votes-${destination.id}">${destination.votes}</span></p>
            <button class="vote-btn" data-destination-id="${destination.id}">Vote</button>
            <a href="destination.php?id=${destination.id}">View Details</a>
          </div>
        `;
      } else {
        topDestinationContent.innerHTML = '<p>No destinations yet.</p>';
      }
      attachVoteEventListeners();
    }
  
    // Display the list of destinations
    function displayDestinations(destinations) {
      const destinationsContent = document.getElementById('destinations-content');
      destinationsContent.innerHTML = '';
      destinations.forEach(destination => {
        const card = document.createElement('div');
        card.classList.add('destination-card');
        card.innerHTML = `
          <h3>${destination.name}</h3>
          <p>Votes: <span id="votes-${destination.id}">${destination.votes}</span></p>
          <button class="vote-btn" data-destination-id="${destination.id}">Vote</button>
          <a href="destination.php?id=${destination.id}">View Details</a>
        `;
        destinationsContent.appendChild(card);
      });
      attachVoteEventListeners();
    }
  
    // Attach event listeners to vote buttons
    function attachVoteEventListeners() {
      document.querySelectorAll('.vote-btn').forEach(button => {
        button.addEventListener('click', (e) => {
          e.preventDefault();
          const destinationId = e.target.dataset.destinationId;
          voteForDestination(destinationId);
        });
      });
    }
  
    // Function to handle voting
    function voteForDestination(destinationId) {
      fetch('vote.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ destination_id: destinationId })
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update the vote count on the page
          document.querySelector(`#votes-${destinationId}`).innerText = data.newVoteCount;
        } else {
          alert('Error voting for destination.');
        }
      })
      .catch(error => console.error('Error:', error));
    }
  
    // Handle submission of the suggestion form
    document.getElementById('suggest-form').addEventListener('submit', (e) => {
      e.preventDefault();
      const destinationName = document.getElementById('suggestion-name').value;
      const formData = new FormData();
      formData.append('destination_name', destinationName);
  
      fetch('suggest.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Reload destinations after suggestion
          loadDestinations();
          document.getElementById('suggest-form').reset();
        } else {
          alert('Error submitting suggestion.');
        }
      })
      .catch(error => console.error('Error:', error));
    });
  });
  