# Sikora Custom Login

Customize the WordPress login screen with a Media Library background image and a cleaner branded layout.

| | |
| --- | --- |
| **Version** | 2.2.2 |
| **Requires at least** | WordPress 6.0 |
| **Tested up to** | 7.1 |
| **Requires PHP** | 7.4 |
| **License** | [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html) |
| **Tags** | login, custom login, branding, admin login, media library |

## Description

Sikora Custom Login customizes `wp-login.php` for a cleaner, on-brand experience.

### Features

- Choose a business logo from the Media Library (shown in place of the WordPress logo)
- Choose a full-screen login background from the Media Library
- Pick a background color used when no background image is selected
- Center the login form in the viewport
- Optionally hide the "Lost your password?" link
- Stores an attachment ID (not a raw URL) and validates images server-side

### Security notes

- Background and logo images must be JPEG, PNG, GIF, or WebP (SVG is not allowed)
- Images are checked for MIME type, file contents when available, and attachment status
- Hiding login links with CSS is for branding — it is not a security control
- Password reset links are shown by default; check **Remove** under Password Reset Link in settings to hide them and block the reset flow

## Installation

1. Install the ZIP file via **Plugins → Add New → Upload Plugin**.
2. Activate the plugin through the **Plugins** screen.
3. Go to **Settings → Sikora Custom Login**.
4. Optionally choose a **Business Logo** from the Media Library.
5. Choose a background image from the Media Library and save.
6. Optionally pick a **Background Color**, used only when no background image is selected.
7. Optionally check **Remove** under Password Reset Link if you want to hide password recovery on wp-login.php.

## Frequently Asked Questions

### Where are the settings?

**Settings → Sikora Custom Login**.

### Why was my background image cleared?

The plugin removes saved backgrounds that are no longer allowed — for example SVG files, missing attachments, or private/trash media. Choose a new JPEG, PNG, GIF, or WebP image.

### Why isn't my background color showing?

The background image takes precedence. Remove the image under **Background Image** and the background color is used instead. Clear the color field to fall back to the default WordPress background.

### Does hiding "Lost your password?" block password reset?

No. CSS only hides the link. Password reset works normally by default. Check **Remove** under Password Reset Link in the settings to hide the link *and* block `wp-login.php?action=lostpassword`.

### Can I use a CDN-hosted image?

Yes, if the attachment is still a Media Library item. If your CDN uses a different hostname, add it with the `sikora_login_allowed_bg_hosts` filter.

### Does this replace a security plugin?

No. Use a dedicated security plugin for rate limiting, two-factor authentication, and similar protections.

## Project structure

```
sikora-custom-login/
├── sikora-custom-login.php   # Main plugin file
├── uninstall.php                # Option cleanup on delete
├── readme.txt                   # WordPress.org readme
├── README.md                    # This file
├── build.sh                     # Builds the distributable ZIP
└── assets/
    ├── admin.js                 # Settings page Media Library picker / color picker
    ├── login.css                # Login page layout / branding CSS (printed inline)
    └── .htaccess                # Block PHP execution in assets/
```

## Changelog

### 2.2.2

- Rename plugin to Sikora Custom Login
- Use the `sikora-custom-login` slug (no "wordpress" in the plugin identity)

### 2.2.1

- Show the plugin version beside the settings page title

### 2.2.0

- New **Business Logo** setting: choose a Media Library image shown in the WordPress logo slot (84×84 px); omitted when none is selected

### 2.1.0

- New **Background Color** setting with a color picker, used when no background image is selected
- Footer link colors now follow the background's brightness for readable contrast
- Login CSS is printed inline instead of linked, so hosts that strip the `?ver=` cache buster can no longer serve a stale stylesheet
- Fix the login form's white box stopping short of the Log In button
- Login footer links are no longer underlined on hover, and no longer show a focus box after a mouse click
- The Privacy Policy link now matches the color of the other login footer links
- Simplify the settings page copy

### 2.0.0

- Major hardening release
- Validate background images (MIME, file contents, attachment status)
- Move admin JS and login CSS into `assets/`
- Optional disable-password-reset setting (default off)
- Activation cleanup, uninstall cleanup, and admin notices for invalid images
- Filterable capability, MIME types, and hosts
- WordPress-standard inline documentation

### 1.0.0

- Initial release: Media Library background, centered login form, hide logo and login links

## License

This plugin is licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).

© [Sikora Collective](https://sikoracollective.com/)
