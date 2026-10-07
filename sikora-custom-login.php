<?php
/**
 * Plugin Name:       Sikora Custom Login
 * Description:       Customizes the WordPress admin dialog: sets a custom background image (chosen from the Media Library), hides the WordPress logo/link, and can optionally remove the "Lost your password?" link and password reset flow.
 * Version:           2.2.2
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            <a href="https://sikoracollective.com/">Sikora Collective</a>
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sikora-custom-login
 *
 * @package SikoraCustomLogin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Absolute filesystem path to the plugin directory, with trailing slash.
 *
 * @since 2.0.0
 * @var string
 */
define( 'SIKORA_LOGIN_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

/**
 * URL to the plugin directory, with trailing slash.
 *
 * @since 2.0.0
 * @var string
 */
define( 'SIKORA_LOGIN_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Plugin version string. Used as a fallback asset version when filemtime is unavailable.
 *
 * @since 2.0.0
 * @var string
 */
define( 'SIKORA_LOGIN_VERSION', '2.2.2' );

/**
 * Option name for the login business logo Media Library attachment ID.
 *
 * Stores an integer attachment ID (not a raw URL). Displayed in place of the
 * WordPress logo when set; when empty the logo area is omitted.
 *
 * @since 2.2.0
 * @var string
 */
define( 'SIKORA_LOGIN_LOGO_OPTION', 'sikora_login_logo_image' );

/**
 * Display size of the login logo area in pixels (matches WordPress core logo).
 *
 * @since 2.2.0
 * @var int
 */
define( 'SIKORA_LOGIN_LOGO_SIZE', 84 );

/**
 * Option name for the login background Media Library attachment ID.
 *
 * Stores an integer attachment ID (not a raw URL) so the URL is always resolved
 * server-side via {@see wp_get_attachment_url()}.
 *
 * @since 2.0.0
 * @var string
 */
define( 'SIKORA_LOGIN_BG_OPTION', 'sikora_login_bg_image' );

/**
 * Option name for the login background color.
 *
 * Stores a sanitized hex color (e.g. `#1d2327`) or an empty string when unset.
 * Only used when no background image is selected.
 *
 * @since 2.1.0
 * @var string
 */
define( 'SIKORA_LOGIN_BG_COLOR_OPTION', 'sikora_login_bg_color' );

/**
 * Default login background color.
 *
 * Stored on activation (and on the first request of an already-active install)
 * when no value exists yet, so new installs have a color from the start.
 *
 * @since 2.1.0
 * @var string
 */
define( 'SIKORA_LOGIN_BG_COLOR_DEFAULT', '#eaeaea' );

/**
 * Option name for whether password reset on wp-login.php is disabled.
 *
 * Value is `1` (reset blocked) or `0` (default, reset allowed).
 *
 * @since 2.0.0
 * @var string
 */
define( 'SIKORA_LOGIN_DISABLE_RESET_OPTION', 'sikora_login_disable_password_reset' );

/**
 * Transient name used to show an admin notice after clearing an invalid background.
 *
 * @since 2.0.0
 * @var string
 */
define( 'SIKORA_LOGIN_INVALID_BG_NOTICE', 'sikora_login_invalid_bg_notice' );

/**
 * Returns the capability required to manage plugin settings.
 *
 * @since 2.0.0
 *
 * @return string Capability slug.
 */
function sikora_login_manage_capability() {
	/**
	 * Filters the capability required to manage Sikora Custom Login settings.
	 *
	 * Useful on multisite installs that need a capability other than `manage_options`.
	 *
	 * @since 2.0.0
	 *
	 * @param string $capability Capability name. Default 'manage_options'.
	 */
	return (string) apply_filters( 'sikora_login_manage_capability', 'manage_options' );
}

/**
 * Builds a cache-busting version string for a plugin asset.
 *
 * Prefers the file modification time so browsers pick up changes without a
 * manual version bump. Falls back to {@see SIKORA_LOGIN_VERSION}.
 *
 * @since 2.0.0
 *
 * @param string $relative_path Path relative to the plugin root (e.g. 'assets/admin.js').
 * @return string Version string suitable for {@see wp_enqueue_script()} / {@see wp_enqueue_style()}.
 */
function sikora_login_asset_version( $relative_path ) {
	$file = SIKORA_LOGIN_PLUGIN_DIR . ltrim( str_replace( '\\', '/', $relative_path ), '/' );

	if ( is_readable( $file ) ) {
		$mtime = filemtime( $file );
		if ( false !== $mtime ) {
			return (string) $mtime;
		}
	}

	return SIKORA_LOGIN_VERSION;
}

/**
 * Returns the list of MIME types allowed for the login background image.
 *
 * @since 2.0.0
 *
 * @param int $attachment_id Attachment ID, passed through to the filter. Default 0.
 * @return string[] List of allowed MIME type strings.
 */
function sikora_login_allowed_bg_mimes( $attachment_id = 0 ) {
	$allowed_mimes = array(
		'image/jpeg',
		'image/png',
		'image/gif',
		'image/webp',
	);

	/**
	 * Filters the MIME types allowed for the login background image.
	 *
	 * SVG (`image/svg+xml`) must not be added; it is rejected elsewhere as well.
	 *
	 * @since 2.0.0
	 *
	 * @param string[] $allowed_mimes Allowed MIME types.
	 * @param int      $attachment_id Attachment ID being evaluated.
	 */
	return apply_filters( 'sikora_login_allowed_bg_mimes', $allowed_mimes, $attachment_id );
}

/**
 * Determines whether an attachment may be used as the login background.
 *
 * Accepts only existing Media Library JPEG, PNG, GIF, or WebP images (not SVG).
 * Checks attachment metadata, file extension, and on-disk image MIME contents.
 *
 * @since 2.0.0
 *
 * @param int $attachment_id Attachment post ID.
 * @return bool True if the attachment is allowed as a background image.
 */
function sikora_is_allowed_bg_image( $attachment_id ) {
	$attachment_id = absint( $attachment_id );

	if ( ! $attachment_id ) {
		return false;
	}

	$post = get_post( $attachment_id );
	if ( ! $post || 'attachment' !== $post->post_type ) {
		return false;
	}

	if ( 'trash' === $post->post_status ) {
		return false;
	}

	if ( ! wp_attachment_is_image( $attachment_id ) ) {
		return false;
	}

	$allowed_mimes = sikora_login_allowed_bg_mimes( $attachment_id );
	$mime          = get_post_mime_type( $attachment_id );

	if ( 'image/jpg' === $mime ) {
		$mime = 'image/jpeg';
	}

	if ( ! $mime || ! in_array( $mime, $allowed_mimes, true ) ) {
		return false;
	}

	$file = get_attached_file( $attachment_id );
	if ( ! $file || ! is_readable( $file ) ) {
		return false; // require a readable file, not just metadata
	}

	$extension   = strtolower( pathinfo( $file, PATHINFO_EXTENSION ) );
	$allowed_ext = array( 'jpg', 'jpeg', 'png', 'gif', 'webp' );
	if ( ! in_array( $extension, $allowed_ext, true ) ) {
		return false;
	}

	// Reject files whose contents are not a real allowed image (e.g. SVG renamed to .jpg).
	$detected_mime = wp_get_image_mime( $file );
	if ( 'image/jpg' === $detected_mime ) {
		$detected_mime = 'image/jpeg';
	}
	if ( ! $detected_mime || ! in_array( $detected_mime, $allowed_mimes, true ) ) {
		return false;
	}

	return true;
}

/**
 * Sanitizes the background image option before it is saved.
 *
 * `options.php` passes `null` when a field is absent from POST; that must not
 * clear an existing selection. An explicit empty/`0` value clears the image.
 *
 * @since 2.0.0
 *
 * @param mixed $value Raw value from the settings form.
 * @return int Attachment ID to store, or 0 to clear the setting.
 */
function sikora_sanitize_bg_image( $value ) {
	$previous = (int) get_option( SIKORA_LOGIN_BG_OPTION, 0 );

	// Field omitted from POST — keep the existing saved image.
	if ( null === $value ) {
		return $previous;
	}

	$id = absint( $value );

	// Explicit remove.
	if ( 0 === $id ) {
		return 0;
	}

	if ( sikora_is_allowed_bg_image( $id ) ) {
		return $id;
	}

	return sikora_is_allowed_bg_image( $previous ) ? $previous : 0;
}

/**
 * Sanitizes the business logo option before it is saved.
 *
 * Same attachment rules as the background image. `options.php` passes `null`
 * when a field is absent from POST; that must not clear an existing logo.
 *
 * @since 2.2.0
 *
 * @param mixed $value Raw value from the settings form.
 * @return int Attachment ID to store, or 0 to clear the setting.
 */
function sikora_sanitize_logo_image( $value ) {
	$previous = (int) get_option( SIKORA_LOGIN_LOGO_OPTION, 0 );

	// Field omitted from POST — keep the existing saved logo.
	if ( null === $value ) {
		return $previous;
	}

	$id = absint( $value );

	// Explicit remove.
	if ( 0 === $id ) {
		return 0;
	}

	if ( sikora_is_allowed_bg_image( $id ) ) {
		return $id;
	}

	return sikora_is_allowed_bg_image( $previous ) ? $previous : 0;
}

/**
 * Returns the stored login background color.
 *
 * Re-sanitizes on read so a hand-edited option can never reach the stylesheet.
 * Falls back to {@see SIKORA_LOGIN_BG_COLOR_DEFAULT} only when no row exists; a
 * stored empty string means the user deliberately cleared the color.
 *
 * @since 2.1.0
 *
 * @return string Hex color (e.g. `#eaeaea`), or an empty string when cleared.
 */
function sikora_login_get_bg_color() {
	$color = sanitize_hex_color( (string) get_option( SIKORA_LOGIN_BG_COLOR_OPTION, SIKORA_LOGIN_BG_COLOR_DEFAULT ) );

	return $color ? $color : '';
}

/**
 * Sanitizes the background color option before it is saved.
 *
 * `options.php` passes `null` when a field is absent from POST; that must not
 * clear an existing color. An empty value clears the setting. An invalid value
 * keeps the previous color and reports an error.
 *
 * @since 2.1.0
 *
 * @param mixed $value Raw value from the settings form.
 * @return string Sanitized hex color, or an empty string to clear the setting.
 */
function sikora_sanitize_bg_color( $value ) {
	$previous = sikora_login_get_bg_color();

	// Field omitted from POST — keep the existing saved color.
	if ( null === $value ) {
		return $previous;
	}

	$raw = trim( (string) $value );

	// Explicit clear.
	if ( '' === $raw ) {
		return '';
	}

	// Tolerate a pasted value without the leading "#".
	if ( '#' !== substr( $raw, 0, 1 ) ) {
		$raw = '#' . $raw;
	}

	$color = sanitize_hex_color( $raw );

	if ( $color ) {
		return $color;
	}

	add_settings_error(
		SIKORA_LOGIN_BG_COLOR_OPTION,
		'sikora_login_bg_color_invalid',
		sprintf(
			/* translators: %s: example hex color. */
			__( 'The background color must be a hex value such as %s. The previous color was kept.', 'sikora-custom-login' ),
			SIKORA_LOGIN_BG_COLOR_DEFAULT
		),
		'error'
	);

	return $previous;
}

/**
 * Whether a hex color is dark enough to need light-colored text on top.
 *
 * Uses the WCAG relative luminance formula; the 0.179 threshold is the point at
 * which white text gives better contrast than black.
 *
 * @since 2.1.0
 *
 * @param string $hex Hex color, with or without the leading `#`.
 * @return bool True when the color is dark.
 */
function sikora_login_is_dark_color( $hex ) {
	$hex = ltrim( (string) $hex, '#' );

	if ( 3 === strlen( $hex ) ) {
		$hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
	}

	if ( 6 !== strlen( $hex ) || ! ctype_xdigit( $hex ) ) {
		return false;
	}

	$channels = array();

	foreach ( array( 0, 2, 4 ) as $offset ) {
		$value      = hexdec( substr( $hex, $offset, 2 ) ) / 255;
		$channels[] = ( $value <= 0.03928 ) ? $value / 12.92 : pow( ( $value + 0.055 ) / 1.055, 2.4 );
	}

	$luminance = ( 0.2126 * $channels[0] ) + ( 0.7152 * $channels[1] ) + ( 0.0722 * $channels[2] );

	return $luminance < 0.179;
}

/**
 * Sanitizes the disable-password-reset checkbox value.
 *
 * The settings form sends "1" when Remove is checked and "0" when unchecked
 * (hidden field). Only an explicit "1" disables password reset; anything else —
 * including the default, unchecked state — leaves the reset links in place.
 *
 * @since 2.0.0
 *
 * @param mixed $value Raw submitted value.
 * @return int `1` when Remove is checked, `0` otherwise.
 */
function sikora_sanitize_disable_password_reset( $value ) {
	// Field omitted from POST — keep the existing choice.
	if ( null === $value ) {
		return sikora_login_is_password_reset_disabled() ? 1 : 0;
	}

	return ( '1' === trim( (string) $value ) ) ? 1 : 0;
}

/**
 * Forces a plugin option to `autoload = no` so it is not loaded on every request.
 *
 * Uses {@see wp_set_option_autoload_values()} when available (WordPress 6.4+),
 * otherwise updates the options table directly.
 *
 * @since 2.0.0
 *
 * @param string $option Option name.
 * @return void
 */
function sikora_login_set_option_autoload_no( $option ) {
	if ( function_exists( 'wp_set_option_autoload_values' ) ) {
		wp_set_option_autoload_values( array( $option => false ) );
		return;
	}

	global $wpdb;
	// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching -- fallback when wp_set_option_autoload_values() is unavailable.
	$wpdb->update(
		$wpdb->options,
		array( 'autoload' => 'no' ),
		array( 'option_name' => $option ),
		array( '%s' ),
		array( '%s' )
	);
	wp_cache_delete( $option, 'options' );
}

/**
 * Sets autoload to no after the background image option is added or updated.
 *
 * Hooked to `add_option_{$option}` and `update_option_{$option}`.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_login_after_bg_option_saved() {
	sikora_login_set_option_autoload_no( SIKORA_LOGIN_BG_OPTION );
}
add_action( 'add_option_' . SIKORA_LOGIN_BG_OPTION, 'sikora_login_after_bg_option_saved' );
add_action( 'update_option_' . SIKORA_LOGIN_BG_OPTION, 'sikora_login_after_bg_option_saved' );

/**
 * Sets autoload to no after the logo option is added or updated.
 *
 * Hooked to `add_option_{$option}` and `update_option_{$option}`.
 *
 * @since 2.2.0
 *
 * @return void
 */
function sikora_login_after_logo_option_saved() {
	sikora_login_set_option_autoload_no( SIKORA_LOGIN_LOGO_OPTION );
}
add_action( 'add_option_' . SIKORA_LOGIN_LOGO_OPTION, 'sikora_login_after_logo_option_saved' );
add_action( 'update_option_' . SIKORA_LOGIN_LOGO_OPTION, 'sikora_login_after_logo_option_saved' );

/**
 * Sets autoload to no after the background color option is added or updated.
 *
 * Hooked to `add_option_{$option}` and `update_option_{$option}`.
 *
 * @since 2.1.0
 *
 * @return void
 */
function sikora_login_after_bg_color_option_saved() {
	sikora_login_set_option_autoload_no( SIKORA_LOGIN_BG_COLOR_OPTION );
}
add_action( 'add_option_' . SIKORA_LOGIN_BG_COLOR_OPTION, 'sikora_login_after_bg_color_option_saved' );
add_action( 'update_option_' . SIKORA_LOGIN_BG_COLOR_OPTION, 'sikora_login_after_bg_color_option_saved' );

/**
 * Sets autoload to no after the disable-password-reset option is added or updated.
 *
 * Hooked to `add_option_{$option}` and `update_option_{$option}`.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_login_after_reset_option_saved() {
	sikora_login_set_option_autoload_no( SIKORA_LOGIN_DISABLE_RESET_OPTION );
}
add_action( 'add_option_' . SIKORA_LOGIN_DISABLE_RESET_OPTION, 'sikora_login_after_reset_option_saved' );
add_action( 'update_option_' . SIKORA_LOGIN_DISABLE_RESET_OPTION, 'sikora_login_after_reset_option_saved' );

/**
 * Clears a stored background attachment ID only when the media was deleted or is SVG.
 *
 * @since 2.0.0
 *
 * @return bool True if an invalid value was cleared; false otherwise.
 */
function sikora_login_clear_invalid_bg_option() {
	$attachment_id = (int) get_option( SIKORA_LOGIN_BG_OPTION, 0 );

	if ( ! $attachment_id ) {
		return false;
	}

	if ( sikora_is_allowed_bg_image( $attachment_id ) ) {
		return false;
	}

	update_option( SIKORA_LOGIN_BG_OPTION, 0, false );
	set_transient( SIKORA_LOGIN_INVALID_BG_NOTICE, 1, WEEK_IN_SECONDS );

	return true;
}

/**
 * Clears a stored logo attachment ID only when the media was deleted or is disallowed.
 *
 * @since 2.2.0
 *
 * @return bool True if an invalid value was cleared; false otherwise.
 */
function sikora_login_clear_invalid_logo_option() {
	$attachment_id = (int) get_option( SIKORA_LOGIN_LOGO_OPTION, 0 );

	if ( ! $attachment_id ) {
		return false;
	}

	if ( sikora_is_allowed_bg_image( $attachment_id ) ) {
		return false;
	}

	update_option( SIKORA_LOGIN_LOGO_OPTION, 0, false );

	return true;
}

/**
 * Runs on plugin activation: clears invalid backgrounds and normalizes autoload flags.
 *
 * Registered via {@see register_activation_hook()}.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_login_activate() {
	sikora_login_clear_invalid_bg_option();
	sikora_login_clear_invalid_logo_option();

	if ( false !== get_option( SIKORA_LOGIN_BG_OPTION, false ) ) {
		sikora_login_set_option_autoload_no( SIKORA_LOGIN_BG_OPTION );
	}

	if ( false !== get_option( SIKORA_LOGIN_LOGO_OPTION, false ) ) {
		sikora_login_set_option_autoload_no( SIKORA_LOGIN_LOGO_OPTION );
	}

	if ( ! sikora_login_store_reset_option_default() ) {
		sikora_login_set_option_autoload_no( SIKORA_LOGIN_DISABLE_RESET_OPTION ); // row already existed
	}

	if ( ! sikora_login_store_bg_color_default() ) {
		sikora_login_set_option_autoload_no( SIKORA_LOGIN_BG_COLOR_OPTION ); // row already existed
	}
}
register_activation_hook( __FILE__, 'sikora_login_activate' );

/**
 * Stores the default disable-password-reset value when the option row is missing.
 *
 * The default is `0` (checkbox unchecked, password reset links shown). An existing
 * stored value — including a stored `0` — is never overwritten because
 * {@see add_option()} is a no-op when the row already exists.
 *
 * @since 2.0.0
 *
 * @return bool True when the default was just stored; false when a value already existed.
 */
function sikora_login_store_reset_option_default() {
	if ( false !== get_option( SIKORA_LOGIN_DISABLE_RESET_OPTION, false ) ) {
		return false;
	}

	return (bool) add_option( SIKORA_LOGIN_DISABLE_RESET_OPTION, 0, '', 'no' );
}

/**
 * Stores the default background color when the option row is missing.
 *
 * An existing stored value — including a stored empty string, meaning the user
 * cleared the color — is never overwritten because {@see add_option()} is a no-op
 * when the row already exists.
 *
 * @since 2.1.0
 *
 * @return bool True when the default was just stored; false when a value already existed.
 */
function sikora_login_store_bg_color_default() {
	if ( false !== get_option( SIKORA_LOGIN_BG_COLOR_OPTION, false ) ) {
		return false;
	}

	return (bool) add_option( SIKORA_LOGIN_BG_COLOR_OPTION, SIKORA_LOGIN_BG_COLOR_DEFAULT, '', 'no' );
}

/**
 * Persists plugin option defaults on already-active installs.
 *
 * The activation hook only fires once, so sites running an older build may have no
 * option row at all. Hooked to both `admin_init` and `login_init` (early) because
 * those are the only request paths that read these settings.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_login_bootstrap_option_defaults() {
	static $done = false;

	if ( $done ) {
		return; // once per request
	}

	$done = true;

	sikora_login_store_reset_option_default();
	sikora_login_store_bg_color_default();
}
add_action( 'admin_init', 'sikora_login_bootstrap_option_defaults', 1 );
add_action( 'login_init', 'sikora_login_bootstrap_option_defaults', 1 );

/**
 * Validates the saved background image during admin requests.
 *
 * Only clears the option when the attachment no longer exists or is not a usable image.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_login_admin_validate_saved_bg() {
	// Never run the cleanup during options.php saves — it can race with update_option().
	global $pagenow;
	if ( isset( $pagenow ) && 'options.php' === $pagenow ) {
		return;
	}

	if ( ! current_user_can( sikora_login_manage_capability() ) ) {
		return;
	}

	sikora_login_clear_invalid_bg_option();
	sikora_login_clear_invalid_logo_option();
}
add_action( 'admin_init', 'sikora_login_admin_validate_saved_bg' );

/**
 * Prints admin notices under the settings page title.
 *
 * After a successful save, exactly one success message is shown. When there are
 * errors or warnings, up to five distinct messages may be shown. Core
 * `options-head.php` output is suppressed on this screen so notices are not duplicated.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_login_print_page_notice() {
	global $wp_settings_errors;

	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only UI flag from options.php redirect.
	$settings_updated = isset( $_GET['settings-updated'] ) && 'true' === $_GET['settings-updated'];

	// Load and clear the Settings API queue so it cannot be printed elsewhere.
	get_settings_errors();
	$queued             = is_array( $wp_settings_errors ) ? $wp_settings_errors : array();
	$wp_settings_errors = array();
	delete_transient( 'settings_errors' );

	$has_invalid_bg = (bool) get_transient( SIKORA_LOGIN_INVALID_BG_NOTICE );
	if ( $has_invalid_bg ) {
		delete_transient( SIKORA_LOGIN_INVALID_BG_NOTICE );
	}

	$parsed = array();
	$seen   = array();

	foreach ( $queued as $error ) {
		if ( empty( $error['message'] ) ) {
			continue;
		}

		$type = isset( $error['type'] ) ? (string) $error['type'] : 'error';
		if ( 'updated' === $type ) {
			$type = 'success';
		}

		$plain = wp_strip_all_tags( (string) $error['message'] );
		$key   = strtolower( trim( $plain ) );
		if ( '' === $key || isset( $seen[ $key ] ) ) {
			continue;
		}
		$seen[ $key ] = true;

		$parsed[] = array(
			'type'    => $type,
			'message' => (string) $error['message'],
			'code'    => isset( $error['code'] ) ? (string) $error['code'] : 'settings',
		);
	}

	if ( $has_invalid_bg ) {
		$settings_url = admin_url( 'options-general.php?page=sikora-custom-login' );
		$parsed[]     = array(
			'type'    => 'warning',
			'message' => sprintf(
				/* translators: %s: URL to the plugin settings page. */
				__( 'Sikora Custom Login cleared an invalid or disallowed background image (for example SVG or a missing file). <a href="%s">Choose a new JPEG, PNG, GIF, or WebP image</a>.', 'sikora-custom-login' ),
				esc_url( $settings_url )
			),
			'code'    => 'sikora-invalid-bg',
		);
	}

	$problem_notices = array();
	foreach ( $parsed as $notice ) {
		if ( in_array( $notice['type'], array( 'error', 'warning' ), true ) ) {
			$problem_notices[] = $notice;
		}
	}

	$to_show = array();

	if ( ! empty( $problem_notices ) ) {
		$to_show = array_slice( $problem_notices, 0, 5 );
	} elseif ( $settings_updated ) {
		$to_show[] = array(
			'type'    => 'success',
			'message' => __( 'Settings saved.', 'sikora-custom-login' ),
			'code'    => 'settings-updated',
		);
	} else {
		$success_notices = array();
		$other_notices   = array();
		foreach ( $parsed as $notice ) {
			if ( 'success' === $notice['type'] ) {
				$success_notices[] = $notice;
			} else {
				$other_notices[] = $notice;
			}
		}

		if ( ! empty( $success_notices ) ) {
			$to_show[] = array(
				'type'    => 'success',
				'message' => __( 'Settings saved.', 'sikora-custom-login' ),
				'code'    => 'settings-updated',
			);
		} else {
			$to_show = array_slice( $other_notices, 0, 5 );
		}
	}

	if ( empty( $to_show ) ) {
		return;
	}

	$allowed_html = array(
		'a' => array(
			'href'   => array(),
			'target' => array(),
			'rel'    => array(),
		),
	);

	foreach ( $to_show as $notice ) {
		printf(
			'<div id="setting-error-%1$s" class="notice notice-%2$s settings-error is-dismissible"><p><strong>%3$s</strong></p></div>' . "\n",
			esc_attr( $notice['code'] ),
			esc_attr( $notice['type'] ),
			wp_kses( $notice['message'], $allowed_html )
		);
	}
}

