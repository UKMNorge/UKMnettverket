<?php

use UKMNorge\Nettverk\Administrator;
use UKMNorge\Wordpress\WriteUser;


if(!isset( $_POST['adminId'])) {
    die('adminId må sendes som argument');
}

$adminId = $_POST['adminId'];
$administrator = new Administrator($adminId);
$user = $administrator->getUser();

// Lagrer bild på User
$user->setBilde(null);
WriteUser::InsertOrUpdateBilde($user);