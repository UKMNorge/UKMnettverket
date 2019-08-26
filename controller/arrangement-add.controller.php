<?php

require_once('UKM/fylker.class.php');

UKMNettverket::addViewData('fylke', fylker::getById($_GET['omrade']));