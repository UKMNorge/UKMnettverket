<?php

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\OAuth2\HandleAPICall;
use UKMNorge\Nettverk\OmradeKontaktpersoner;
use UKMNorge\Nettverk\WriteOmradeKontaktperson;
use UKMNorge\Nettverk\Omrade;


$handleCall = new HandleAPICall(['pl_id', 'redirect_til_arrangement'], ['remove_kontaktpersoner'], ['POST', 'GET'], false);

$plId = $handleCall->getArgument('pl_id');
$removeKontaktpersoner = $handleCall->getOptionalArgument('remove_kontaktpersoner') ?? [];
$redirectTilArrangement = $handleCall->getArgument('redirect_til_arrangement');


$arrangement = new Arrangement($plId);
$arrangementOmrade = new Omrade('monstring', $arrangement->getId());

foreach($arrangement->getKontaktpersoner()->getAll() as $okp) {
    // Hvis array is empty, alle kontaktpersoner skal fjernes ellers hopp over de som er i array
    if(is_array($removeKontaktpersoner) && $removeKontaktpersoner[$okp->getId()] ) {
        continue;
    }
    try {
        $okp = OmradeKontaktpersoner::getById($okp->getId());
        WriteOmradeKontaktperson::removeFromOmrade($okp, $arrangementOmrade);
    } catch(Exception $e) {
        // Selv om det feiler, fortsett til arrangementet   
    }
}
redirectToArrangement($redirectTilArrangement);


function redirectToArrangement($redirectTilArrangement) {
    echo '<script>window.location.href = "'. $redirectTilArrangement .'";</script>';
}
