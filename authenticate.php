<?php
session_start();

// Definer hardkodede brukernavn og passord
$valid_username = 'admin';
$valid_password = 'password';

// Få innsendt brukernavn og passord
$username = $_POST['username'];
$password = $_POST['password'];

// Sjekk om brukernavn og passord er gyldige
if ($username === $valid_username && $password === $valid_password) {
    $_SESSION['loggedin'] = true;
    header('Location: protected/index.php');
    exit();
} else {
    echo "Feil brukernavn eller passord.";
}
?>
