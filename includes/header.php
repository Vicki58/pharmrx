<?php
/**
 * Shared Header Template
 * Initializes authorization checks and includes page layout elements.
 */
require_once __DIR__ . '/../config/auth.php';
requireLogin(); // Globally enforce login for all pages including this header (except login/register)
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? htmlspecialchars($pageTitle) : "Pharmacy Management System"; ?></title>
    <!-- Custom CSS Styles -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="app-container">
        <!-- Sidebar Navigation -->
        <?php include_once __DIR__ . '/sidebar.php'; ?>
        
        <!-- Main Panel Content -->
        <main class="app-content">
