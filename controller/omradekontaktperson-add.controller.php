<?php

// Opprett OmradeKontaktperson og legg til i området

use UKMNorge\OAuth2\ArrSys\HandleAPICallWithAuthorization;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Nettverk\OmradeKontaktperson;
use UKMNorge\Nettverk\WriteOmradeKontaktperson;
use UKMNorge\Nettverk\OmradeKontaktpersoner;

require_once('UKM/Autoloader.php');


$omradeId = HandleAPICallWithAuthorization::getArgumentBeforeInit('omradeId', 'POST');
$omradeType = HandleAPICallWithAuthorization::getArgumentBeforeInit('omradeType', 'POST');
$redirectPage = HandleAPICallWithAuthorization::getArgumentBeforeInit('redirectPage', 'POST');

if(($omradeType != 'fylke' && $omradeType != 'kommune' && $omradeType != 'monstring') || $omradeType == null) {
    // Send error if the area type is not 'fylke' or 'kommune'
    HandleAPICallWithAuthorization::sendError("Støtter område type 'fylke', 'monstring' eller 'kommune'", 400);
}
$omrade = new Omrade($omradeType, $omradeId);

if($omradeType == 'monstring') {
    $tilgang = 'arrangement_i_kommune_fylke';
}
else {
    $tilgang = $omradeType == 'kommune' ? 'kommune_eller_fylke' : 'fylke';
}
$tilgangAttribute = $omradeId;

// For å legge til en kontaktperson i området, trenger ikke tilgang til området siden alle kan bruke same kontaktpersoner
$foundMobil = HandleAPICallWithAuthorization::getArgumentBeforeInit('foundMobil', 'POST');
if($foundMobil != 'null') {
    $okp = OmradeKontaktpersoner::getByMobil($foundMobil);
    connectOkpToOmrade($okp, $omrade, $redirectPage);
}
// Brukeren finnes ikke, opprett og legg til i området
else {   
    $handleCall = new HandleAPICallWithAuthorization(['fornavn', 'etternavn', 'mobil', 'epost', 'foundMobil', 'redirectPage'], ['beskrivelse'], ['POST'], false, false, $tilgang, $tilgangAttribute);

    $fornavn = $handleCall->getArgument('fornavn');
    $etternavn = $handleCall->getArgument('etternavn');
    $mobil = $handleCall->getArgument('mobil');
    $epost = $handleCall->getArgument('epost');
    $beskrivelse = $handleCall->getOptionalArgument('beskrivelse') ?? '';

    $redirectPage = $handleCall->getArgument('redirectPage');

    // Check mobil
    if(!preg_match('/^\d{8}$/', $mobil)) {
        HandleAPICallWithAuthorization::sendError('Mobilnummeret må være 8 siffer og kun tall', 400);
    }

    $okp = new OmradeKontaktperson(['id' => -1, 'fornavn' => $fornavn, 'etternavn' => $etternavn, 'mobil' => $mobil, 'epost' => $epost, 'beskrivelse' => $beskrivelse, 'eier_omrade_id' => $omradeId, 'eier_omrade_type' => $omradeType]);
    // Upload profile image
    WriteOmradeKontaktperson::uploadProfileImage($_FILES['profile_picture'], $okp, false);

    connectOkpToOmrade($okp, $omrade, $redirectPage);
}


function connectOkpToOmrade(OmradeKontaktperson $okp, Omrade $omrade, $redirectPage) {
    try {
        WriteOmradeKontaktperson::leggTilOmradeKontaktperson($omrade, $okp);
    } catch(Exception $e) {
        HandleAPICallWithAuthorization::sendError($e->getMessage(), 400);
    }

    // echo '<script>window.location.href = "?page=UKMnettverket_'. $omrade->getType() .'&omrade='. $omrade->getForeignId() .'&type='. $omrade->getType() .'";</script>';
    echo '<script>window.location.href = "?page=' . $redirectPage . '&omrade=' . $omrade->getForeignId() .'&type='. $omrade->getType() .'";</script>';

    exit();
}
