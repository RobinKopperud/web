<?php

function getLatestMeasurement($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM tren_measurements WHERE user_id = ? ORDER BY date DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $measurement = $result->fetch_assoc();
    $stmt->close();
    return $measurement;
}

function getLatestPhoto($conn, $user_id) {
    $stmt = $conn->prepare("SELECT * FROM tren_photos WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $photo = $result->fetch_assoc();
    $stmt->close();
    return $photo;
}

function addMeasurement($conn, $user_id, $weight, $waist, $widest, $date) {
    $stmt = $conn->prepare("INSERT INTO tren_measurements (user_id, weight, waist, widest, date) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iddss", $user_id, $weight, $waist, $widest, $date);
    $stmt->execute();
    $stmt->close();
}

function uploadPhoto($conn, $user_id, $file_path) {
    $stmt = $conn->prepare("INSERT INTO tren_photos (user_id, file_path) VALUES (?, ?)");
    $stmt->bind_param("is", $user_id, $file_path);
    $stmt->execute();
    $stmt->close();
}

function getFirstPhoto($conn, $user_id) {
    $stmt = $conn->prepare("SELECT file_path FROM tren_photos WHERE user_id = ? ORDER BY uploaded_at ASC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $first_photo = $result->fetch_assoc();
    $stmt->close();
    return $first_photo ? $first_photo['file_path'] : null;
}

function getLastPhoto($conn, $user_id) {
    $stmt = $conn->prepare("SELECT file_path FROM tren_photos WHERE user_id = ? ORDER BY uploaded_at DESC LIMIT 1");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $last_photo = $result->fetch_assoc();
    $stmt->close();
    return $last_photo ? $last_photo['file_path'] : null;
}
