<?php
include_once '../includes/header.php'; // Include header file
?>

<main>
  <!-- Section to display the most upvoted destination -->
  <section id="top-destination">
    <h2>Most Upvoted Destination</h2>
    <div id="top-destination-content">
      <!-- The most upvoted destination will be loaded here by JavaScript -->
    </div>
  </section>

  <!-- Section to display all destinations -->
  <section id="destinations">
    <h2>All Destinations</h2>
    <div id="destinations-content">
      <!-- The list of destinations will be dynamically loaded here by JavaScript -->
    </div>
  </section>

  <!-- Suggestion form -->
  <section id="suggestion-form">
    <h2>Suggest a Destination</h2>
    <form id="suggest-form">
      <input type="text" id="suggestion-name" placeholder="Enter destination name" required>
      <button type="submit">Submit</button>
    </form>
  </section>
</main>

<script src="../js/main.js"></script> <!-- Load your main JavaScript file -->
<?php include_once '../includes/footer.php'; ?>
