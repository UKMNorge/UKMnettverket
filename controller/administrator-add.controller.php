<?php

use Symfony\Component\Security\Acl\Exception\Exception;
use UKMNorge\Geografi\Fylker;
use UKMNorge\Wordpress\User;

require_once('UKM/Autoloader.php');

switch( $_GET['type'] ) {
    case 'fylke':
        UKMNettverket::addViewData('fylke', Fylker::getById($_GET['omrade']));
    break;
    case 'kommune':
    break;

    default:
        throw new Exception(
            'Beklager, men støtte for '. $_GET['type'] .' er ikke integrert. '.
            '<a href="mailto:support@ukm.no">Kontakt UKM Norge</a>'
        );
}


/* BRUKEREN HAR SKREVET INN E-POSTADRESSE */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Hent bruker eller opprett placeholder-objekt
    try {
        $user = User::loadByEmail($_POST['email']);
    } catch (Exception $e) {
        $user = User::createEmpty();
    }

    UKMnettverket::addViewData('doAdd', true);

    // Bruker eksisterer ikke - fyll ut e-post og brukernavn
    if (!$user->isReal()) {
        $user->setEmail($_POST['email']);
        $user->setUsername(
            substr(
                $_POST['email'],
                0,
                strpos($_POST['email'], '@')
            )
        );
    }
    UKMnettverket::addViewData('user', $user);
}
