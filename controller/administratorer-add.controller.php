<?php

use UKMNorge\Wordpress\User;

require_once('UKM/fylker.class.php');
require_once('UKM/Wordpress/User.class.php');
require_once('UKM/Wordpress/WriteUser.class.php');

switch( $_GET['type'] ) {
    case 'fylke':
        UKMsystem_tools::addViewData('fylke', fylker::getById($_GET['omrade']));
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

    UKMsystem_tools::addViewData('doAdd', true);

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
    UKMsystem_tools::addViewData('user', $user);
}
