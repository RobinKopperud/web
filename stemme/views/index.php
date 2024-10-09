<?php
include_once '../../includes/db.php';
include_once 'header.php';

// Fetch the most upvoted destination
$topDestinationQuery = "SELECT * FROM destinations ORDER BY votes DESC LIMIT 1";
$topDestinationResult = $conn->query($topDestinationQuery);
$topDestination = $topDestinationResult->fetch_assoc();

// Fetch all destinations
$destinationsQuery = "SELECT * FROM destinations ORDER BY name ASC";
$destinationsResult = $conn->query($destinationsQuery);
?>

<main>
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

  <section id="destinations">
    <h2>All Destinations</h2>
    <?php while ($destination = $destinationsResult->fetch_assoc()) { ?>
      <div class="destination-card">
        <h3><?php echo $destination['name']; ?></h3>
        <p>Votes: <?php echo $destination['votes']; ?></p>
        <a href="destination.php?id=<?php echo $destination['id']; ?>">View Details</a>
        <form method="post" action="vote.php">
          <input type="hidden" name="destination_id" value="<?php echo $destination['id']; ?>">
          <button type="submit">Vote</button>
        </form>
      </div>
    <?php } ?>
  </section>

  <section id="suggestion-form">
    <h2>Suggest a Destination</h2>
    <form action="suggest.php" method="post">
      <input type="text" name="destination_name" placeholder="Destination Name" required>
      <button type="submit">Submit</button>
    </form>
  </section>
</main>

<?php include_once 'footer.php'; ?>
