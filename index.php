<?php
// Allow CORS and handle methods
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    exit(0);
}

// Your existing PHP code continues below...
// DON'T add this if you already have PHP code - just add the headers above your existing code
?>
