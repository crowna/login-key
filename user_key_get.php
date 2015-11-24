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
//header("Content-Length: ". filesize("$filename").";");
header("Content-Disposition: attachment; filename=$filename");
header("Content-Type: application/octet-stream; ");
header("Content-Transfer-Encoding: binary");

if (is_user_logged_in()) {

    $userkey = get_user_option('user_key');

    echo $userkey ;

}else {
    echo "no access";
}
?>