<?php

use UKMNorge\OAuth2\ArrSys\HandleAPICallWithAuthorization;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Nettverk\OmradeKontaktperson;
use UKMNorge\Nettverk\WriteOmrade;

require_once('UKM/Autoloader.php');

$omradeId = HandleAPICallWithAuthorization::getArgumentBeforeInit('omradeId', 'GET');
$omradeType = HandleAPICallWithAuthorization::getArgumentBeforeInit('omradeType', 'GET');

if(($omradeType != 'fylke' && $omradeType != 'kommune') || $omradeType == null) {
    // Send error if the area type is not 'fylke' or 'kommune'
    HandleAPICallWithAuthorization::sendError("Støtter område type 'fylke' eller 'kommune'", 400);
}

$tilgang = $omradeType == 'fylke' ? 'fylke' : 'kommune';
$tilgangAttribute = $omradeId;

$handleCall = new HandleAPICallWithAuthorization(['omradeId', 'omradeType', 'omradeKontaktpersonId'], [], ['GET'], false, false, $tilgang, $tilgangAttribute);

$omradeKontaktpersonId = $handleCall->getArgument('omradeKontaktpersonId');

$omrade = Omrade::getByType($omradeType, $omradeId);


UKMnettverket::addViewData('omradekontaktperson', $omrade->getOmradeKontaktpersoner()->get($omradeKontaktpersonId));
UKMnettverket::addViewData('omrade', $omrade);
