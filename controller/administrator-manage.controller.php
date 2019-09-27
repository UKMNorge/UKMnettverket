<?php

use UKMNorge\Wordpress\User;
use UKMNorge\Wordpress\WriteUser;
use UKMNorge\Nettverk\Administrator;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Nettverk\WriteOmrade;
use UKMNorge\Wordpress\Blog;

require_once('UKM/fylker.class.php');
require_once('UKM/Wordpress/User.class.php');
require_once('UKM/Wordpress/WriteUser.class.php');
require_once('UKM/Wordpress/User.class.php');
require_once('UKM/Wordpress/Blog.php');
require_once('UKM/Wordpress/WriteUser.class.php');
require_once('UKM/Nettverk/Administrator.class.php');
require_once('UKM/Nettverk/WriteOmrade.class.php');
require_once('UKM/Nettverk/Omrade.class.php');

/**
 *  FJERN ADMINISTRATOR
 */
if( isset( $_GET['removeAdmin'] ) ) {
    $omrade = Omrade::getByType( 
        $_GET['type'], 
        (Int) $_GET['omrade'], 
        (Int) get_option('season')
    );
    $admin = $omrade->getAdministratorer()->get( (Int) $_GET['removeAdmin'] );
    $res = WriteOmrade::fjernAdmin( $omrade, $admin );
    if( $res ) {
        UKMnettverket::getFlash()->add(
            'success',
            $admin->getUser()->getNavn() .' er fjernet som administrator for '. $omrade->getNavn()
        );
    }
}


/**
 *  LEGG TIL ADMINISTRATOR
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $user = User::loadByEmail($_POST['email']);
    } catch (Exception $e) {
        $user = User::createEmpty();
    }

    // OPPRETT + OPPDATER BRUKER
    if (isset($_POST['user_id'])) {
        // Opprett bruker
        if ($_POST['user_id'] == '0') {
            $user->setEmail($_POST['email']);
            $user->setUsername($_POST['username']);
            $created = true;
        } else {
            $created = false;
        }
        $user->setFirstName($_POST['first_name']);
        $user->setLastName($_POST['last_name']);
        $user->setPhone((int) $_POST['phone']);

        $user = WriteUser::save($user);

        $omrade = Omrade::getByType( 
            $_POST['omrade_type'], 
            (Int) $_POST['omrade_id'], 
            (Int) get_option('season')
        );
        $administrator = new Administrator( $user->getId() );
        WriteOmrade::leggTilAdmin( $omrade, $administrator );

        // Abonner på blog 1 - alle må det.
        Blog::leggTilBruker(1, $user->getId(), 'subscriber');

        UKMnettverket::getFlash()->add(
            'success',
            ($created ? 
                'Bruker er oppprettet for '. $user->getName() .' og '
                : $user->getName() . ' er ') .
            'lagt til som administrator for ' . $omrade->getNavn()
        );
    }
}