/**
 * On this plugin's settings screen, stop core from printing settings errors early.
 *
 * Notices are rendered once under the page title instead.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_login_settings_page_load() {
	remove_action( 'admin_notices', 'settings_errors' );
	remove_action( 'all_admin_notices', 'settings_errors' );

	// Hide settings notices above the page title; they are printed under the h1 instead.
	add_action( 'admin_notices', 'sikora_login_buffer_start_admin_notices', 0 );
	add_action( 'all_admin_notices', 'sikora_login_buffer_start_admin_notices', 0 );
	add_action( 'admin_notices', 'sikora_login_buffer_end_admin_notices', PHP_INT_MAX );
	add_action( 'all_admin_notices', 'sikora_login_buffer_end_admin_notices', PHP_INT_MAX );

	// Core options-head.php prints settings_errors() after all_admin_notices; discard that output.
	add_action( 'all_admin_notices', 'sikora_login_ob_start_options_head_buffer', PHP_INT_MAX );
}
add_action( 'load-settings_page_sikora-custom-login', 'sikora_login_settings_page_load' );

/**
 * Starts an output buffer around admin notices on this settings screen.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_login_buffer_start_admin_notices() {
	if ( ! sikora_login_is_settings_screen() ) {
		return;
	}
	ob_start();
}

/**
 * Discards buffered admin notices on this settings screen (shown under the title instead).
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_login_buffer_end_admin_notices() {
	if ( ! sikora_login_is_settings_screen() ) {
		return;
	}
	if ( ob_get_level() > 0 ) {
		ob_end_clean();
	}
}

/**
 * Starts output buffering before core options-head.php runs on Settings submenu pages.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_login_ob_start_options_head_buffer() {
	if ( ! sikora_login_is_settings_screen() ) {
		return;
	}

	ob_start();
	$GLOBALS['sikora_login_options_head_ob'] = true;
}

/**
 * Discards buffered output from options-head.php on this plugin's settings screen.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_login_discard_options_head_buffer() {
	if ( empty( $GLOBALS['sikora_login_options_head_ob'] ) ) {
		return;
	}

	$GLOBALS['sikora_login_options_head_ob'] = false;

	if ( ob_get_level() > 0 ) {
		ob_end_clean();
	}
}

/**
 * Whether the current admin screen is this plugin's settings page.
 *
 * @since 2.0.0
 *
 * @return bool
 */
