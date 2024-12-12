<?php

use UKMNorge\OAuth2\ArrSys\HandleAPICallWithAuthorization;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Nettverk\OmradeKontaktperson;
use UKMNorge\Nettverk\WriteOmradeKontaktperson;

require_once('UKM/Autoloader.php');


$omradeId = HandleAPICallWithAuthorization::getArgumentBeforeInit('omradeId', 'POST');
$omradeType = HandleAPICallWithAuthorization::getArgumentBeforeInit('omradeType', 'POST');
if(($omradeType != 'fylke' && $omradeType != 'kommune') || $omradeType == null) {
    // Send error if the area type is not 'fylke' or 'kommune'
    HandleAPICallWithAuthorization::sendError("Støtter område type 'fylke' eller 'kommune'", 400);
}

$tilgang = $omradeType == 'fylke' ? 'fylke' : 'kommune';
$tilgangAttribute = $omradeId;

$handleCall = new HandleAPICallWithAuthorization(['fornavn', 'etternavn', 'mobil', 'epost'], ['beskrivelse'], ['POST'], false, false, $tilgang, $tilgangAttribute);

$fornavn = $handleCall->getArgument('fornavn');
$etternavn = $handleCall->getArgument('etternavn');
$mobil = $handleCall->getArgument('mobil');
$epost = $handleCall->getArgument('epost');
$beskrivelse = $handleCall->getArgument('beskrivelse');


try {
    $omradeKontaktperson = new OmradeKontaktperson(['id' => -1, 'fornavn' => $fornavn, 'etternavn' => $etternavn, 'mobil' => $mobil, 'epost' => $epost, 'beskrivelse' => $beskrivelse, 'eier_omrade_id' => $omradeId, 'eier_omrade_type' => $omradeType]);
    $omrade = new Omrade($omradeType, $omradeId);
    WriteOmradeKontaktperson::leggTilOmradeKontaktperson($omrade, $omradeKontaktperson);
} catch(Exception $e) {
    HandleAPICallWithAuthorization::sendError($e->getMessage(), 400);
}


$handleCall->sendToClient(true);