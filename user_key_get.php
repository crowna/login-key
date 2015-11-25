<?php
error_reporting(0);
/**
 * user_key_get.php
 * 150220 jc
 *
 * The purpose of this file:
 * - to respond via ajax for popup GUI
 * - to create a user_key if it doesn't exist
 * - enable the user_key to be downloaded as a file (send to user's email)
 * - enable resetting of the user_key (send to user's email)
 * - enable switching off of the user_key system for this user (send confirmation to user's email)
 */
//MySQL DB settings
include_once('../../../wp-config.php');


$filename = 'key.p4h';
header("Cache-Control: public");
header("Content-Description: File Transfer");
header("Content-Disposition: attachment; filename=$filename");
header("Content-Type: application/octet-stream; ");
header("Content-Transfer-Encoding: binary");

$uk_other = $_GET['uk_other'] ;
$user = wp_get_current_user() ;
if ( is_numeric( $uk_other ) && $uk_other !== false ){
    $user = get_userdata( $uk_other );
}

if (is_user_logged_in()) {
    $userkey = apply_filters('get_login_key_userkey',  get_user_option('user_key', $user->ID ));
    echo  $userkey ;
}else {
    $no_access = apply_filters('get_login_key_no_access',  "no access" ); //note default behaviour relies on this for frontend user response
    echo $no_access ;
}