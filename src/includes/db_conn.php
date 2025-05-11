<?php
require_once "conn.php";
/**
 * Database Connection for EcoCom Project
 *
 * This file establishes a connection to the MySQL database for the EcoCom project.
 */

// Database connection parameters
$host = 'localhost';
$user = 'root';
$pass = ''; // Update with your MySQL password if needed
$dbname = 'ecocom_db';
$charset = 'utf8mb4';

// Create connection
$conn = new mysqli($host, $user, $pass, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set charset
$conn->set_charset($charset);
