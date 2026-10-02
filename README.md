# Sikora WordPress Login

Customize the WordPress login screen with a Media Library background image and a cleaner branded layout.

| | |
| --- | --- |
| **Version** | 2.0.0 |
| **Requires at least** | WordPress 6.0 |
| **Tested up to** | 6.7 |
| **Requires PHP** | 7.4 |
| **License** | [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html) |
| **Tags** | login, custom login, branding, admin login, media library |

## Description

Sikora WordPress Login customizes `wp-login.php` for a cleaner, on-brand experience.

### Features

- Choose a full-screen login background from the Media Library
- Center the login form in the viewport
- Hide the WordPress logo link (branding)
- Optionally hide the "Lost your password?" link (branding only)
- Optional setting to fully disable password reset on `wp-login.php`
- Stores an attachment ID (not a raw URL) and validates images server-side

### Security notes

- Background images must be JPEG, PNG, GIF, or WebP (SVG is not allowed)
- Images are checked for MIME type, file contents when available, attachment status, and max dimensions (default 4096×4096)
- Hiding login links with CSS is for branding — it is not a security control
- Password reset links are shown by default; check **Remove** under Password Reset Links in settings to hide them and block the reset flow

### Filters

Developers can extend behavior with:

| Filter | Purpose |
| --- | --- |
| `sikora_login_manage_capability` | Capability required to manage settings |
| `sikora_login_allowed_bg_mimes` | Allowed background MIME types |
| `sikora_login_allowed_attachment_statuses` | Allowed attachment post statuses |
| `sikora_login_max_bg_width` / `sikora_login_max_bg_height` | Max image size in pixels |
| `sikora_login_allowed_bg_hosts` | Allowed URL hosts (useful for CDN / offloaded media) |

## Installation

1. Upload the `sikora-wordpress-login` folder to the `/wp-content/plugins/` directory, or install the ZIP via **Plugins → Add New → Upload Plugin**.
2. Activate the plugin through the **Plugins** screen.
3. Go to **Settings → Sikora WordPress Login**.
4. Choose a background image from the Media Library and save.
5. Optionally check **Remove** under Password Reset Links if you want to hide password recovery on wp-login.php.

### Development checkout

```bash
git clone git@github.com:kevinsikora/sikora-wordpress-login.git
# Symlink or copy into wp-content/plugins/sikora-wordpress-login
```

## Frequently Asked Questions

### Where are the settings?

**Settings → Sikora WordPress Login**.

### Why was my background image cleared?

The plugin removes saved backgrounds that are no longer allowed — for example SVG files, missing attachments, private/trash media, or images larger than the configured maximum size. Choose a new JPEG, PNG, GIF, or WebP image.

### Does hiding "Lost your password?" block password reset?

No. CSS only hides the link. Password reset works normally by default. Check **Remove** under Password Reset Links in the settings to hide the link *and* block `wp-login.php?action=lostpassword`.

### Can I use a CDN-hosted image?

Yes, if the attachment is still a Media Library item. If your CDN uses a different hostname, add it with the `sikora_login_allowed_bg_hosts` filter.

### Does this replace a security plugin?

No. Use a dedicated security plugin for rate limiting, two-factor authentication, and similar protections.

## Project structure

```
sikora-wordpress-login/
├── sikora-wordpress-login.php   # Main plugin file
├── uninstall.php                # Option cleanup on delete
├── readme.txt                   # WordPress.org readme
├── README.md                    # This file
└── assets/
    ├── admin.js                 # Settings page Media Library picker
    ├── login.css                # Login page layout / branding CSS (printed inline)
    ├── index.php                # Directory safeguard
    └── .htaccess                # Block PHP execution in assets/
```

## Changelog

### 2.0.0

- Major hardening release
- Validate background images (MIME, file contents, status, max dimensions)
- Move admin JS and login CSS into `assets/`
- Optional disable-password-reset setting (default off)
- Activation cleanup, uninstall cleanup, and admin notices for invalid images
- Filterable capability, MIME types, hosts, and max dimensions
- WordPress-standard inline documentation

### 1.0.0

- Initial release: Media Library background, centered login form, hide logo and login links

## Upgrade notice (2.0.0)

Hardening release. Re-activate after upgrading so activation cleanup can clear any invalid saved background. SVG backgrounds are no longer allowed.

## License

This plugin is licensed under the [GNU General Public License v2.0 or later](https://www.gnu.org/licenses/gpl-2.0.html).

© [Sikora Collective](https://sikoracollective.com/)
