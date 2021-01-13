<?php

use UKMNorge\Wordpress\Blog;
header('Content-Type: application/json');

require_once('UKM/Autoloader.php');

$success = Blog::isAvailablePath( $_POST['path_geo'] . $_POST['path_event'] );

UKMsystem_tools::addResponseData(
    [
        'success' => $success,
        'message' => 'Adressen er '. ($success ? 'ledig' : 'allerede i bruk'),
        'count' => $_POST['count'],
        'path' => $_POST['path_geo'] . $_POST['path_event']
    ]
);