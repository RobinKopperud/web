<?php
include_once '../../includes/db.php';
include_once 'header.php';

$destinationId = $_GET['id'];

// Fetch destination details
$destinationQuery = "SELECT * FROM destinations WHERE id = $destinationId";
$destinationResult = $conn->query($destinationQuery);
$destination = $destinationResult->fetch_assoc();

// Fetch images
$imagesQuery = "SELECT * FROM images WHERE destination_id = $destinationId";
$imagesResult = $conn->query($imagesQuery);

// Fetch links
$linksQuery = "SELECT * FROM links WHERE destination_id = $destinationId";
$linksResult = $conn->query($linksQuery);
?>

<main>
  <h1><?php echo $destination['name']; ?></h1>

  <section id="details">
    <h2>Uploaded Screenshots</h2>
    <div id="price-images">
      <?php while ($image = $imagesResult->fetch_assoc()) { ?>
        <img src="../uploads/<?php echo $image['file_name']; ?>" alt="Screenshot">
      <?php } ?>
    </div>

    <h2>Links to Hotels/Flights</h2>
    <ul id="links">
      <?php while ($link = $linksResult->fetch_assoc()) { ?>
        <li><a href="<?php echo $link['url']; ?>" target="_blank"><?php echo $link['description']; ?></a></li>
      <?php } ?>
    </ul>
  </section>

  <section id="add-info">
    <h2>Upload a Screenshot</h2>
    <form action="upload_image.php" method="post" enctype="multipart/form-data">
      <input type="hidden" name="destination_id" value="<?php echo $destinationId; ?>">
      <input type="file" name="image" required>
      <button type="submit">Upload</button>
    </form>

    <h2>Add a Link</h2>
    <form action="add_link.php" method="post">
      <input type="hidden" name="destination_id" value="<?php echo $destinationId; ?>">
      <input type="url" name="url" placeholder="URL" required>
      <input type="text" name="description" placeholder="Description" required>
      <button type="submit">Add Link</button>
    </form>
  </section>
</main>

<?php include_once 'footer.php'; ?>
