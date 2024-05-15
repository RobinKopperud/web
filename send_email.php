<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service = $_POST['service'];
    $message = $_POST['message'];

    $to = 'robinkopperud@robinkopperud.no';
    $subject = 'Hjelp med ' . $service;
    $body = "Hei,\n\nJeg trenger hjelp med følgende:\n\n$message\n\nMed vennlig hilsen,\n[Ditt Navn]";
    $headers = 'From: noreply@robinkopperud.no' . "\r\n" .
               'Reply-To: noreply@robinkopperud.no' . "\r\n" .
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
