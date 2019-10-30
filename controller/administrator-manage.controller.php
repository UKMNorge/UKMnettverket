<?php

use UKMNorge\API\Mailchimp\Liste\Arrangor;
use UKMNorge\API\Mailchimp\Mailchimp;
use UKMNorge\API\Mailchimp\Subscriber;
use UKMNorge\Kommunikasjon\Epost;
use UKMNorge\Kommunikasjon\Mottaker;
use UKMNorge\Wordpress\User;
use UKMNorge\Wordpress\WriteUser;
use UKMNorge\Nettverk\Administrator;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Nettverk\WriteOmrade;
use UKMNorge\Rapporter\Template\Write;
use UKMNorge\Twig\Twig;
use UKMNorge\Wordpress\Blog;

require_once('UKM/Autoloader.php');

/**
 *  FJERN ADMINISTRATOR
 */
if (isset($_GET['removeAdmin'])) {
    $omrade = Omrade::getByType(
        $_GET['type'],
        (int) $_GET['omrade'],
        (int) get_option('season')
    );
    $admin = $omrade->getAdministratorer()->get((int) $_GET['removeAdmin']);
    $res = WriteOmrade::fjernAdmin($omrade, $admin);
    if ($res) {
        UKMnettverket::getFlash()->add(
            'success',
            $admin->getUser()->getNavn() . ' er fjernet som administrator for ' . $omrade->getNavn()
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
        if (!User::erAktiv($user->getId())) {
            WriteUser::aktiver($user);
            $passord = WriteUser::genererPassord();
            WriteUser::setPassord($user, $passord);
            WriteUser::sendNyttPassord($user->getNavn(), $user->getEpost(), $passord);
        }

        $omrade = Omrade::getByType(
            $_POST['omrade_type'],
            (int) $_POST['omrade_id'],
            (int) get_option('season')
        );
        $administrator = new Administrator($user->getId());
        WriteOmrade::leggTilAdmin($omrade, $administrator);

        // Abonner på blog 1 - alle må det.
        Blog::leggTilBruker(1, $user->getId(), 'subscriber');

        // Send velkommen-epost hvis brukeren er oppdatert
        // Hvis brukeren er deaktivert vil det også sendes en e-post med nytt passord automatisk
        if (!$created) {
            WriteOmrade::sendVelkommenTilNyttOmrade($user->getName(), $user->getEmail(), $omrade);
        }

        // Opprett Subscriber-instance for administrator
        $subscriber = Subscriber::createFromDetails(
            $user->getEmail(),
            $user->getFirstName(),
            $user->getLastName()
        );

        try {
            // Legg til tags
            $tags_to_add = [
                'UKMadmin',
                'UKM' . get_site_option('season'),
                ($omrade->getType() == 'kommune' ? 'lokalkontakt' : 'fylkeskontakt'),
                $omrade->getId(),
                $omrade->getNavn(),
            ];
            // Abonner på Arrangør-lista
            Arrangor::subscribe($subscriber);
            $tags = Arrangor::addTags($subscriber, $tags_to_add );
            // Håndter tag-error
            if ($tags->hasError()) {
                $message = '';
                foreach( $tags->getError() as $error ) {
                    $message .= $error->getMessage() .', ';
                }
                throw new Exception( rtrim( $message, ', ') );
            }
        } catch (Exception $e) {
            #Twig::addPath('UKM/Nettverket/twig/');
            $epost = Epost::fraSupport();
            $epost->leggTilMottaker( Mottaker::fraEpost('support@ukm.no','UKM Support' ) );
            $epost->leggTilBlindkopi( Mottaker::fraEpost('marius@ukm.no','Marius Mandal') );
            $epost->setEmne('Feil tagget administrator');
            $epost->setMelding( 
                Twig::render(
                    'epost_tag_feilet.html.twig',
                    [
                        'epost' => $user->getEmail(),
                        'tags_to_add' => join(', ', $tags_to_add),
                        'feilmelding' => rtrim( $e->getMessage(), ', ')
                    ]
                )
            );
            $epost->send();

            // Håndter 
            UKMnettverket::getFlash()->add(
                'info',
                'En feil med nyhetsbrevet kan gjøre at ' . $user->getName() . ' ikke får velkomst-epost. ' .
                'Vi jobber med å rette feilen. Systemet sa: '. $e->getMessage()
            );
        }

        UKMnettverket::getFlash()->add(
            'success',
            ($created ?
                'Bruker er oppprettet for ' . $user->getName() . ' og '
                : $user->getName() . ' er ') .
                'lagt til som administrator for ' . $omrade->getNavn()
        );
    }
}