function sikora_login_is_settings_screen() {
	if ( ! function_exists( 'get_current_screen' ) ) {
		return isset( $_GET['page'] ) && 'sikora-custom-login' === $_GET['page']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	}

	$screen = get_current_screen();
	return ( $screen && 'settings_page_sikora-custom-login' === $screen->id );
}

/**
 * Prints the invalid-background warning notice when the transient is set.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_login_print_invalid_bg_notice() {
	if ( ! current_user_can( sikora_login_manage_capability() ) ) {
		return;
	}

	if ( ! get_transient( SIKORA_LOGIN_INVALID_BG_NOTICE ) ) {
		return;
	}

	delete_transient( SIKORA_LOGIN_INVALID_BG_NOTICE );

	$settings_url = admin_url( 'options-general.php?page=sikora-custom-login' );
	?>
	<div class="notice notice-warning is-dismissible">
		<p>
			<?php
			echo wp_kses(
				sprintf(
					/* translators: %s: URL to the plugin settings page. */
					__( 'Sikora Custom Login cleared an invalid or disallowed background image (for example SVG or a missing file). <a href="%s">Choose a new JPEG, PNG, GIF, or WebP image</a>.', 'sikora-custom-login' ),
					esc_url( $settings_url )
				),
				array(
					'a' => array(
						'href' => array(),
					),
				)
			);
			?>
		</p>
	</div>
	<?php
}

