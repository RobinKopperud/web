<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nordkisa Pizza og Grill</title>
    <link rel="stylesheet" href="styles.css">
    <script src="script.js"></script>

</head>
<body>
    <nav>
        <h1>Nordkisa Pizza og Grill</h1>
    </nav>
    <div class="contact">
        <a href="tel:+1234567890">Call us: +1234567890</a>
    </div>

    <div class="edit">
    <h2>Edit Menu</h2>
    <div class="form-section">
        <h3>Add a New Card</h3>
        <?php
        if (isset($_GET['message'])) {
            echo '<p>' . htmlspecialchars($_GET['message']) . '</p>';
        }
        ?>
        <form id="addCardForm" action="add_pizza.php" method="POST">
            <label for="section">Section:</label>
            <select id="section" name="section">
                <option value="pizza">Pizza</option>
                <option value="kebab">Kebab</option>
                <option value="grill">Grill</option>
            </select><br>
            <label for="title">Title:</label>
            <input type="text" id="title" name="title" required><br>
            <label for="price">Price:</label>
            <input type="text" id="price" name="price" required><br>
            <label for="description">Description:</label>
            <input type="text" id="description" name="description" required><br>
            <button type="submit">Add Card</button>
        </form>
    </div>
    <div class="form-section">
        <h3>Remove a Card</h3>
        <form id="removeCardForm" action="remove_pizza.php" method="POST">
            <label for="removeNumber">Number:</label>
            <input type="number" id="removeNumber" name="removeNumber" required><br>
            <button type="submit">Remove Card</button>
        </form>
    </div>
</div>



    <!-- Include the sections dynamically generated from the database -->
    <?php include 'fetch_pizzas.php'; ?>

    <footer>
        <a href="tel:+1234567890">Call us: +1234567890</a><br>
        <a href="mailto:contact@nordkisapizzaoggrill.com">Email us</a>
    </footer>

</body>
</html>
