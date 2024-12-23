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

    $tilgang = $omradeType == 'kommune' ? 'kommune_eller_fylke' : 'fylke';
    $tilgangAttribute = $omradeId;

    $handleCall = new HandleAPICallWithAuthorization(['okpId', 'fornavn', 'mobil', 'etternavn'], ['epost', 'beskrivelse', 'deletedProfileImage'], ['POST'], false, false, $tilgang, $tilgangAttribute);

    $id = $handleCall->getArgument('okpId');
    $fornavn = $handleCall->getArgument('fornavn');
    $etternavn = $handleCall->getArgument('etternavn');
    $mobil = $handleCall->getArgument('mobil');
    $epost = $handleCall->getOptionalArgument('epost');
    $beskrivelse = $handleCall->getOptionalArgument('beskrivelse') ?? '';
    $deletedProfileImage = $handleCall->getOptionalArgument('deletedProfileImage') == 'true' ? true : false;

    try {
        $okp = OmradeKontaktpersoner::getByMobil($mobil);
        $okp->setFornavn($fornavn);
        $okp->setEtternavn($etternavn);
        $okp->setEpost($epost);
        $okp->setBeskrivelse($beskrivelse);
        
        WriteOmradeKontaktperson::uploadProfileImage($_FILES['profile_picture'], $okp, $deletedProfileImage);
        WriteOmradeKontaktperson::editOmradekontaktperson($okp);
    } catch(Exception $e) {
        HandleAPICallWithAuthorization::sendError($e->getMessage(), 400);
    }

    echo '<script>
        window.history.back();
    </script>';
    exit();
}
else {
    $mobil = HandleAPICallWithAuthorization::getArgumentBeforeInit('mobil', 'GET');
    $okp = OmradeKontaktpersoner::getByMobil($mobil);


    UKMnettverket::addViewData('tilbakeUrl', getTilbakeLenke());
    $omrade = new Omrade($okp->getEierOmradeType(), $okp->getEierOmradeId());
    if(!AccessControlArrSys::hasOmradeAccess($omrade)) {
        UKMnettverket::addViewData('omrade', $omrade);
        UKMnettverket::addViewData('tilgang', false);    
    }
    else {
        showUser($okp);
    }
}

function getTilbakeLenke() {
    $userOmradeId = HandleAPICallWithAuthorization::getArgumentBeforeInit('omradeId', 'GET');
    $userOmradeType = HandleAPICallWithAuthorization::getArgumentBeforeInit('omradeType', 'GET');
    return '?page=UKMnettverket_'. ($userOmradeType == 'fylke' ? 'fylker' : $userOmradeType) . '&omrade=' . $userOmradeId .'&type='. $userOmradeType;
}

function showUser(OmradeKontaktperson $okp) {
    $omrade = new Omrade($okp->getEierOmradeType(), $okp->getEierOmradeId());

    UKMnettverket::addViewData('tilgang', true);
    UKMnettverket::addViewData('omradekontaktperson', $okp);
    UKMnettside::addViewData('omrade', $omrade);

}