/**
 * Displays an admin warning when an invalid background image was cleared.
 *
 * On this plugin's settings screen the notice is printed under the page title
 * instead (see {@see sikora_render_settings_page()}).
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_login_invalid_bg_admin_notice() {
	if ( sikora_login_is_settings_screen() ) {
		return;
	}

	sikora_login_print_invalid_bg_notice();
}
add_action( 'admin_notices', 'sikora_login_invalid_bg_admin_notice' );

/**
 * Resolves a validated http(s) URL for a background attachment.
 *
 * Ensures the attachment passes {@see sikora_is_allowed_bg_image()} and that the
 * URL host is one of the allowed site / uploads / content hosts (or a host added
 * via the `sikora_login_allowed_bg_hosts` filter).
 *
 * @since 2.0.0
 *
 * @param int|null $attachment_id Optional attachment ID. Defaults to the saved option.
 * @return string Validated URL, or an empty string when the image is invalid.
 */
function sikora_get_validated_bg_url( $attachment_id = null ) {
	if ( null === $attachment_id ) {
		$attachment_id = (int) get_option( SIKORA_LOGIN_BG_OPTION, 0 );
	}

	$attachment_id = absint( $attachment_id );

	if ( ! sikora_is_allowed_bg_image( $attachment_id ) ) {
		return '';
	}

	$url = wp_get_attachment_url( $attachment_id );
	if ( ! $url ) {
		return '';
	}

	$url = esc_url_raw( $url, array( 'http', 'https' ) );
	if ( ! $url ) {
		return '';
	}

	$url_host = wp_parse_url( $url, PHP_URL_HOST );
	if ( ! $url_host ) {
		return '';
	}

	$upload_dir    = wp_upload_dir();
	$allowed_hosts = array_filter(
		array_unique(
			array_map(
				'strtolower',
				array(
					(string) wp_parse_url( home_url(), PHP_URL_HOST ),
					(string) wp_parse_url( site_url(), PHP_URL_HOST ),
					(string) wp_parse_url( content_url(), PHP_URL_HOST ),
					(string) wp_parse_url( $upload_dir['baseurl'], PHP_URL_HOST ),
				)
			)
		)
	);

	/**
	 * Filters the hostnames allowed for login background image URLs.
	 *
	 * Add CDN hostnames here when media is offloaded to an external host.
	 *
	 * @since 2.0.0
	 *
	 * @param string[] $allowed_hosts Lowercased hostnames.
	 * @param int      $attachment_id Attachment ID being resolved.
	 */
	$allowed_hosts = apply_filters( 'sikora_login_allowed_bg_hosts', $allowed_hosts, $attachment_id );

	if ( ! in_array( strtolower( $url_host ), $allowed_hosts, true ) ) {
		return '';
	}

	return $url;
}

