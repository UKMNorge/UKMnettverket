<?php

use UKMNorge\API\Mailchimp\Liste\Arrangor;
use UKMNorge\API\Mailchimp\Mailchimp;
use UKMNorge\Wordpress\User;
use UKMNorge\Wordpress\WriteUser;
use UKMNorge\Nettverk\Administrator;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Nettverk\WriteOmrade;
use UKMNorge\Rapporter\Template\Write;
use UKMNorge\Wordpress\Blog;

require_once('UKM/Autoloader.php');

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

        // Deaktiverte brukere reaktiveres og får nytt passord
        if( !User::erAktiv( $user->getId() ) ) {
            WriteUser::aktiver($user);
            $passord = WriteUser::genererPassord();
            WriteUser::setPassord( $user, $passord );
            WriteUser::sendNyttPassord( $user->getNavn(), $user->getEpost(), $passord );
        }
        
        $omrade = Omrade::getByType( 
            $_POST['omrade_type'], 
            (Int) $_POST['omrade_id'], 
            (Int) get_option('season')
        );
        $administrator = new Administrator( $user->getId() );
        WriteOmrade::leggTilAdmin( $omrade, $administrator );
        
        // Abonner på blog 1 - alle må det.
        Blog::leggTilBruker(1, $user->getId(), 'subscriber');
        
        // Send velkommen-epost
        if( !$created ) {
            WriteOmrade::sendVelkommenTilNyttOmrade( $user->getName(), $user->getEmail(), $omrade );
        }
        
        // Admin må abonnere på nyhetsbrevet vårt
        $arrangorliste = Arrangor::getList();
        $arrangorliste->addSubscriber(
            [
                'email_address' => $user->getEmail(),
                'first_name' => $user->getFirstName(),
                'last_name' => $user->getLastName(),
            ]
        );
        $mailchimp = new Mailchimp();
        $mailchimp->saveListChanges( $arrangorliste );

        // Tagg vår nye administrator
        $tags = [
            'UKMadmin',
            'UKM'.get_site_option('season'),
            $omrade->getType() == 'kommune' ? 'lokalkontakt' : 'fylkeskontakt',
            $omrade->getId(),
            $omrade->getNavn(),
        ];

        foreach( $tags as $index => $tag ) {
            $tags[ $index ] = Mailchimp::sanitizeTag( $tag );
        }
        try {
            Arrangor::tag( $user->getEmail(), $tags );
        } catch( Exception $e ) {
            error_log('MAILCHIMP ERROR');
            error_log('MSG: '. $e->getMessage());
            error_log('CODE: '. $e->getCode());
            UKMnettverket::getFlash()->add(
                'info',
                'En feil med nyhetsbrevet kan gjøre at '. $user->getName() .' ikke får velkomst-epost. '.
                'Vi jobber med å rette feilen'
            );
        }

        UKMnettverket::getFlash()->add(
            'success',
            ($created ? 
                'Bruker er oppprettet for '. $user->getName() .' og '
                : $user->getName() . ' er ') .
            'lagt til som administrator for ' . $omrade->getNavn()
        );
    }
}
