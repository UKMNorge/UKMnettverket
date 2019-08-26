<?php

use UKMNorge\Nettverk\Administrator;

require_once('UKM/fylker.class.php');
require_once('UKM/Nettverk/Administrator.class.php');

if( is_user_admin() ) {
    require_once('UKM/Nettverk/Administrator.class.php');
    $current_admin = new Administrator( get_current_user_id() );

    $omrader = $current_admin->getOmrader();
} else {
    $omrader = [];
    foreach( fylker::getAll() as $fylke ) {
        $omrade = $fylke->getNettverkOmrade();
        $omrader[ $omrade->getId() ] = $omrade;
    }
}

UKMNettverket::addViewData('omrader', $omrader );

require_once('administrator-manage.controller.php');