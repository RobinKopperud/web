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
    <link rel="stylesheet" href="../style.css">
    <link rel="stylesheet" href="../mobile.css" media="screen and (max-width: 768px)">
    <link rel="stylesheet" href="timeline.css">
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
            <?php
            $side = 'left';
            foreach ($events as $event) {
                echo '<div class="timeline-event ' . $side . '">';
                echo '    <div class="image-container">';
                $image = !empty($event['image']) ? htmlspecialchars($event['image']) : 'v3.jpg';
                echo '        <img src="../uploads/' . $image . '" alt="' . htmlspecialchars($event['title']) . '">';
                echo '    </div>';
                echo '    <div class="content">';
                echo '        <h2>' . htmlspecialchars($event['title']) . '</h2>';
                echo '        <p>' . htmlspecialchars($event['comment']) . '</p>';
                echo '    </div>';
                echo '    <div class="date">' . htmlspecialchars($event['event_date']) . '</div>';
                echo '</div>';
                $side = ($side === 'left') ? 'right' : 'left';
            }
            ?>
        </div>
    </section>
</body>
</html>
