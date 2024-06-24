<?php
// Include the database connection file
include_once '../../../db.php'; // Adjust the path as needed

// Fetch the timeline events from the database
$sql = "SELECT * FROM timeline_events ORDER BY event_date ASC";
$result = $conn->query($sql);

$events = [];
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $events[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tidslinje - Mental Racing Team</title>
    <link rel="stylesheet" href="timeline.css?v=1.3">
</head>
<body>
    <header>
        <div class="container">
            <a href="../index.php" class="header-link">
                <h1>Mental Racing Team</h1>
            </a>
        </div>
    </header>

    <section class="container">
        <h2>Tidslinje</h2>
        <div class="timeline">
            <?php foreach ($events as $index => $event): ?>
                <div class="timeline-card <?php echo $index % 2 == 0 ? 'high' : 'low'; ?>">
                    <div class="date"><?php echo htmlspecialchars($event['event_date']); ?></div>
                    <div class="image-container">
                        <?php $image = !empty($event['image']) ? htmlspecialchars($event['image']) : 'v3.jpg'; ?>
                        <img src="../uploads/<?php echo $image; ?>" alt="<?php echo htmlspecialchars($event['title']); ?>">
                    </div>
                    <div class="content">
                        <h2><?php echo htmlspecialchars($event['title']); ?></h2>
                        <p><?php echo htmlspecialchars($event['comment']); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</body>
</html>
