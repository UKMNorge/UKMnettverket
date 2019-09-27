<?php

use UKMNorge\Nettverk\Omrade;
use UKMNorge\Wordpress\Blog;

require_once('UKM/Wordpress/Blog.php');
require_once('UKM/logger.class.php');

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

    // Vi har alt, opprett blogg
    require_once('UKM/write_monstring.class.php');
    require_once('UKM/write_kontakt.class.php');


    if ($_GET['type'] == 'fylke') {
        $geografi = fylker::getById($_GET['omrade']);
    } elseif ($_GET['type'] == 'kommune') {
        $geografi = [new kommune($_GET['omrade'])];
    } else {
        throw new Exception('Mangler info for å definere geografi');
    }

    UKMlogger::initWP(0);

    // Opprett arrangement
    $arrangement = write_monstring::create(
        $_GET['type'],                              // Type mønstring
        $_GET['omrade'],                             // Eier
        (int) get_site_option('season'),            // Sesong
        $_POST['navn'],                             // Navn
        $geografi,             // Geografisk tilhørighet (hm..)
        $_POST['path']
    );


    // Legg til alle kommuner i fellesmønstringen
    if (isset($_POST['kommuner']) && is_array($_POST['kommuner'])) {
        $fellesmonstring = true;
        foreach( $_POST['kommuner'] as $kommune_id ) {
            $kommune = new kommune( $kommune_id );
            $arrangement->getKommuner()->leggTil( $kommune );
        }
    } else {
        $fellesmonstring = false;
    }

    $arrangement->setStart(
        write_monstring::inputToDateTime(
            $_POST['start'],
            '18:00'
        )
    );

    $arrangement->setSynlig( $_POST['synlig'] == 'true' );
    write_monstring::save($arrangement);

    $omrade = new Omrade($_GET['type'], $_GET['omrade']);

    // Hvis område ikke har en blogg, legg arrangementet hit
    if (!$omrade->getArrangementer(get_site_option('season'))->har() && !$fellesmonstring) {
        $current_blog = Blog::getIdByPath($_POST['path']);
        Blog::oppdaterFraArrangement( $current_blog, $arrangement );
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

        $kontakt = write_kontakt::create(
            $admin->getUser()->getFirstName(),
            $admin->getUser()->getLastName(),
            $admin->getUser()->getPhone()
        );
        $kontakt->setEpost($admin->getUser()->getEmail());
        write_kontakt::save($kontakt);

        $arrangement->getKontaktpersoner()->leggTil($kontakt);
    }
    write_monstring::save($arrangement);

    UKMnettverket::addViewData('arrangement', $arrangement);
    UKMnettverket::addViewData('blog_path', $_POST['path']);
    UKMnettverket::addViewData('omrade', $omrade);
    UKMnettverket::setAction('arrangement-added');
}