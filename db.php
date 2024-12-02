<?php
$host = 'localhost'; // Docker maps MySQL to localhost on port 3306
$username = 'root';
$password = 'root';
$dbname = 'local'; // The name of the database you created

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
