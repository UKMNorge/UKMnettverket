<?php

use UKMNorge\Nettverk\Administrator;
use UKMNorge\Wordpress\WriteUser;

if(!isset( $_POST['adminId'])) {
    die('adminId må sendes som argument');
}

$adminId = $_POST['adminId'];
$administrator = new Administrator($adminId);

$user = $administrator->getUser();

$file_name = $_FILES['file']['name'];
$file_temp = $_FILES['file']['tmp_name'];

$upload_dir = wp_upload_dir();
$image_data = file_get_contents( $file_temp );
$filename = basename( $file_name );
$filetype = wp_check_filetype($file_name);
$filename = time().'.'.$filetype['ext'];

if ( wp_mkdir_p( $upload_dir['path'] ) ) {
    $file = $upload_dir['path'] . '/' . $filename;
}
else {
    $file = $upload_dir['basedir'] . '/' . $filename;
}

file_put_contents( $file, $image_data );
$wp_filetype = wp_check_filetype( $filename, null );
$attachment = array(
    'post_mime_type' => $wp_filetype['type'],
    'post_title' => sanitize_file_name( $filename ),
    'post_content' => '',
    'post_status' => 'inherit'
);

$attach_id = wp_insert_attachment( $attachment, $file );
require_once( ABSPATH . 'wp-admin/includes/image.php' );
$attach_data = wp_generate_attachment_metadata( $attach_id, $file );
wp_update_attachment_metadata( $attach_id, $attach_data );

$url = wp_get_attachment_url($attach_id);

// Lagrer bild på User
$user->setBilde($url);
WriteUser::InsertOrUpdateBilde($user);