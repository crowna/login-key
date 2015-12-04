<?php
/**
 * Created by PhpStorm.
 * User: crowe
 * Date: 1/12/2015
 * Time: 22:25
 */
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

$lk_ops = array('lk_allow_shortcode','lk_default_start','lk_disable');

for ($i = 0; $i < count($lk_ops); $i++) {
    delete_option( $lk_ops[$i] );
    delete_site_option( $lk_ops[$i] );
}


//delete related user data
GLOBAL $wpdb;
$sql = 'DELETE FROM `' . $wpdb->prefix . 'usermeta` WHERE `meta_key`="user_key"';
$wpdb->query( $sql ) ;