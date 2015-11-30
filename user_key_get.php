<?php
error_reporting(0);
/**
 * user_key_get.php
 * 150220 jc
 *
 * The purpose of this file:
 * - enable the user_key to be downloaded as a file
 */
//MySQL DB settings
include_once('../../../wp-config.php');

if (is_user_logged_in()) { //will only download if user is logged in
    $filename = 'key.p4h';
    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header("Content-Disposition: attachment; filename=$filename");
    header("Content-Type: application/octet-stream; ");
    header("Content-Transfer-Encoding: binary");

    $uk_other = $_GET['uk_other'] ;
    $user = wp_get_current_user() ;
    if ( is_numeric( $uk_other ) && $uk_other !== false && current_user_can('edit_users') ){ //only admin can download the key of others
        $user = get_userdata( $uk_other );
    }

    $userkey = apply_filters('get_login_key_userkey',  get_user_option('user_key', $user->ID ));
    echo  $userkey ;
}else {
    $no_access = apply_filters('get_login_key_no_access',  "no access" ); //note default behaviour relies on this for frontend user response
    echo $no_access ;
}