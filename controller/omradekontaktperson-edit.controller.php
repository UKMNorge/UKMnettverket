<?php

use UKMNorge\OAuth2\ArrSys\HandleAPICallWithAuthorization;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Nettverk\OmradeKontaktperson;
use UKMNorge\Nettverk\WriteOmradeKontaktperson;
use UKMNorge\Nettverk\OmradeKontaktpersoner;
use UKMNorge\OAuth2\ArrSys\AccessControlArrSys;


require_once('UKM/Autoloader.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $omradeId = HandleAPICallWithAuthorization::getArgumentBeforeInit('omradeId', 'POST');
    $omradeType = HandleAPICallWithAuthorization::getArgumentBeforeInit('omradeType', 'POST');
    if(($omradeType != 'fylke' && $omradeType != 'kommune') || $omradeType == null) {
        // Send error if the area type is not 'fylke' or 'kommune'
        HandleAPICallWithAuthorization::sendError("Støtter område type 'fylke' eller 'kommune'", 400);
    }

    $tilgang = $omradeType == 'fylke' ? 'fylke' : 'kommune';
    $tilgangAttribute = $omradeId;

    $handleCall = new HandleAPICallWithAuthorization(['okpId', 'fornavn', 'mobil', 'etternavn', 'epost'], ['beskrivelse'], ['GET', 'POST'], false, false, $tilgang, $tilgangAttribute);

    $id = $handleCall->getArgument('okpId');
    $fornavn = $handleCall->getArgument('fornavn');
    $etternavn = $handleCall->getArgument('etternavn');
    $epost = $handleCall->getArgument('epost');
    $beskrivelse = $handleCall->getOptionalArgument('beskrivelse') ?? '';
    $mobil = $handleCall->getArgument('mobil');

    // Check mobil
    if(!preg_match('/^\d{8}$/', $mobil)) {
        HandleAPICallWithAuthorization::sendError('Mobilnummeret må være 8 siffer og kun tall', 400);
    }

    try {
        $okp = OmradeKontaktpersoner::getByMobil($mobil);
        $okp->setFornavn($fornavn);
        $okp->setEtternavn($etternavn);
        $okp->setEpost($epost);
        $okp->setBeskrivelse($beskrivelse);
        WriteOmradeKontaktperson::editOmradekontaktperson($okp);
    } catch(Exception $e) {
        HandleAPICallWithAuthorization::sendError($e->getMessage(), 400);
    }


    echo '<script>window.location.href = "?page=UKMnettverket_'. $omradeType .'&omrade='. $omradeId .'&type='. $omradeType .'";</script>';
    exit();
}
else {
    $mobil = HandleAPICallWithAuthorization::getArgumentBeforeInit('mobil', 'GET');
    $okp = OmradeKontaktpersoner::getByMobil($mobil);

    $omrade = new Omrade($okp->getEierOmradeType(), $okp->getEierOmradeId());
    if(!AccessControlArrSys::hasOmradeAccess($omrade)) {
        UKMnettverket::addViewData('tilgang', false);
    }
    else {
        showUser($okp);
    }
}

function showUser(OmradeKontaktperson $okp) {
    $omrade = new Omrade($okp->getEierOmradeType(), $okp->getEierOmradeId());

    UKMnettverket::addViewData('tilgang', true);
    UKMnettverket::addViewData('omradekontaktperson', $okp);
    UKMnettside::addViewData('omrade', $omrade);

}

    