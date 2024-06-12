<?php
include_once '../../db.php'; // Adjust the path as needed

// Function to log errors
function log_error($message) {
    $log_file = dirname(__FILE__) . '/error_log.txt';
    error_log($message . "\n", 3, $log_file);
}

$pizzas = [];
$sql = "SELECT * FROM pizza ORDER BY section, id ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pizzas[] = $row;
    }
} else {
    log_error("No pizzas found");
}
$conn->close();

// Function to renumber pizzas based on section order
function renumber_pizzas($pizzas) {
    $number = 1;
    foreach ($pizzas as &$pizza) {
        $pizza['new_id'] = $number++;
    }
    return $pizzas;
}

// Separate pizzas by section and renumber them
$pizza_section = [];
$kebab_section = [];
$grill_section = [];

foreach ($pizzas as $pizza) {
    switch ($pizza['section']) {
        case 'pizza':
            $pizza_section[] = $pizza;
            break;
        case 'kebab':
            $kebab_section[] = $pizza;
            break;
        case 'grill':
            $grill_section[] = $pizza;
            break;
    }
}

$pizza_section = renumber_pizzas($pizza_section);
$kebab_section = renumber_pizzas($kebab_section);
$grill_section = renumber_pizzas($grill_section);
?>

<section id="pizza-section" class="menu-section">
    <h2>Pizza</h2>
    <div class="menu">
        <?php foreach ($pizza_section as $pizza): ?>
            <div class="card">
                <div class="number"><?php echo htmlspecialchars($pizza['new_id']); ?></div>
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($pizza['title']); ?></h3>
                    <p><?php echo htmlspecialchars($pizza['price']); ?></p>
                    <p><?php echo htmlspecialchars($pizza['description']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="kebab-section" class="menu-section">
    <h2>Kebab</h2>
    <div class="menu">
        <?php foreach ($kebab_section as $kebab): ?>
            <div class="card">
                <div class="number"><?php echo htmlspecialchars($kebab['new_id']); ?></div>
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($kebab['title']); ?></h3>
                    <p><?php echo htmlspecialchars($kebab['price']); ?></p>
                    <p><?php echo htmlspecialchars($kebab['description']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>

<section id="grill-section" class="menu-section">
    <h2>Grill</h2>
    <div class="menu">
        <?php foreach ($grill_section as $grill): ?>
            <div class="card">
                <div class="number"><?php echo htmlspecialchars($grill['new_id']); ?></div>
                <div class="card-content">
                    <h3><?php echo htmlspecialchars($grill['title']); ?></h3>
                    <p><?php echo htmlspecialchars($grill['price']); ?></p>
                    <p><?php echo htmlspecialchars($grill['description']); ?></p>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</section>
