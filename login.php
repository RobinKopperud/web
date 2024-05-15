<!DOCTYPE html>
<html lang="no">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logg inn</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background-color: #f4f4f4;
        }
        .login-container {
            background-color: #fff;
            padding: 2rem;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .login-container h2 {
            margin-bottom: 1rem;
        }
        .login-container form {
            display: flex;
            flex-direction: column;
        }
        .login-container input {
            margin-bottom: 1rem;
            padding: 0.5rem;
            font-size: 1rem;
        }
        .login-container button {
            padding: 0.5rem;
            font-size: 1rem;
            background-color: #333;
            color: #fff;
            border: none;
            cursor: pointer;
        }
        .login-container button:hover {
            background-color: #555;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Logg inn</h2>
        <form action="authenticate.php" method="post">
            <input type="text" name="username" placeholder="Brukernavn" required>
            <input type="password" name="password" placeholder="Passord" required>
            <button type="submit">Logg inn</button>
        </form>
    </div>
</body>
</html>