/**
 * Registers the plugin settings page under Settings.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_register_settings_page() {
	add_options_page(
		__( 'Sikora Custom Login', 'sikora-custom-login' ),
		__( 'Sikora Custom Login', 'sikora-custom-login' ),
		sikora_login_manage_capability(),
		'sikora-custom-login',
		'sikora_render_settings_page'
	);
}
add_action( 'admin_menu', 'sikora_register_settings_page' );

/**
 * Adds a Settings link on the Plugins list row for this plugin.
 *
 * @since 2.2.2
 *
 * @param string[] $links Existing plugin action links.
 * @return string[]
 */
function sikora_login_plugin_action_links( $links ) {
	$settings_link = sprintf(
		'<a href="%s">%s</a>',
		esc_url( admin_url( 'options-general.php?page=sikora-custom-login' ) ),
		esc_html__( 'Settings', 'sikora-custom-login' )
	);

	array_unshift( $links, $settings_link );

	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'sikora_login_plugin_action_links' );

/**
 * Registers plugin options with the Settings API.
 *
 * Options are not exposed via the REST API and are registered with
 * `autoload` disabled where supported.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_register_settings() {
	register_setting(
		'sikora_custom_login_settings_group',
		SIKORA_LOGIN_LOGO_OPTION,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sikora_sanitize_logo_image',
			'default'           => '0',
			'show_in_rest'      => false,
			'autoload'          => false,
		)
	);

	register_setting(
		'sikora_custom_login_settings_group',
		SIKORA_LOGIN_BG_OPTION,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sikora_sanitize_bg_image',
			'default'           => '0',
			'show_in_rest'      => false,
			'autoload'          => false,
		)
	);

	register_setting(
		'sikora_custom_login_settings_group',
		SIKORA_LOGIN_BG_COLOR_OPTION,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'sikora_sanitize_bg_color',
			// Do not set 'default' here: when the stored value equals a registered
			// default, update_option() calls add_option() and the save is skipped
			// if the row already exists (so picking a color after clearing one
			// would never persist). Reads default via get_option( $option, '' ).
			'show_in_rest'      => false,
			'autoload'          => false,
		)
	);

	register_setting(
		'sikora_custom_login_settings_group',
		SIKORA_LOGIN_DISABLE_RESET_OPTION,
		array(
			'type'              => 'integer',
			'sanitize_callback' => 'sikora_sanitize_disable_password_reset',
			// Do not set 'default' here: when the stored value equals a registered
			// default, update_option() calls add_option() and the save is skipped
			// if the row already exists (so unchecking Remove would never persist).
			'show_in_rest'      => false,
			'autoload'          => false,
		)
	);
}
add_action( 'admin_init', 'sikora_register_settings' );

/**
 * Returns the settings-page CSS that tightens the color picker spacing.
 *
 * `.wp-picker-container` is an inline-block, so when the picker is expanded the
 * line box it sits in reserves descender space underneath, pushing the
 * description text away. Making it a block removes that, and the margins are
 * declared with `!important` so other admin styles cannot reopen the gap.
 *
 * @since 2.1.0
 *
 * @return string CSS, no enclosing <style> tag.
 */
