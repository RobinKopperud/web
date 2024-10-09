<?php
include_once '../../../db.php';  // Database connection
include_once '../includes/header.php';    // Include the header

// Fetch the most upvoted destination
$topDestinationQuery = "SELECT * FROM destinations ORDER BY votes DESC LIMIT 1";
$topDestinationResult = $conn->query($topDestinationQuery);
$topDestination = $topDestinationResult->fetch_assoc();

// Fetch all destinations
$destinationsQuery = "SELECT * FROM destinations ORDER BY name ASC";
$destinationsResult = $conn->query($destinationsQuery);

// Check if there’s an error (user already voted)
if (isset($_GET['vote_error']) && $_GET['vote_error'] == 'already_voted') {
    echo "<p style='color: red; text-align: center;'>You have already voted for this destination.</p>";
}
?>

<main>
  <!-- Most Upvoted Destination -->
  <section id="top-destination">
    <h2>Most Upvoted Destination</h2>
    <?php if ($topDestination) { ?>
      <div class="destination-card">
        <h3><?php echo $topDestination['name']; ?></h3>
        <p>Votes: <?php echo $topDestination['votes']; ?></p>
        <a href="destination.php?id=<?php echo $topDestination['id']; ?>">View Details</a>
      </div>
    <?php } else { ?>
      <p>No destinations yet.</p>
    <?php } ?>
  </section>

  <!-- All Destinations -->
  <section id="destinations">
    <h2>All Destinations</h2>
    <?php while ($destination = $destinationsResult->fetch_assoc()) { 
        // Check if the user has voted for this destination using cookies
        $hasVoted = isset($_COOKIE['voted_' . $destination['id']]);
    ?>
      <div class="destination-card">
        <h3><?php echo $destination['name']; ?></h3>
        <p>Votes: <?php echo $destination['votes']; ?></p>
        <a href="destination.php?id=<?php echo $destination['id']; ?>">View Details</a>

        <!-- Disable vote button if the user has already voted -->
        <form action="vote.php" method="post">
          <input type="hidden" name="destination_id" value="<?php echo $destination['id']; ?>">
          <button type="submit" <?php echo $hasVoted ? 'disabled' : ''; ?>>
            <?php echo $hasVoted ? 'Already Voted' : 'Vote'; ?>
          </button>
        </form>
      </div>
    <?php } ?>
  </section>

  <!-- Suggestion Form -->
  <section id="suggestion-form">
    <h2>Suggest a Destination</h2>
    <form action="suggest.php" method="post">
      <input type="text" name="destination_name" placeholder="Destination Name" required>
      <button type="submit">Submit</button>
    </form>
  </section>
</main>

<?php include_once '../includes/footer.php'; ?>
