<?php

use UKMNorge\API\Mailchimp\Liste\Arrangor;
use UKMNorge\API\Mailchimp\Mailchimp;
use UKMNorge\API\Mailchimp\Subscriber;
use UKMNorge\API\Mailchimp\Tag;
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
    try{
        $admin = $omrade->getAdministratorer()->get((int) $_GET['removeAdmin']);
    }
    catch(Exception $e) {
        UKMnettverket::getFlash()->error('Administrator finnes ikke!');
    }
    $res = WriteOmrade::fjernAdmin($omrade, $admin);
    if ($res) {
        UKMnettverket::getFlash()->add(
            'success',
            $admin->getUser()->getNavn() . ' er fjernet som administrator for ' . $omrade->getNavn()
        );
    }

    // Fjern også admin for alle eksisterende arrangementer
    try {
        WriteOmrade::fjernAdminFraAlleArrangementer(
            $omrade,
            $admin,
            (int) get_site_option('season')
        );
    } catch (Exception $e) {
        UKMnettverket::getFlash()->error(
            'Systemet fikk ikke fjernet administratoren fra alle områdets arrangementer. ' .
                'Kontakt <a href="mailto:support@ukm.no">support@ukm.no</a>. Systemet sa: ' .
                $e->getMessage()
        );
    }
}


/**
 *  LEGG TIL ADMINISTRATOR
 */
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['email'])) {
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
                Tag::sanitize('UKMadmin'),
                Tag::sanitize('UKM' . get_site_option('season')),
                Tag::sanitize(($omrade->getType() == 'kommune' ? 'lokalkontakt' : 'fylkeskontakt')),
                Tag::sanitize($omrade->getId()),
                Tag::sanitize($omrade->getNavn()),
            ];
            
            if( UKM_HOSTNAME == 'ukm.dev') {
                throw new Exception(
                    'Tagger ikke admins i UKM.dev'
                );
            }
            
            // Abonner på Arrangør-lista
            Arrangor::subscribe($subscriber);
            $tags = Arrangor::addTags($subscriber, $tags_to_add);
            // Håndter tag-error
            if ($tags->hasError()) {
                $message = '';
                foreach ($tags->getError() as $error) {
                    $message .= $error->getMessage() . ', ';
                }
                throw new Exception(rtrim($message, ', '));
            }
        } catch (Exception $e) {
            Twig::addPath(dirname(stream_resolve_include_path('UKM/Nettverk/twig/epost_tag_feilet.html.twig')));
            $epost = Epost::fraSupport();
            $epost->leggTilMottaker(Mottaker::fraEpost('support@ukm.no', 'UKM Support'));
            $epost->setEmne('Feil tagget administrator');
            $epost->setMelding(
                Twig::render(
                    'epost_tag_feilet.html.twig',
                    [
                        'epost' => $user->getEmail(),
                        'tags_to_add' => join(', ', $tags_to_add),
                        'feilmelding' => rtrim($e->getMessage(), ', ')
                    ]
                )
            );
            $epost->send();

            // Håndter 
            UKMnettverket::getFlash()->add(
                'info',
                'En feil med nyhetsbrevet kan gjøre at ' . $user->getName() . ' ikke får velkomst-epost. ' .
                    'Vi jobber med å rette feilen. Systemet sa: ' . $e->getMessage()
            );
        }

        UKMnettverket::getFlash()->add(
            'success',
            ($created ?
                'Bruker er oppprettet for ' . $user->getName() . ' og '
                : $user->getName() . ' er ') .
                'lagt til som administrator for ' . $omrade->getNavn()
        );

        // Legg også til admin for alle eksisterende arrangementer
        try {
            WriteOmrade::leggTilAdminIAlleArrangementer(
                $omrade,
                $administrator,
                (int) get_site_option('season')
            );
        } catch (Exception $e) {
            UKMnettverket::getFlash()->error(
                'Systemet fikk ikke lagt til administratoren i alle områdets arrangementer. ' .
                    'Kontakt <a href="mailto:support@ukm.no">support@ukm.no</a>. Systemet sa: ' .
                    $e->getMessage()
            );
        }
    }
}