function sikora_login_admin_inline_css() {
	return '
.sikora-bg-color-cell .wp-picker-container{display:block;}
.sikora-bg-color-cell .wp-picker-holder{line-height:0;}
.sikora-bg-color-cell .wp-picker-container .iris-picker{margin-top:4px!important;margin-bottom:0!important;}
.sikora-bg-color-cell p.description{margin-top:4px!important;}
';
}

/**
 * Enqueues the Media Library modal and admin script on the plugin settings page.
 *
 * @since 2.0.0
 *
 * @param string $hook Current admin page hook suffix.
 * @return void
 */
function sikora_enqueue_admin_scripts( $hook ) {
	if ( 'settings_page_sikora-custom-login' !== $hook ) {
		return;
	}

	wp_enqueue_media();
	wp_enqueue_style( 'wp-color-picker' );

	// No src: the handle exists only to carry the inline CSS below.
	wp_register_style( 'sikora-custom-login-admin', false, array( 'wp-color-picker' ), SIKORA_LOGIN_VERSION );
	wp_enqueue_style( 'sikora-custom-login-admin' );
	wp_add_inline_style( 'sikora-custom-login-admin', sikora_login_admin_inline_css() );

	$admin_script_ver = sikora_login_asset_version( 'assets/admin.js' );

	wp_register_script(
		'sikora-custom-login-admin',
		SIKORA_LOGIN_PLUGIN_URL . 'assets/admin.js',
		array( 'jquery', 'media-editor', 'wp-color-picker' ),
		$admin_script_ver,
		true
	);
	wp_enqueue_script( 'sikora-custom-login-admin' );
}
add_action( 'admin_enqueue_scripts', 'sikora_enqueue_admin_scripts' );

