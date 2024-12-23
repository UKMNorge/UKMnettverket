<?php

// Side hvor OmradeKontaktpersonen kan opprettes

use UKMNorge\OAuth2\ArrSys\HandleAPICallWithAuthorization;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Nettverk\OmradeKontaktperson;

require_once('UKM/Autoloader.php');

$redirectPage = HandleAPICallWithAuthorization::getArgumentBeforeInit('page', 'GET');
$omradeId = HandleAPICallWithAuthorization::getArgumentBeforeInit('omradeId', 'GET');
$omradeType = HandleAPICallWithAuthorization::getArgumentBeforeInit('omradeType', 'GET');

if(($omradeType != 'fylke' && $omradeType != 'kommune') || $omradeType == null) {
    // Send error if the area type is not 'fylke' or 'kommune'
    HandleAPICallWithAuthorization::sendError("Støtter område type 'fylke' eller 'kommune'", 400);
}

$tilgang = $omradeType == 'kommune' ? 'kommune_eller_fylke' : 'fylke';
$tilgangAttribute = $omradeId;

$handleCall = new HandleAPICallWithAuthorization(['omradeId', 'omradeType'], [], ['GET'], false, false, $tilgang, $tilgangAttribute);

$omrade = Omrade::getByType($omradeType, $omradeId);

$omradeKontaktperson = OmradeKontaktperson::createEmpty();


UKMnettverket::addViewData('omradekontaktperson', $omradeKontaktperson);
UKMnettverket::addViewData('omrade', $omrade);
UKMnettverket::addViewData('redirectPage', $redirectPage);
