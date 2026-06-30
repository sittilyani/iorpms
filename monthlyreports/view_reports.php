<?php
$reportsDir = 'Monthly Reports'; // Relative path to the reports folder

if (is_dir($reportsDir)) {
    $files = scandir($reportsDir);

    foreach ($files as $file) {
        if ($file !== '.' && $file !== '..') {
            echo "<a href='" . $reportsDir . "/" . $file . "' target='_blank'>" . $file . "</a><br>";
        }
    }
} else {
    echo 'The reports folder does not exist.';
}
   