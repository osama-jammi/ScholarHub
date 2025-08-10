<?php
session_start();


if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['role'])) {
    $_SESSION['role'] = $_POST['role'];

    if ($_POST['role'] === 'etudiant') {
        header("Location: register_student.php");
    } else {
        header("Location: register_professor.php");
    }
    exit();
}
