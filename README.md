# NPBN Cookie Consent

PDPA-compliant cookie consent banner for WordPress. Auto-blocks third-party tracking scripts until the visitor gives consent.

## Features

- **Granular consent** — 4 categories: Necessary, Functional, Analytics, Marketing
- **Dual-layer script blocking** — server-side output buffer + client-side MutationObserver
- **No page reload** — scripts unblock dynamically when consent is given
- **Settings modal** — visitors can toggle individual cookie categories
- **Consent logging** — stores consent records with IP, user agent, and categories
- **Shortcode** — `[npbn_cookie_settings]` renders cookie toggles on any page
- **Floating settings button** — lets visitors change consent after dismissing the banner
- **PDPA / GDPR ready** — opt-in model, easy consent withdrawal
- **Theme font** — inherits the active theme's font family
- **Auto-update from GitHub** — updates appear in the WordPress dashboard

## Requirements

- WordPress 5.8+
- PHP 7.4+

## Installation

1. Download the latest release zip from [Releases](https://github.com/nopphan/npbn-cookie-consent/releases)
2. In WordPress admin, go to **Plugins > Add New > Upload Plugin**
3. Upload the zip and activate

Or clone into your plugins directory:

```bash
cd wp-content/plugins
git clone https://github.com/nopphan/npbn-cookie-consent.git
```

## Auto-Updates

The plugin checks GitHub Releases for new versions automatically. When a new release is published, you'll see the update in **Dashboard > Updates** just like any wp.org plugin.

## Shortcode

Use `[npbn_cookie_settings]` on any page to render cookie category toggles inline. This is useful for a dedicated "Cookie Settings" or "Privacy Policy" page.

## Blocked Domains

Scripts from these domains are auto-blocked until consent is given:

| Category    | Domains                                                                 |
|------------|-------------------------------------------------------------------------|
| Analytics  | Google Analytics, GTM, Hotjar, Plausible, Clarity, Segment             |
| Marketing  | Facebook Pixel, LinkedIn, Bing, Google Ads, TikTok, LINE Tag           |

## Settings

Configure in **Settings > Cookie Consent**:

- Banner text, heading, and button labels
- Banner position (top, bottom, modal)
- Colors (background, text, button)
- Cookie expiry days
- Category descriptions
- Show/hide reject-all on banner
- Show/hide floating settings button
- Privacy policy URL
- Reset text to defaults

## License

GPL-2.0-or-later
