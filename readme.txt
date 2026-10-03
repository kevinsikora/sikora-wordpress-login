=== Sikora WordPress Login ===
Contributors: sikoracollective
Tags: login, custom login, branding, admin login, media library
Requires at least: 6.0
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 2.2.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Customize the WordPress login screen with a Media Library background image and a cleaner branded layout.

== Description ==

Sikora WordPress Login customizes `wp-login.php` for a cleaner, on-brand experience.

= Features =

* Choose a business logo from the Media Library (shown in place of the WordPress logo)
* Choose a full-screen login background from the Media Library
* Pick a background color (default #eaeaea) used when no background image is selected
* Center the login form in the viewport
* Optionally hide the "Lost your password?" link (branding only)
* Optional setting to fully disable password reset on `wp-login.php`
* Stores an attachment ID (not a raw URL) and validates images server-side

= Security notes =

* Background and logo images must be JPEG, PNG, GIF, or WebP (SVG is not allowed)
* Images are checked for MIME type, file contents when available, and attachment status
* Hiding login links with CSS is for branding — it is not a security control
* Password reset links are shown by default; check "Remove" under Password Reset Link in settings to hide them and block the reset flow

= Filters =

Developers can extend behavior with:

* `sikora_login_manage_capability` — capability required to manage settings
* `sikora_login_allowed_bg_mimes` — allowed background MIME types
* `sikora_login_allowed_bg_hosts` — allowed URL hosts (useful for CDN / offloaded media)

== Installation ==

1. Upload the `sikora-wordpress-login` folder to the `/wp-content/plugins/` directory, or install the ZIP via **Plugins → Add New → Upload Plugin**.
2. Activate the plugin through the **Plugins** screen.
3. Go to **Settings → Sikora WordPress Login**.
4. Optionally choose a **Business Logo** from the Media Library (shown at 84×84 px).
5. Choose a background image from the Media Library and save.
6. Optionally pick a **Background Color**, used only when no background image is selected.
7. Optionally check **Remove** under Password Reset Link if you want to hide password recovery on wp-login.php.

== Frequently Asked Questions ==

= Where are the settings? =

**Settings → Sikora WordPress Login**.

= Why isn't my background color showing? =

The background image takes precedence. Remove the image under **Background Image** and the background color is used instead. New installs start at #eaeaea; clear the color field to fall back to the default WordPress background.

= Why was my background image cleared? =

The plugin removes saved backgrounds that are no longer allowed — for example SVG files, missing attachments, or private/trash media. Choose a new JPEG, PNG, GIF, or WebP image.

= Does hiding "Lost your password?" block password reset? =

No. CSS only hides the link. Password reset works normally by default. Check **Remove** under Password Reset Link in the settings to hide the link *and* block `wp-login.php?action=lostpassword`.

= Can I use a CDN-hosted image? =

Yes, if the attachment is still a Media Library item. If your CDN uses a different hostname, add it with the `sikora_login_allowed_bg_hosts` filter.

= Does this replace a security plugin? =

No. Use a dedicated security plugin for rate limiting, two-factor authentication, and similar protections.

== Screenshots ==

1. Settings page for choosing a Media Library background image.
2. Customized WordPress login screen with background image.

== Changelog ==

= 2.2.0 =
* New "Business Logo" setting: choose a Media Library image shown in the WordPress logo slot (84×84 px); omitted when none is selected

= 2.1.0 =
* New "Background Color" setting with a color picker, used when no background image is selected (default #eaeaea)
* Footer link colors now follow the background's brightness for readable contrast
* Login CSS is printed inline instead of linked, so hosts that strip the `?ver=` cache buster can no longer serve a stale stylesheet
* Fix the login form's white box stopping short of the Log In button
* Login footer links are no longer underlined on hover, and no longer show a focus box after a mouse click
* The Privacy Policy link now matches the color of the other login footer links
* Simplify the settings page copy

= 2.0.0 =
* Major hardening release
* Validate background images (MIME, file contents, attachment status)
* Move admin JS and login CSS into `assets/`
* Optional disable-password-reset setting (default off)
* Activation cleanup, uninstall cleanup, and admin notices for invalid images
* Filterable capability, MIME types, and hosts
* WordPress-standard inline documentation

= 1.0.0 =
* Initial release: Media Library background, centered login form, hide logo and login links

== Upgrade Notice ==

= 2.2.0 =
Adds a Business Logo setting. Choose a Media Library image to show in place of the WordPress logo (84×84 px). With no logo selected, that area stays empty.

= 2.1.0 =
Adds a Background Color setting (default #eaeaea). The background image still takes precedence; the color is used only when no image is selected.

= 2.0.0 =
Hardening release. Re-activate after upgrading so activation cleanup can clear any invalid saved background. SVG backgrounds are no longer allowed.
