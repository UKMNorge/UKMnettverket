<?php

use UKMNorge\Arrangement\Arrangement;
use UKMNorge\Arrangement\Load;
use UKMNorge\Arrangement\Write;
use UKMNorge\Wordpress\Blog;
header('Content-Type: application/json');

require_once('UKM/Autoloader.php');

try {    
    $arrangement = new Arrangement((int)$_POST['arrangement']);
    $arrangement->setPamelding('apen');
    Write::save($arrangement);
    UKMnettverket::addResponseData(
        [
            'success' => true,
            'message' => 'Påmelding er aktivert, og du kan nå melde av innslagene'
        ]
    );
} catch( Exception $e ) {
    UKMnettverket::addResponseData(
        [
            'success' => false,
            'message' => 'Beklager, klarte ikke å slå på påmelding'
        ]
    );
}