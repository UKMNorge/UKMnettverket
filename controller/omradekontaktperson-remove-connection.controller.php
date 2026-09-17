<?php
use UKMNorge\OAuth2\HandleAPICall;
use UKMNorge\Nettverk\OmradeKontaktpersoner;
use UKMNorge\Nettverk\WriteOmradeKontaktperson;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\OAuth2\ArrSys\AccessControlArrSys;


$handleCall = new HandleAPICall(['okpId', 'omradeType', 'omradeId', 'page'], ['mobil'], ['POST', 'GET'], false);

$omradeType = $handleCall->getArgument('omradeType');
$omradeId = $handleCall->getArgument('omradeId');
$page = $handleCall->getArgument('page');

$okpId = $handleCall->getArgument('okpId');
$omrade = new Omrade($omradeType, $omradeId);
$mobil = $handleCall->getArgument('mobil');

if(!AccessControlArrSys::hasOmradeAccess($omrade)) {
    $handleCall->sendErrorToClient('Du har ikke tilgang til å slette kontaktpersonen fra området', 403);
}

try {
    $okp = OmradeKontaktpersoner::getById($okpId);
    WriteOmradeKontaktperson::removeFromOmrade($okp, $omrade);
} catch(Exception $e) {
    $handleCall->sendErrorToClient($e->getMessage(), 400);
}

echo '<script>window.location.href = "?page=' . $page . '&omrade='. $omradeId .'&type='. $omradeType .'";</script>';
// echo '<script>history.back();</script>';
exit();