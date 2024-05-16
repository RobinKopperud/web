<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $service = $_POST['service'];
    $message = $_POST['message'];
    $userEmail = $_POST['userEmail'];

    // Email to you (Robin Kopperud)
    $to = 'robinkopperud@robinkopperud.no';
    $subject = 'Hjelp med ' . $service;
    $body = "Hei,\n\nJeg trenger hjelp med følgende:\n\n$message\n\nMed vennlig hilsen,\n$userEmail";
    $headers = 'From: ' . $userEmail . "\r\n" .
               'Reply-To: ' . $userEmail . "\r\n" .
               'Content-Type: text/plain; charset=UTF-8' . "\r\n" .
               'MIME-Version: 1.0' . "\r\n" .
               'X-Mailer: PHP/' . phpversion();

    $mailSent = mail($to, $subject, $body, $headers);

    // Auto-reply to the user
    $replySubject = 'Bekreftelse: på ' . $service;
    $replyBody = "Hei,\n\nTakk for at du kontaktet oss angående $service.\n\nVi har mottatt din forespørsel og vil komme tilbake til deg så snart som mulig.\n\nMed vennlig hilsen,\nRobin Kopperud";
    $replyHeaders = 'From: robinkopperud@robinkopperud.no' . "\r\n" .
                    'Reply-To: robinkopperud@robinkopperud.no' . "\r\n" .
                    'Content-Type: text/plain; charset=UTF-8' . "\r\n" .
                    'MIME-Version: 1.0' . "\r\n" .
                    'X-Mailer: PHP/' . phpversion();

    $autoReplySent = mail($userEmail, $replySubject, $replyBody, $replyHeaders);

    if ($mailSent && $autoReplySent) {
        echo 'E-posten ble sendt og en bekreftelses-e-post er sendt til brukeren.';
    } else {
        echo 'Det oppstod en feil ved sending av e-posten.';
    }
} else {
    echo 'Ugyldig forespørsel.';
}
?>
