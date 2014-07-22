<?php

if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
Proper structuring of message data fields in case of animation and theme key-value
Changed the array keys
title -> headline
promo_image -> icon
*/
global $wpdb, $wp_rewrite;

$results = $wpdb->get_results( "SELECT * FROM {$wpdb->postmeta} WHERE meta_key LIKE 'icegram_message_%'" );

foreach ( $results as $result ) {

    $message_data = unserialize( $result->meta_value );
    if( is_array( $message_data ) && !empty( $message_data ) ) {        
        $message_type = $message_data['type'];
        if( isset( $message_data['theme'] ) && is_array( $message_data['theme'] ) && !empty( $message_data['theme'][$message_type] ) ) {
            $message_data['theme'] = $message_data['theme'][$message_type];
        }
        if( isset( $message_data['animation'] ) && is_array( $message_data['animation'] ) ) {
            if( !empty( $message_data['animation'][$message_type] ) ) {
                $message_data['animation'] = $message_data['animation'][$message_type];
            } else {
                unset( $message_data['animation'] );
            }
        }
        if( isset( $message_data['title'] ) ) {
            $message_data['headline'] = $message_data['title'];
            unset( $message_data['title'] );
        }
        if( isset( $message_data['promo_image'] ) ) {
            $message_data['icon'] = $message_data['promo_image'];
            unset( $message_data['promo_image'] );
        }
        update_post_meta( $result->post_id, $result->meta_key, $message_data );
    }

}

// Change post_type for messages and campaigns
$old_post_types = array('message', 'campaign');
foreach ($old_post_types as $type) {
    
    $q = 'numberposts=-1&post_status=any&post_type='.$type;
    $items = get_posts($q);
    foreach ($items as $item) {
        $update['ID'] = $item->ID;
        $update['post_type'] = "ig_{$type}";
        wp_update_post( $update );
    }

    /*
    $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_type = REPLACE(post_type, %s, %s) 
                                   WHERE post_type LIKE %s", $type, 'ig_'.$type, $type ) );
    $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET guid = REPLACE(guid, %s, %s) 
                                   WHERE guid LIKE %s", "post_type={$type}", "post_type=ig_{$type}", "%post_type={$type}%" ) );

    $wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET guid = REPLACE(guid, %s, %s) 
                                   WHERE guid LIKE %s", "/{$type}/", "/ig_{$type}/", "%/{$type}/%" ) );
    */                               
}

if ($wp_rewrite) {
    $wp_rewrite->flush_rules();
}

update_option( 'icegram_db_version', '1.2' );