/**
 * Renders the plugin settings page markup.
 *
 * Outputs the business logo picker, background image picker, the background color
 * picker, and the optional password-reset toggle.
 * Requires {@see sikora_login_manage_capability()}.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_render_settings_page() {
	if ( ! current_user_can( sikora_login_manage_capability() ) ) {
		return;
	}

	sikora_login_discard_options_head_buffer();

	$logo_id       = (int) get_option( SIKORA_LOGIN_LOGO_OPTION, 0 );
	$current_logo  = '';
	$attachment_id = (int) get_option( SIKORA_LOGIN_BG_OPTION, 0 );
	$current_image = '';
	$bg_color      = sikora_login_get_bg_color();

	if ( $logo_id ) {
		$current_logo = sikora_get_validated_bg_url( $logo_id );

		if ( ! $current_logo ) {
			$fallback = wp_get_attachment_image_url( $logo_id, 'thumbnail' );
			if ( ! $fallback ) {
				$fallback = wp_get_attachment_url( $logo_id );
			}
			if ( $fallback ) {
				$current_logo = (string) esc_url_raw( $fallback, array( 'http', 'https' ) );
			}
		}

		if ( ! $current_logo && ! get_post( $logo_id ) ) {
			$logo_id = 0;
		}
	}

	if ( $attachment_id ) {
		// Always try to show a preview for a stored Media Library image.
		$current_image = sikora_get_validated_bg_url( $attachment_id );

		if ( ! $current_image ) {
			$fallback = wp_get_attachment_image_url( $attachment_id, 'medium' );
			if ( ! $fallback ) {
				$fallback = wp_get_attachment_url( $attachment_id );
			}
			if ( $fallback ) {
				$current_image = (string) esc_url_raw( $fallback, array( 'http', 'https' ) );
			}
		}

		if ( ! $current_image && ! get_post( $attachment_id ) ) {
			$attachment_id = 0;
		}
	}
	?>
	<div class="wrap">
		<h1 style="font-weight:700;"><?php esc_html_e( 'Sikora Custom Login', 'sikora-custom-login' ); ?></h1>
		<?php sikora_login_print_page_notice(); ?>
		<form method="post" action="options.php">
			<?php settings_fields( 'sikora_custom_login_settings_group' ); ?>

			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">
						<label for="sikora-logo-image-id">
							<?php esc_html_e( 'Business Logo', 'sikora-custom-login' ); ?>
						</label>
					</th>
					<td>
						<img
							id="sikora-logo-preview"
							src="<?php echo $current_logo ? esc_url( $current_logo, array( 'http', 'https' ) ) : ''; ?>"
							alt="<?php echo $current_logo ? esc_attr__( 'Current Sikora Custom Login business logo', 'sikora-custom-login' ) : ''; ?>"
							style="max-width:84px; max-height:84px; margin-bottom:10px; <?php echo $current_logo ? 'display:block;' : 'display:none;'; ?>"
						>

						<input
							type="hidden"
							id="sikora-logo-image-id"
							name="<?php echo esc_attr( SIKORA_LOGIN_LOGO_OPTION ); ?>"
							value="<?php echo esc_attr( $logo_id ); ?>"
						>

						<button type="button" id="sikora-choose-logo" class="button button-secondary">
							<?php esc_html_e( 'Choose Logo', 'sikora-custom-login' ); ?>
						</button>

						<button
							type="button"
							id="sikora-remove-logo"
							class="button button-link-delete"
							style="margin-left:10px;<?php echo ( $current_logo || $logo_id ) ? '' : ' display:none;'; ?>"
						>
							<?php esc_html_e( 'Remove Logo', 'sikora-custom-login' ); ?>
						</button>

						<p class="description">
							<?php
							printf(
								/* translators: 1: logo width in pixels, 2: logo height in pixels. */
								esc_html__( 'Maximum display size: %1$d×%2$d pixels. Larger images are scaled down to fit. Only JPEG, PNG, GIF, or WebP images can be selected.', 'sikora-custom-login' ),
								(int) SIKORA_LOGIN_LOGO_SIZE,
								(int) SIKORA_LOGIN_LOGO_SIZE
							);
							?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="sikora-bg-image-id">
							<?php esc_html_e( 'Background Image', 'sikora-custom-login' ); ?>
						</label>
					</th>
					<td>
						<img
							id="sikora-image-preview"
							src="<?php echo $current_image ? esc_url( $current_image, array( 'http', 'https' ) ) : ''; ?>"
							alt="<?php echo $current_image ? esc_attr__( 'Current Sikora Custom Login background', 'sikora-custom-login' ) : ''; ?>"
							style="max-width:300px; margin-bottom:10px; <?php echo $current_image ? 'display:block;' : 'display:none;'; ?>"
						>

						<input
							type="hidden"
							id="sikora-bg-image-id"
							name="<?php echo esc_attr( SIKORA_LOGIN_BG_OPTION ); ?>"
							value="<?php echo esc_attr( $attachment_id ); ?>"
						>

						<button type="button" id="sikora-choose-image" class="button button-secondary">
							<?php esc_html_e( 'Choose Image', 'sikora-custom-login' ); ?>
						</button>

						<button
							type="button"
							id="sikora-remove-image"
							class="button button-link-delete"
							style="margin-left:10px;<?php echo ( $current_image || $attachment_id ) ? '' : ' display:none;'; ?>"
						>
							<?php esc_html_e( 'Remove Image', 'sikora-custom-login' ); ?>
						</button>

						<p class="description">
							<?php esc_html_e( 'Only JPEG, PNG, GIF, or WebP images can be selected.', 'sikora-custom-login' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="sikora-bg-color">
							<?php esc_html_e( 'Background Color', 'sikora-custom-login' ); ?>
						</label>
					</th>
					<td class="sikora-bg-color-cell">
						<input
							type="text"
							id="sikora-bg-color"
							class="sikora-color-field"
							name="<?php echo esc_attr( SIKORA_LOGIN_BG_COLOR_OPTION ); ?>"
							value="<?php echo esc_attr( $bg_color ); ?>"
							data-default-color="<?php echo esc_attr( SIKORA_LOGIN_BG_COLOR_DEFAULT ); ?>"
						>

						<p class="description">
							<?php esc_html_e( 'The selected color is only used when no background image is selected.', 'sikora-custom-login' ); ?>
						</p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<?php esc_html_e( 'Password Reset Link', 'sikora-custom-login' ); ?>
					</th>
					<td>
						<?php
						// Hidden field must stay outside the checkbox so an unchecked
						// state always submits 0 (same-name fields: last value wins).
						?>
						<input type="hidden" name="<?php echo esc_attr( SIKORA_LOGIN_DISABLE_RESET_OPTION ); ?>" value="0">
						<label for="sikora-disable-password-reset">
							<input
								type="checkbox"
								id="sikora-disable-password-reset"
								name="<?php echo esc_attr( SIKORA_LOGIN_DISABLE_RESET_OPTION ); ?>"
								value="1"
								<?php checked( sikora_login_is_password_reset_disabled() ); ?>
							>
							<?php esc_html_e( 'Remove', 'sikora-custom-login' ); ?>
						</label>
					</td>
				</tr>
			</table>

			<?php submit_button( __( 'Save Settings', 'sikora-custom-login' ) ); ?>
		</form>
	</div>
	<?php
}

