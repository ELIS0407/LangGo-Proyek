<?php
session_start();

if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true || $_SESSION['role'] !== 'siswa') {
    header("location: ../login.php");
    exit;
}

// Get the class level from URL parameter
$class_level = isset($_GET['level']) ? $_GET['level'] : 'basic';

// Validate class level
if (!in_array($class_level, ['basic', 'intermediate', 'advanced'])) {
    $class_level = 'basic';
}

// Redirect to level_content.php with the class level parameter
header("Location: level_content.php?level=$class_level");
exit;
?>
