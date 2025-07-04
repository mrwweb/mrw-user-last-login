# MRW User Last Login

Adds sortable Registration Date and Last Login columns to the Users admin table. If WordFence was previously installed, it will show any data saved about the user's last login. This is a great tool for auditing a site and removing old users.

## Author

Mark Root-Wiley, [MRW Web Design, WordPress Websites for Nonprofits](https://MRWweb.com)

## Roadmap

- Make sure multisite support works (haven't tested or thought about it yet)
- [Under Consideration] Optional feature to automatically changes a user's role to "None"after a certain period of inactivity.

## Credits

Forked and just about completely rewritten from [User Registration Last Login Time](https://wordpress.org/plugins/user-registration-last-login-time/) under GPL v2.0 or later

## Changelog

### 1.1.0 (4 Jul 2025)

- Show WordFence date of last login if that exists and plugin doesn't have its own data for that user
- Switch from `date()` to `gmdate()` for better timezone support
- Change textdomain to plugin slug and add translator comments

### 1.0.0 (18 Nov 2024)

- Initial Release
