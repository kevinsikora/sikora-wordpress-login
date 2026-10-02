<?php
/**
 * Uninstall cleanup for Sikora WordPress Login.
 *
 * Fired when the plugin is deleted via the WordPress admin.
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}

delete_option( 'sikora_login_bg_image' );
delete_option( 'sikora_login_bg_color' );
delete_option( 'sikora_login_disable_password_reset' );
delete_transient( 'sikora_login_invalid_bg_notice' );

// Multisite: remove options from each site.
if ( is_multisite() ) {
    $site_ids = get_sites( array( 'fields' => 'ids', 'number' => 0 ) );

    foreach ( $site_ids as $site_id ) {
        switch_to_blog( (int) $site_id );
        delete_option( 'sikora_login_bg_image' );
        delete_option( 'sikora_login_bg_color' );
        delete_option( 'sikora_login_disable_password_reset' );
        delete_transient( 'sikora_login_invalid_bg_notice' );
        restore_current_blog();
    }
}
