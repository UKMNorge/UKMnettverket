<?php

use UKMNorge\Nettverk\Omrade;

$omrade = new Omrade($_GET['type'], (Int)$_GET['omrade']);


$email_kommune = [];
foreach( $omrade->getFylke()->getKommuner()->getAll() as $kommune ) {
    $kommune_omrade = new Omrade('kommune', $kommune->getId());
    foreach( $kommune_omrade->getAdministratorer()->getAll() as $admin ) {
        $email_kommune[] = $admin->getUser()->getEmail();
    }
}
$email_kommune = array_unique($email_kommune);

$email_fylke = [];
foreach( $omrade->getAdministratorer()->getAll() as $admin ) {
    $email_fylke[] = $admin->getUser()->getEmail();
}
$email_fylke = array_unique($email_fylke);


UKMnettverket::addViewData('omrade', $omrade);
UKMnettverket::addViewData('email_kommune', $email_kommune);
UKMnettverket::addViewData('email_fylke', $email_fylke);