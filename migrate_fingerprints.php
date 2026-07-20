<?php
require_once 'includes/config.php';

echo "Recreating fingerprints table...\n";

$conn->query("SET FOREIGN_KEY_CHECKS = 0");
$conn->query("DROP TABLE IF EXISTS fingerprints");

$sql = "CREATE TABLE fingerprints (
  id INT AUTO_INCREMENT PRIMARY KEY,
  visitDate DATETIME DEFAULT CURRENT_TIMESTAMP,
  mat_id VARCHAR(50) NOT NULL,
  mat_number VARCHAR(50) DEFAULT NULL,
  clientName VARCHAR(100) NOT NULL,
  nickname VARCHAR(100) DEFAULT NULL,
  dob DATE DEFAULT NULL,
  sex VARCHAR(50) NOT NULL,
  current_status VARCHAR(50) NOT NULL,
  fingerprint_data LONGBLOB DEFAULT NULL,
  template_data LONGBLOB DEFAULT NULL,
  quality_score INT DEFAULT 0,
  fingerprint_type VARCHAR(50) DEFAULT 'Index',
  scanner_type VARCHAR(50) DEFAULT 'ZKTeco',
  capture_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (mat_id) REFERENCES patients (mat_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci";

if ($conn->query($sql)) {
    echo "Fingerprints table recreated successfully!\n";
} else {
    echo "Error creating table: " . $conn->error . "\n";
}

$conn->query("SET FOREIGN_KEY_CHECKS = 1");
?>
