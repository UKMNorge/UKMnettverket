<?php

use UKMNorge\Wordpress\Blog;

require_once('UKM/Wordpress/Blog.php');

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
else {
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
    if (isset($_POST['path'])) {

        require_once('UKM/write_monstring.class.php');

        // Opprett arrangement
        $arrangement = write_monstring::create(
            $_GET['type'],                              // Type mønstring
            $_GET['omrade'],                             // Eier
            (int) get_site_option('season'),            // Sesong
            $_POST['navn'],                             // Navn
            fylker::getById($_GET['omrade']),             // Geografisk tilhørighet (hm..)
            $_POST['path']
        );

        $arrangement->setStart(
            write_monstring::inputToDateTime( 
                $_POST['start'],
                '18:00'
            )
        );
        write_monstring::save( $arrangement );

        // Opprett blogg
        Blog::opprettForArrangement(
            $arrangement,
            $_POST['path']
        );

        UKMnettverket::setAction('arrangement-administratorer');
    }
    // Vi mangler nettside-spørsmål
    else {
        UKMnettverket::setAction('arrangement-website');

        UKMnettverket::addViewData(
            'path_forslag',
            Blog::sanitizePath($_POST['navn'])
        );
    }
}