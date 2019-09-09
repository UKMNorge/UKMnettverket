<?php

use UKMNorge\Nettverk\Omrade;
use UKMNorge\Wordpress\Blog;

require_once('UKM/Wordpress/Blog.php');
require_once('UKM/logger.class.php');

// STEG 1 Vi mangler basis-verdier
if( empty( $_POST['type'] ) || empty( $_POST['pamelding'] ) ) {
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
elseif (isset($_POST['path']))
{
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


    // Opprett arrangement
    $arrangement = write_monstring::create(
        $_GET['type'],                              // Type mønstring
        $_GET['omrade'],                             // Eier
        (int) get_site_option('season'),            // Sesong
        $_POST['navn'],                             // Navn
        fylker::getById($_GET['omrade']),             // Geografisk tilhørighet (hm..)
        $_POST['path']
    );

    UKMlogger::initWP( $arrangement->getId() );

    $arrangement->setStart(
        write_monstring::inputToDateTime( 
            $_POST['start'],
            '18:00'
        )
    );
    write_monstring::save( $arrangement );

    // Opprett blogg
    $blog_id = Blog::opprettForArrangement(
        $arrangement,
        $_POST['path']
    );

    // Legg til admins
    $omrade = new Omrade( $_GET['type'], $_GET['omrade'] );
    foreach( $omrade->getAdministratorer()->getAll() as $admin ) {
        Blog::leggTilBruker( $blog_id, $admin->getId(), 'administrator' );

        $kontakt = write_kontakt::create(
            $admin->getUser()->getFirstName(),
            $admin->getUser()->getLastName(),
            $admin->getUser()->getPhone()
        );
        $kontakt->setEpost( $admin->getUser()->getEmail() );
        write_kontakt::save( $kontakt );

        $arrangement->getKontaktpersoner()->leggTil( $kontakt );
    }
    write_monstring::save( $arrangement );

    UKMnettverket::addViewData('arrangement', $arrangement);
    UKMnettverket::addViewData('blog_path', $_POST['path']);
    UKMnettverket::addViewData('omrade', $omrade);
    UKMnettverket::setAction('arrangement-added');
}