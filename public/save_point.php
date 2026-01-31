<?php
// Connessione DB (sostituisci con i tuoi dati)
$conn = new mysqli("localhost", "username", "password", "nome_database");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $course_id = $_POST['course_id'];
    $hole = $_POST['hole'];
    $type = $_POST['type'];
    $lat = $_POST['lat'];
    $lng = $_POST['lng'];

    $stmt = $conn->prepare("INSERT INTO golf_coordinates (course_id, hole_number, point_type, latitude, longitude) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iisdd", $course_id, $hole, $type, $lat, $lng);

    if ($stmt->execute()) {
        echo "Successo!";
    } else {
        echo "Errore: " . $conn->error;
    }
}
?>
