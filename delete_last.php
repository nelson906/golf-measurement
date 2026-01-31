<?php
$conn = new mysqli("localhost", "username", "password", "nome_database");

// Elimina l'ultimo punto inserito per quel determinato campo
$course_id = $_POST['course_id'];
$stmt = $conn->prepare("DELETE FROM golf_coordinates WHERE course_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $course_id);

if ($stmt->execute()) {
    echo "Ultimo punto rimosso con successo";
} else {
    echo "Errore durante l'eliminazione";
}
?>
