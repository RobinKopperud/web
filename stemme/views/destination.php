<?php
define('APP_INIT', true);
include_once '../../../includes/db.php';  // Database connection
include_once '../includes/header.php';

$destinationId = $_GET['id'];

// Fetch destination details
$query = "SELECT * FROM destinations WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $destinationId);
$stmt->execute();
$result = $stmt->get_result();
$destination = $result->fetch_assoc();
$stmt->close();

// Fetch related images and links
$imagesQuery = "SELECT * FROM images WHERE destination_id = $destinationId";
$imagesResult = $conn->query($imagesQuery);

$linksQuery = "SELECT * FROM links WHERE destination_id = $destinationId";
$linksResult = $conn->query($linksQuery);
?>

<main>
  <h1><?php echo $destination['name']; ?></h1>

  <!-- Display Uploaded Images -->
  <section id="images">
    <h2>Uploaded Screenshots</h2>
    <?php while ($image = $imagesResult->fetch_assoc()) { ?>
      <img src="../uploads/<?php echo $image['file_name']; ?>" alt="Screenshot">
    <?php } ?>
  </section>

  <!-- Display Links -->
  <section id="links">
    <h2>Links to Hotels/Flights</h2>
    <ul>
      <?php while ($link = $linksResult->fetch_assoc()) { ?>
        <li><a href="<?php echo $link['url']; ?>" target="_blank"><?php echo $link['description']; ?></a></li>
      <?php } ?>
    </ul>
  </section>

  <!-- Form to Upload a New Screenshot -->
  <section id="upload-image">
    <h2>Upload a Screenshot</h2>
    <form action="upload_image.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="destination_id" value="<?php echo $destinationId; ?>">
      <input type="file" name="image" required>
      <button type="submit">Upload</button>
    </form>
  </section>

  <!-- Form to Add a New Link -->
  <section id="add-link">
    <h2>Add a Link</h2>
    <form action="add_link.php" method="post">
      <input type="hidden" name="destination_id" value="<?php echo $destinationId; ?>">
      <input type="url" name="url" placeholder="URL" required>
      <input type="text" name="description" placeholder="Description" required>
      <button type="submit">Add Link</button>
    </form>
  </section>
</main>

<?php include_once '../includes/footer.php'; ?>
