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

$default_opt_name = 'lk_default_start';
delete_option( $default_opt_name );
delete_site_option( $default_opt_name );

$disable_opt_name = 'lk_disable';
delete_option( $disable_opt_name );
delete_site_option( $disable_opt_name );


//delete related user data
GLOBAL $wpdb;
$sql = 'DELETE FROM `' . $wpdb->prefix . 'usermeta` WHERE `meta_key`="user_key"';
$wpdb->query( $sql ) ;