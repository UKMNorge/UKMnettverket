<?php

use UKMNorge\Wordpress\User;
header('Content-Type: application/json');

require_once('UKM/Autoloader.php');

$success = User::isAvailableUsername( $_POST['username'] );

UKMsystem_tools::addResponseData(
    [
        'success' => $success,
        'message' => 'Brukernavnet er '. ($success ? 'ledig' : 'allerede i bruk'),
        'username' => $_POST['username'],
        'count' => $_POST['count']
    ]
);