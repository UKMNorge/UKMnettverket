<?php

use UKMNorge\Arrangement\Write;
use UKMNorge\Geografi\Fylker;
use UKMNorge\Geografi\Kommune;
use UKMNorge\Log\Logger;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Wordpress\Blog;
use UKMNorge\Arrangement\Kontaktperson\Write as WriteKontakt;

require_once('UKM/Autoloader.php');

// STEG 1 Vi mangler basis-verdier
if (empty($_POST['type']) || empty($_POST['pamelding'])) {
    /** MANGLENDE VERDIER  **/
    if (empty($_POST['type'])) {
        UKMnettverket::getFlashbag()->add(
            'danger',
            'Du må velge type arrangement for å komme videre'
        );
    }

    if (empty($_POST['pamelding'])) {
        UKMnettverket::getFlashbag()->add(
            'danger',
            'Du må velge type påmelding for å komme videre'
        );
    }
}
// VI HAR ALLE VERIDER
elseif (isset($_POST['path'])) {
    /** IKKE STØTTEDE VERDIER **/
    if ($_POST['type'] == 'arrangement') {
        throw new Exception(
            'BEKLAGER, vi støtter ikke andre typer arrangement for øyeblikket. Det kommer snart'
        );
    }

    if ($_POST['pamelding'] == 'lukket') {
        throw new Exception(
            'BEKLAGER, vi støtter ikke påmelding med krav for øyeblikket. Det kommer snart'
        );
    }

    if ($_GET['type'] == 'fylke' && $_POST['pamelding'] == 'apen') {
        throw new Exception(
            'BEKLAGER, vi støtter ikke påmelding for fylkesarrangementer enda, men det kommer snart!'
        );
    }

    // Vi har alt, opprett blogg
    require_once('UKM/write_monstring.class.php');
    require_once('UKM/write_kontakt.class.php');


    if ($_GET['type'] == 'fylke') {
        $geografi = Fylker::getById($_GET['omrade']);
    } elseif ($_GET['type'] == 'kommune') {
        $geografi = [new Kommune($_GET['omrade'])];
    } else {
        throw new Exception('Mangler info for å definere geografi');
    }

    Logger::initWP(0);

    // Opprett arrangement
    $arrangement = Write::create(
        $_GET['type'],                              // Type mønstring
        $_GET['omrade'],                             // Eier
        (int) get_site_option('season'),            // Sesong
        $_POST['navn'],                             // Navn
        $geografi,             // Geografisk tilhørighet (hm..)
        $_POST['path']
    );


    // Legg til alle kommuner i fellesmønstringen
    if (isset($_POST['kommuner']) && is_array($_POST['kommuner']) && sizeof($_POST['kommuner']) > 0) {
        $fellesmonstring = true;
        foreach ($_POST['kommuner'] as $kommune_id) {
            $kommune = new Kommune($kommune_id);
            $arrangement->getKommuner()->leggTil($kommune);
        }
    } else {
        $fellesmonstring = false;
    }

    if (isset($_POST['start'])) {
        $arrangement->setStart(
            Write::inputToDateTime(
                $_POST['start'],
                '18:00'
            )
        );
    }

    if (isset($_POST['pamelding'])) {
        if ($_POST['pamelding'] == 'betinget') {
            $arrangement->setPamelding('betinget');
            $arrangement->setHarVideresending(false);
        } else if ($_POST['pamelding'] == 'apen') {
            $arrangement->setPamelding('apen');
            $arrangement->setHarVideresending(false);
        } else {
            $arrangement->setPamelding('ingen');
        }
    }

    $arrangement->setSynlig($_POST['synlig'] == 'true');
    Write::save($arrangement);

    $omrade = new Omrade($_GET['type'], $_GET['omrade']);

    // Hvis område ikke har en blogg, legg arrangementet hit
    if ($omrade->getArrangementer(get_site_option('season'))->getAntall() == 1 && !$fellesmonstring) {
        try {
            $blog_id = Blog::getIdByPath($_POST['path']);
            Blog::oppdaterFraArrangement($blog_id, $arrangement);
        } catch (Exception $e) {
            // 172007 = fant ingen blogg 
            // (som kan skje hvis vi ikke har opprettet blogger for alle kommuner og fylker)
            if ($e->getCode() == 172007) {
                $blog_id = Blog::opprettForArrangement(
                    $arrangement,
                    $_POST['path']
                );
            } else {
                throw $e;
            }
        }
    }
    // Opprett blogg
    else {
        $blog_id = Blog::opprettForArrangement(
            $arrangement,
            $_POST['path']
        );
    }

    // Legg til admins
    foreach ($omrade->getAdministratorer()->getAll() as $admin) {
        Blog::leggTilBruker($blog_id, $admin->getId(), 'editor');

        $kontakt = WriteKontakt::create(
            $admin->getUser()->getFirstName(),
            $admin->getUser()->getLastName(),
            $admin->getUser()->getPhone()
        );
        $kontakt->setEpost($admin->getUser()->getEmail());
        WriteKontakt::save($kontakt);

        $arrangement->getKontaktpersoner()->leggTil($kontakt);
    }
    Write::save($arrangement);

    UKMnettverket::addViewData('arrangement', $arrangement);
    UKMnettverket::addViewData('blog_path', $_POST['path']);
    UKMnettverket::addViewData('omrade', $omrade);
    UKMnettverket::setAction('arrangement-added');
}
