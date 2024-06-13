<?php
include_once '../../db.php'; // Adjust the path as needed

// Function to log errors
function log_error($message) {
    $log_file = dirname(__FILE__) . '/error_log.txt';
    error_log($message . "\n", 3, $log_file);
}

$pizzas = [];
$sql = "SELECT * FROM pizza ORDER BY section DESC, id ASC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $pizzas[] = $row;
    }
} else {
    log_error("No pizzas found");
}
$conn->close();
?>

<section id="pizza-section" class="menu-section">
    <h2>Pizza</h2>
    <div class="menu">
        <?php foreach ($pizzas as $pizza): ?>
            <?php if ($pizza['section'] === 'pizza'): ?>
                <div class="card">
                    <div class="number"><?php echo htmlspecialchars($pizza['id']); ?></div>
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($pizza['title']); ?></h3>
                        <p><?php echo htmlspecialchars($pizza['price']); ?></p>
                        <p><?php echo htmlspecialchars($pizza['description']); ?></p>
                        <button class="heart-button" data-id="<?php echo htmlspecialchars($pizza['id']); ?>">
                            ❤️ <span class="heart-count"><?php echo htmlspecialchars($pizza['hearts']); ?></span>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>

<section id="kebab-section" class="menu-section">
    <h2>Kebab</h2>
    <div class="menu">
        <?php foreach ($pizzas as $pizza): ?>
            <?php if ($pizza['section'] === 'kebab'): ?>
                <div class="card">
                    <div class="number"><?php echo htmlspecialchars($pizza['id']); ?></div>
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($pizza['title']); ?></h3>
                        <p><?php echo htmlspecialchars($pizza['price']); ?></p>
                        <p><?php echo htmlspecialchars($pizza['description']); ?></p>
                        <button class="heart-button" data-id="<?php echo htmlspecialchars($pizza['id']); ?>">
                            ❤️ <span class="heart-count"><?php echo htmlspecialchars($pizza['hearts']); ?></span>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>

<section id="grill-section" class="menu-section">
    <h2>Grill</h2>
    <div class="menu">
        <?php foreach ($pizzas as $pizza): ?>
            <?php if ($pizza['section'] === 'grill'): ?>
                <div class="card">
                    <div class="number"><?php echo htmlspecialchars($pizza['id']); ?></div>
                    <div class="card-content">
                        <h3><?php echo htmlspecialchars($pizza['title']); ?></h3>
                        <p><?php echo htmlspecialchars($pizza['price']); ?></p>
                        <p><?php echo htmlspecialchars($pizza['description']); ?></p>
                        <button class="heart-button" data-id="<?php echo htmlspecialchars($pizza['id']); ?>">
                            ❤️ <span class="heart-count"><?php echo htmlspecialchars($pizza['hearts']); ?></span>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
</section>
