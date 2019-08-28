<?php

require_once('UKM/fylker.class.php');

UKMNettverket::addViewData('fylke', fylker::getById($_GET['omrade']));

if( $_SERVER['REQUEST_METHOD'] == 'POST' ) {
    require_once('arrangement-manage.controller.php');
}