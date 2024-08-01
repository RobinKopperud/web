<?php
include_once '../../db.php'; // Adjust the path as needed


if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_name'])) {
        $name = $_POST['name'];
        $status = $_POST['status'];
        $sql = "INSERT INTO names (name, status) VALUES ('$name', '$status')";
        $conn->query($sql);
    } elseif (isset($_POST['remove_name'])) {
        $name = $_POST['name'];
        $sql = "DELETE FROM names WHERE name = '$name'";
        $conn->query($sql);
    } elseif (isset($_POST['update_status'])) {
        $name = $_POST['name'];
        $status = $_POST['status'];
        $sql = "UPDATE names SET status = '$status' WHERE name = '$name'";
        $conn->query($sql);
    }
}

// Fetch all names to display
$sql = "SELECT name, status FROM names";
$result = $conn->query($sql);
$names = array();
while ($row = $result->fetch_assoc()) {
    $names[] = $row;
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Manage Names</title>
</head>
<body>
    <h1>Admin - Manage Names</h1>

    <form method="POST">
        <h2>Add Name</h2>
        <input type="text" name="name" required placeholder="Enter name">
        <select name="status">
            <option value="ok">OK</option>
            <option value="not_ok">Not OK</option>
        </select>
        <button type="submit" name="add_name">Add Name</button>
    </form>

    <form method="POST">
        <h2>Remove Name</h2>
        <select name="name">
            <?php foreach ($names as $name): ?>
                <option value="<?= $name['name'] ?>"><?= $name['name'] ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" name="remove_name">Remove Name</button>
    </form>

    <form method="POST">
        <h2>Update Status</h2>
        <select name="name">
            <?php foreach ($names as $name): ?>
                <option value="<?= $name['name'] ?>"><?= $name['name'] ?></option>
            <?php endforeach; ?>
        </select>
        <select name="status">
            <option value="ok">OK</option>
            <option value="not_ok">Not OK</option>
        </select>
        <button type="submit" name="update_status">Update Status</button>
    </form>

    <button onclick="window.location.href='index.html'">Back to List</button>
</body>
</html>
