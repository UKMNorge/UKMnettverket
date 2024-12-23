<?php

use UKMNorge\Nettverk\OmradeKontaktpersoner;

header('Content-Type: application/json');

require_once('UKM/Autoloader.php');

if(!isset($_POST['mobil'])) {
    UKMsystem_tools::addResponseData(
        [
            'success' => false,
            'message' => 'Mangler mobil',
        ]
    );
    die;
}

$okp = null;
try {
    $okp = OmradeKontaktpersoner::getByMobil($_POST['mobil']);
} catch(Exception $e) {
    UKMsystem_tools::addResponseData(
        [
            'userFound' => false,
            'message' => $e->getMessage(),
        ]
    );
}

if($okp != null) {
    UKMsystem_tools::addResponseData(
        [
            'userFound' => true,
            'okp' => $okp->getArray(),
        ]
    );
}