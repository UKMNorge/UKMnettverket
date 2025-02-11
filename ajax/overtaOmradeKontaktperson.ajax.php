<?php

/**
 * OVERTA EN KONTAKTPERSON
 */

use UKMNorge\Nettverk\Omrade;
use UKMNorge\Nettverk\OmradeKontaktpersoner;
use UKMNorge\Nettverk\WriteOmradeKontaktperson;
use UKMNorge\OAuth2\HandleAPICall;

require_once('UKM/Autoloader.php');

$handleCall = new HandleAPICall(['okpid', 'omradeType', 'omradeId'],[], ['GET', 'POST'], false);

$okpid = $handleCall->getArgument('okpid');
$omradeType = $handleCall->getArgument('omradeType');
$omradeId = $handleCall->getArgument('omradeId');

try {
    $omrade = new Omrade($omradeType, $omradeId);
    $okp = OmradeKontaktpersoner::getById($okpid);

    // Ta over kontaktpersonen
    WriteOmradeKontaktperson::overtaOmradekontaktperson($okp, $omrade);
} catch (Exception $e) {
    $handleCall->sendErrorToClient('Kunne ikke lagre endringer, kontakt support', 401);
}

$handleCall->sendToClient([
    'success' => true,
]);