/**
 * Prints the login-page styles inline, plus the background image or color rule.
 *
 * A valid background image takes precedence; the background color is used only
 * when no image is set. When neither is set the login page keeps core styling.
 *
 * `assets/login.css` is emitted inline rather than linked as a separate request.
 * Many hosts and optimization layers strip the `?ver=` cache buster from static
 * asset URLs while serving them with a long `max-age`, which pins browsers to a
 * stale copy of the stylesheet indefinitely. Inlining ties the CSS to the login
 * HTML response, which is never cached, so updates always take effect.
 *
 * Sizing pairs `background-size: cover` with `background-attachment: fixed` so the
 * image is scaled to the viewport (enlarged only enough to fill the screen),
 * centered, and never stretched unevenly.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_login_custom_styles() {
	// No src: the handle exists only to carry the inline CSS below.
	wp_register_style( 'sikora-custom-login', false, array( 'login' ), SIKORA_LOGIN_VERSION );
	wp_enqueue_style( 'sikora-custom-login' );

	$stylesheet = SIKORA_LOGIN_PLUGIN_DIR . 'assets/login.css';

	if ( is_readable( $stylesheet ) ) {
		$css = file_get_contents( $stylesheet ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local plugin asset, not a remote request.

		if ( is_string( $css ) && '' !== $css ) {
			// Defensive: a stray "</style>" in the asset would break out of the tag.
			wp_add_inline_style( 'sikora-custom-login', str_replace( '</', '<\/', $css ) );
		}
	}

	$bg_image = sikora_get_validated_bg_url();
	$logo_url = sikora_get_validated_bg_url( (int) get_option( SIKORA_LOGIN_LOGO_OPTION, 0 ) );
	$rules    = array();

	// Image wins; the color is only a fallback for when no image is set.
	if ( $bg_image ) {
		$rules[] = sprintf(
			'body.login{background-image:url("%1$s") !important;}',
			esc_url( $bg_image, array( 'http', 'https' ) )
		);
	} else {
		$bg_color = sikora_login_get_bg_color();

		if ( $bg_color ) {
			$rules[] = sprintf(
				'body.login{background-color:%1$s !important;}',
				$bg_color // already sanitized to a hex color
			);
		}
	}

	if ( $logo_url ) {
		$size    = (int) SIKORA_LOGIN_LOGO_SIZE;
		$rules[] = sprintf(
			'body.login.sikora-has-logo h1.wp-login-logo a{background-image:url("%1$s") !important;background-size:contain !important;background-position:center center !important;background-repeat:no-repeat !important;width:%2$dpx !important;height:%2$dpx !important;}',
			esc_url( $logo_url, array( 'http', 'https' ) ),
			$size
		);
	}

	if ( empty( $rules ) ) {
		return;
	}

	wp_add_inline_style( 'sikora-custom-login', implode( '', $rules ) );
}
add_action( 'login_enqueue_scripts', 'sikora_login_custom_styles' );

/**
 * Whether password reset on wp-login.php is disabled in plugin settings.
 *
 * @since 2.0.0
 *
 * @return bool True when the "Remove" password-reset checkbox is checked.
 */
function sikora_login_is_password_reset_disabled() {
	return 1 === (int) get_option( SIKORA_LOGIN_DISABLE_RESET_OPTION, 0 ); // unchecked by default
}

/**
 * Adds body classes reflecting the password-reset and background settings.
 *
 * `sikora-light-links` drives the white footer-link styling, so it is only added
 * when the active background is dark enough for white text to be legible.
 *
 * @since 2.0.0
 *
 * @param string[] $classes Login body classes.
 * @return string[]
 */
function sikora_login_body_class( $classes ) {
	if ( sikora_login_is_password_reset_disabled() ) {
		$classes[] = 'sikora-password-reset-disabled';
	}

	if ( sikora_get_validated_bg_url( (int) get_option( SIKORA_LOGIN_LOGO_OPTION, 0 ) ) ) {
		$classes[] = 'sikora-has-logo';
	}

	if ( sikora_get_validated_bg_url() ) {
		$classes[] = 'sikora-has-bg';
		$classes[] = 'sikora-light-links'; // photos get a dark base color underneath
	} elseif ( sikora_login_is_dark_color( sikora_login_get_bg_color() ) ) {
		// Only switch the footer links to white when the color is dark enough.
		$classes[] = 'sikora-light-links';
	}

	return $classes;
}
add_filter( 'login_body_class', 'sikora_login_body_class' );

/**
 * Removes the "Lost your password?" HTML link when password reset is disabled.
 *
 * @since 2.0.0
 *
 * @param string $html_link Default lost-password link markup.
 * @return string Empty string when disabled; otherwise the original link.
 */
function sikora_login_filter_lost_password_html_link( $html_link ) {
	if ( sikora_login_is_password_reset_disabled() ) {
		return '';
	}

	return $html_link;
}
add_filter( 'lost_password_html_link', 'sikora_login_filter_lost_password_html_link' );

/**
 * Drops the login nav link separator when the lost-password link is suppressed.
 *
 * Avoids a trailing "Register |" when registration is open and reset is disabled.
 *
 * @since 2.0.0
 *
 * @param string $separator Default separator between login nav links.
 * @return string Empty string when reset is disabled; otherwise the original separator.
 */
function sikora_login_filter_login_link_separator( $separator ) {
	if ( sikora_login_is_password_reset_disabled() ) {
		return '';
	}

	return $separator;
}
add_filter( 'login_link_separator', 'sikora_login_filter_login_link_separator' );

/**
 * Redirects password-reset login actions when that setting is enabled.
 *
 * Hooked to `login_form_lostpassword`, `login_form_retrievepassword`,
 * `login_form_resetpass`, and `login_form_rp` so the blocked action is known
 * without reading `$_REQUEST` (avoids nonce-verification sniff false positives).
 * Does nothing when {@see SIKORA_LOGIN_DISABLE_RESET_OPTION} is off.
 *
 * @since 2.0.0
 *
 * @return void
 */
function sikora_maybe_disable_lost_password() {
	if ( ! sikora_login_is_password_reset_disabled() ) {
		return;
	}

	wp_safe_redirect( wp_login_url() );
	exit;
}
add_action( 'login_form_lostpassword', 'sikora_maybe_disable_lost_password' );
add_action( 'login_form_retrievepassword', 'sikora_maybe_disable_lost_password' );
add_action( 'login_form_resetpass', 'sikora_maybe_disable_lost_password' );
add_action( 'login_form_rp', 'sikora_maybe_disable_lost_password' );

/**
 * Filters the login logo URL to the site home URL.
 *
 * The logo is also hidden via CSS; this keeps the underlying link on-site if CSS fails.
 *
 * @since 2.0.0
 *
 * @return string Home URL.
 */
function sikora_login_logo_url() {
	return home_url();
}
add_filter( 'login_headerurl', 'sikora_login_logo_url' );

/**
 * Filters the login logo title/text to the site name.
 *
 * Hooks both `login_headertext` (WordPress 5.2+) and the legacy `login_headertitle` filter.
 *
 * @since 2.0.0
 *
 * @return string Blog name.
 */
function sikora_login_logo_url_title() {
	return get_bloginfo( 'name' );
}
add_filter( 'login_headertext', 'sikora_login_logo_url_title' );
add_filter( 'login_headertitle', 'sikora_login_logo_url_title' );
