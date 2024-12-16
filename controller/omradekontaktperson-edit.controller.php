<?php

use UKMNorge\OAuth2\ArrSys\HandleAPICallWithAuthorization;
use UKMNorge\Nettverk\Omrade;
use UKMNorge\Nettverk\OmradeKontaktperson;
use UKMNorge\Nettverk\WriteOmradeKontaktperson;
use UKMNorge\Nettverk\OmradeKontaktpersoner;
use UKMNorge\OAuth2\ArrSys\AccessControlArrSys;

require_once('UKM/Autoloader.php');


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $omradeId = HandleAPICallWithAuthorization::getArgumentBeforeInit('omradeId', 'POST');
    $omradeType = HandleAPICallWithAuthorization::getArgumentBeforeInit('omradeType', 'POST');
    if(($omradeType != 'fylke' && $omradeType != 'kommune') || $omradeType == null) {
        // Send error if the area type is not 'fylke' or 'kommune'
        HandleAPICallWithAuthorization::sendError("Støtter område type 'fylke' eller 'kommune'", 400);
    }

    $tilgang = $omradeType == 'fylke' ? 'fylke' : 'kommune';
    $tilgangAttribute = $omradeId;

    $handleCall = new HandleAPICallWithAuthorization(['okpId', 'fornavn', 'mobil', 'etternavn', 'epost'], ['beskrivelse', 'deletedProfileImage'], ['GET', 'POST'], false, false, $tilgang, $tilgangAttribute);

    $id = $handleCall->getArgument('okpId');
    $fornavn = $handleCall->getArgument('fornavn');
    $etternavn = $handleCall->getArgument('etternavn');
    $epost = $handleCall->getArgument('epost');
    $mobil = $handleCall->getArgument('mobil');
    $beskrivelse = $handleCall->getOptionalArgument('beskrivelse') ?? '';
    $deletedProfileImage = $handleCall->getOptionalArgument('deletedProfileImage') == 'true' ? true : false;
    
    // Check mobil
    if(!preg_match('/^\d{8}$/', $mobil)) {
        HandleAPICallWithAuthorization::sendError('Mobilnummeret må være 8 siffer og kun tall', 400);
    }

    try {
        $okp = OmradeKontaktpersoner::getByMobil($mobil);
        $okp->setFornavn($fornavn);
        $okp->setEtternavn($etternavn);
        $okp->setEpost($epost);
        $okp->setBeskrivelse($beskrivelse);
        uploadProfileImage($okp, $deletedProfileImage);
        WriteOmradeKontaktperson::editOmradekontaktperson($okp);
    } catch(Exception $e) {
        HandleAPICallWithAuthorization::sendError($e->getMessage(), 400);
    }


    echo '<script>window.location.href = "?page=UKMnettverket_'. $omradeType .'&omrade='. $omradeId .'&type='. $omradeType .'";</script>';
    exit();
}
else {
    $mobil = HandleAPICallWithAuthorization::getArgumentBeforeInit('mobil', 'GET');
    $okp = OmradeKontaktpersoner::getByMobil($mobil);

    $omrade = new Omrade($okp->getEierOmradeType(), $okp->getEierOmradeId());
    if(!AccessControlArrSys::hasOmradeAccess($omrade)) {
        UKMnettverket::addViewData('tilgang', false);
    }
    else {
        showUser($okp);
    }
}

function showUser(OmradeKontaktperson $okp) {
    $omrade = new Omrade($okp->getEierOmradeType(), $okp->getEierOmradeId());

    UKMnettverket::addViewData('tilgang', true);
    UKMnettverket::addViewData('omradekontaktperson', $okp);
    UKMnettside::addViewData('omrade', $omrade);

}

    

// Returns the URL of the uploaded image
function uploadProfileImage(OmradeKontaktperson $okp, bool $deletedProfileImage) : void {    

    // Profilbildet er fjernet (ingen profilbilde)
    if($deletedProfileImage && $_FILES['profile_picture']['size'] == 0) {
        $okp->setProfileImageUrl(null);
        return;
    }

    $file_name = $_FILES['profile_picture']['name'];
    $file_temp = $_FILES['profile_picture']['tmp_name'];

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
    $okp->setProfileImageUrl($url);
}