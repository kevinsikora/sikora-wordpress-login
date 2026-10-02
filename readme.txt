=== Sikora WordPress Login ===
Contributors: sikoracollective
Tags: login, custom login, branding, admin login, media library
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Customize the WordPress login screen with a Media Library background image and a cleaner branded layout.

== Description ==

Sikora WordPress Login customizes `wp-login.php` for a cleaner, on-brand experience.

= Features =

* Choose a full-screen login background from the Media Library
* Center the login form in the viewport
* Hide the WordPress logo link (branding)
* Optionally hide the "Lost your password?" link (branding only)
* Optional setting to fully disable password reset on `wp-login.php`
* Stores an attachment ID (not a raw URL) and validates images server-side

= Security notes =

* Background images must be JPEG, PNG, GIF, or WebP (SVG is not allowed)
* Images are checked for MIME type, file contents when available, attachment status, and max dimensions (default 4096×4096)
* Hiding login links with CSS is for branding — it is not a security control
* Password reset links are shown by default; check "Remove" under Password Reset Links in settings to hide them and block the reset flow

= Filters =

Developers can extend behavior with:

* `sikora_login_manage_capability` — capability required to manage settings
* `sikora_login_allowed_bg_mimes` — allowed background MIME types
* `sikora_login_allowed_attachment_statuses` — allowed attachment post statuses
* `sikora_login_max_bg_width` / `sikora_login_max_bg_height` — max image size in pixels
* `sikora_login_allowed_bg_hosts` — allowed URL hosts (useful for CDN / offloaded media)

== Installation ==

1. Upload the `sikora-wordpress-login` folder to the `/wp-content/plugins/` directory, or install the ZIP via **Plugins → Add New → Upload Plugin**.
2. Activate the plugin through the **Plugins** screen.
3. Go to **Settings → Sikora WordPress Login**.
4. Choose a background image from the Media Library and save.
5. Optionally check **Remove** under Password Reset Links if you want to hide password recovery on wp-login.php.

== Frequently Asked Questions ==

= Where are the settings? =

**Settings → Sikora WordPress Login**.

= Why was my background image cleared? =

The plugin removes saved backgrounds that are no longer allowed — for example SVG files, missing attachments, private/trash media, or images larger than the configured maximum size. Choose a new JPEG, PNG, GIF, or WebP image.

= Does hiding "Lost your password?" block password reset? =

No. CSS only hides the link. Password reset works normally by default. Check **Remove** under Password Reset Links in the settings to hide the link *and* block `wp-login.php?action=lostpassword`.

= Can I use a CDN-hosted image? =

Yes, if the attachment is still a Media Library item. If your CDN uses a different hostname, add it with the `sikora_login_allowed_bg_hosts` filter.

= Does this replace a security plugin? =

No. Use a dedicated security plugin for rate limiting, two-factor authentication, and similar protections.

== Screenshots ==

1. Settings page for choosing a Media Library background image.
2. Customized WordPress login screen with background image.

== Changelog ==

= 2.0.0 =
* Major hardening release
* Validate background images (MIME, file contents, status, max dimensions)
* Move admin JS and login CSS into `assets/`
* Optional disable-password-reset setting (default off)
* Activation cleanup, uninstall cleanup, and admin notices for invalid images
* Filterable capability, MIME types, hosts, and max dimensions
* WordPress-standard inline documentation

= 1.0.0 =
* Initial release: Media Library background, centered login form, hide logo and login links

== Upgrade Notice ==

= 2.0.0 =
Hardening release. Re-activate after upgrading so activation cleanup can clear any invalid saved background. SVG backgrounds are no longer allowed.
