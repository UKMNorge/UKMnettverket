<?php

use UKMNorge\Nettverk\Omrade;

require_once('UKM/fylker.class.php');
require_once('UKM/Nettverk/Omrade.class.php');


UKMnettverket::addViewData(
    'omrade', 
    new Omrade(
        $_GET['type'], 
        (Int)$_GET['omrade']
    )
);
switch( $_GET['type'] ) {
    case 'fylke':
        UKMnettverket::addViewData('fylke', fylker::getById($_GET['omrade']));
        break;
    case 'kommune':
        UKMnettverket::addViewData('kommune',new kommune($_GET['omrade']));
    break;
}

if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    require_once('arrangement-manage.controller.php');
}