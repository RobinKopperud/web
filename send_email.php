<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service = $_POST['service'];
    $message = $_POST['message'];
    $userEmail = $_POST['userEmail'];

    $to = 'robinkopperud@robinkopperud.no';
    $subject = 'Hjelp med ' . $service;
    $body = "Hei,\n\nJeg trenger hjelp med følgende:\n\n$message\n\nMed vennlig hilsen,\n$userEmail";
    $headers = 'From: ' . $userEmail . "\r\n" .
               'Reply-To: ' . $userEmail . "\r\n" .
               'Content-Type: text/plain; charset=UTF-8' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();

    if (mail($to, $subject, $body, $headers)) {
        echo 'E-posten ble sendt.';
    } else {
        echo 'Det oppstod en feil ved sending av e-posten.';
    }
} else {
    echo 'Ugyldig forespørsel.';
}
?>
