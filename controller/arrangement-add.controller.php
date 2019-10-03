<?php

use UKMNorge\Nettverk\Omrade;

require_once('UKM/Autoloader.php');

UKMnettverket::addViewData('sesong', get_site_option('season'));

UKMnettverket::addViewData(
    'omrade', 
    new Omrade(
        $_GET['type'], 
        (Int)$_GET['omrade']
    )
);
switch( $_GET['type'] ) {
    case 'fylke':
        UKMnettverket::addViewData('fylke', Fylker::getById($_GET['omrade']));
        break;
    case 'kommune':
        UKMnettverket::addViewData('kommune',new Kommune($_GET['omrade']));
    break;
}

if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    require_once('arrangement-manage.controller.php');
}