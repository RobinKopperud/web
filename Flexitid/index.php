<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fleksitid Logging</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <div class="container">
        <h1>Fleksitid Logger</h1>

        <div id="auth-section">
            <h2>Register</h2>
            <form id="register-form">
                <input type="text" id="reg-username" placeholder="Username" required>
                <input type="password" id="reg-password" placeholder="Password" required>
                <button type="submit">Register</button>
            </form>

            <h2>Login</h2>
            <form id="login-form">
                <input type="text" id="login-username" placeholder="Username" required>
                <input type="password" id="login-password" placeholder="Password" required>
                <button type="submit">Login</button>
            </form>
        </div>

        <div id="log-section" style="display: none;">
            <button id="login-btn">Logg Inn</button>
            <button id="logout-btn">Logg Ut</button>
            <div id="log-display" class="log-display">
                <h2>Logg</h2>
                <ul id="log-list"></ul>
                <h3 id="time-status"></h3>
            </div>
        </div>
    </div>
    <script src="script.js"></script>
</body>
</html>
