<?php

use UKMNorge\Nettverk\Administrator;
use UKMNorge\Nettverk\Omrade;

require_once('UKM/fylker.class.php');
require_once('UKM/Nettverk/Administrator.class.php');

$type = str_replace(
    'UKMnettverket_',
    '',
    $_GET['page']
);
if (is_user_admin()) {
    require_once('UKM/Nettverk/Administrator.class.php');
    $current_admin = new Administrator(get_current_user_id());
    $omrader = $current_admin->getOmrader($type);
} else {
    $omrader = [];
    foreach (fylker::getAll() as $fylke) {
        $omrade = $fylke->getNettverkOmrade();
        $omrader[$omrade->getId()] = $omrade;
    }
}

if (isset($_GET['omrade']) && isset($_GET['type'])) {
    $current_omrade_id = $_GET['type'].'_'.$_GET['omrade'];
    // Brukeren er direkte admin for området (ligger i db-tabell)
    if( isset( $omrader[$current_omrade_id]) ) {
        UKMnettverket::setAction('omrade');
        UKMnettverket::addViewData('omrade', $omrader[$current_omrade_id]);
    }
    // Brukeren kan være indirekte admin for en kommune
    // ved å være admin for det overordnede fylket
    elseif( $_GET['type'] == 'kommune' ) {
        $requested_kommune = new kommune( $_GET['omrade'] );
        if( is_user_admin() ) {
            $omrader = $current_admin->getOmrader();
            // Brukeren er admin for fylket
            if( isset( $omrader['fylke_'. $requested_kommune->getFylke()->getId() ] ) ) {
                UKMnettverket::setAction('omrade');
                UKMnettverket::addViewData('omrade', new Omrade('kommune', $requested_kommune->getId()));
            }
        } elseif( is_network_admin() ) {
            UKMnettverket::setAction('omrade');
            UKMnettverket::addViewData('omrade', new Omrade('kommune', $requested_kommune->getId()));
        }
        // Hvis ikke, gjør ingenting - GUI vil gi meningsfylt tilbakemelding
    }
} elseif (sizeof($omrader) == 1) {
    UKMnettverket::setAction('omrade');
    UKMnettverket::addViewData('omrade', array_pop($omrader));
} else {
    UKMnettverket::addViewData('omrader', $omrader);
}

require_once('administrator-manage.controller.php');
