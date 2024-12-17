<?php
use UKMNorge\OAuth2\HandleAPICall;
use UKMNorge\Nettverk\OmradeKontaktpersoner;
use UKMNorge\Nettverk\WriteOmradeKontaktperson;
use UKMNorge\Nettverk\Omrade;


$handleCall = new HandleAPICall(['mobil', 'omradeType', 'omradeId'], [], ['POST', 'GET'], false);

$omradeType = $handleCall->getArgument('omradeType');
$omradeId = $handleCall->getArgument('omradeId');

$omrade = new Omrade($omradeType, $omradeId);
$mobil = $handleCall->getArgument('mobil');
// Check mobil
if(!preg_match('/^\d{8}$/', $mobil)) {
    $handleCall->sendErrorToClient('Mobilnummeret må være 8 siffer og kun tall', 400);
}

try {
    $okp = OmradeKontaktpersoner::getByMobil($mobil);
    WriteOmradeKontaktperson::removeFromOmrade($okp, $omrade);
} catch(Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), 400);
}

// echo '<script>window.location.href = "?page=UKMnettverket_'. $omradeType .'&omrade='. $omradeId .'&type='. $omradeType .'";</script>';
echo '<script>history.back();</script>';
exit();