<?php

use UKMNorge\Wordpress\Blog;
header('Content-Type: application/json');

require_once('UKM/Autoloader.php');

$success = Blog::isAvailablePath( $_POST['path'] );

UKMsystem_tools::addResponseData(
    [
        'success' => $success,
        'message' => 'Adressen er '. ($success ? 'ledig' : 'allerede i bruk'),
        'path' => $_POST['path'],
        'count' => $_POST['count']
    ]
);