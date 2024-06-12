<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nordkisa Pizza og Grill</title>
    <link rel="stylesheet" href="styles.css">
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
            <form id="addCardForm">
                <label for="section">Section:</label>
                <select id="section" name="section">
                    <option value="pizza">Pizza</option>
                    <option value="kebab">Kebab</option>
                    <option value="grill">Grill</option>
                </select><br>
                <label for="title">Title:</label>
                <input type="text" id="title" name="title"><br>
                <label for="price">Price:</label>
                <input type="text" id="price" name="price"><br>
                <label for="description">Description:</label>
                <input type="text" id="description" name="description"><br>
                <button type="button" onclick="handleAddCard()">Add Card</button>
            </form>
        </div>
        <div class="form-section">
            <h3>Remove a Card</h3>
            <form id="removeCardForm">
                <label for="removeNumber">Number:</label>
                <input type="number" id="removeNumber" name="removeNumber"><br>
                <button type="button" onclick="handleRemoveCard()">Remove Card</button>
            </form>
        </div>
    </div>

    <section id="pizza-section" class="menu-section">
        <h2>Pizza</h2>
        <div class="menu">
            <!-- Initial cards can be added here -->
        </div>
    </section>

    <section id="kebab-section" class="menu-section">
        <h2>Kebab</h2>
        <div class="menu">
            <!-- Initial cards can be added here -->
        </div>
    </section>

    <section id="grill-section" class="menu-section">
        <h2>Grill</h2>
        <div class="menu">
            <!-- Initial cards can be added here -->
        </div>
    </section>

    <footer>
        <a href="mailto:contact@nordkisapizzaoggrill.com">Email us</a>
        <a href="tel:+1234567890">Call us: +1234567890</a>
    </footer>
    <script src="script.js"></script>
</body>
</html>
