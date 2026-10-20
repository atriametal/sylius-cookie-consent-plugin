# Sylius Cookie Consent Plugin

[![CI](https://github.com/atriametal/sylius-cookie-consent-plugin/actions/workflows/ci.yml/badge.svg)](https://github.com/atriametal/sylius-cookie-consent-plugin/actions/workflows/ci.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
![Sylius](https://img.shields.io/badge/sylius-1.14%20%7C%202.x-1abb9c.svg)

Asks the visitors of a Sylius shop for their consent to cookies, purpose by purpose, blocks the scripts and the
embedded contents until they consent, and keeps a proof of each choice. It follows the recommendations of the
French data protection authority (CNIL):

- nothing optional is loaded before the visitor chooses;
- rejecting is as easy as accepting: both buttons have the same style, on the banner and in the preferences;
- the visitor can accept or reject each purpose, and sees the third parties behind it;
- the visitor can change or withdraw the consent at any time, from a link in the footer; the cookies of a withdrawn
  purpose are deleted;
- the choice is kept for 6 months by default (13 months at most), then the visitor is asked again;
- each choice is recorded, without any personal data, so that it can be proven.

No external service, no build step: one module script and one style sheet, themable with CSS custom properties.

## Requirements

- Sylius 1.14 or 2.x, PHP 8.2 or later, and Symfony 5.4, 6.4, 7.4 or 8 within the versions supported by your
  Sylius version.
- Sylius 1.14 requires `api-platform/core` 2.7, whose releases are all affected by security advisories: Composer 2.9
  and later refuses to install them unless your project ignores these advisories, as this plugin does for its CI in
  [composer.json](composer.json). This concerns Sylius, not the plugin.

## Installation

1. Require the plugin:

   ```bash
   composer require atriametal/sylius-cookie-consent-plugin
   ```

2. If you do not use Symfony Flex, enable the plugin:

   ```php
   // config/bundles.php
   return [
       // ...
       Atriametal\SyliusCookieConsentPlugin\AtriametalSyliusCookieConsentPlugin::class => ['all' => true],
   ];
   ```

3. Import the routes. The shop route is outside the locale prefix; the admin route goes under the admin prefix:

   ```yaml
   # config/routes/atriametal_sylius_cookie_consent.yaml
   atriametal_cookie_consent_shop:
       resource: '@AtriametalSyliusCookieConsentPlugin/config/routes/shop.php'

   atriametal_cookie_consent_admin:
       resource: '@AtriametalSyliusCookieConsentPlugin/config/routes/admin.php'
       prefix: '/%sylius_admin.path_name%'
   ```

4. Install the assets and create the table of the consent records:

   ```bash
   bin/console assets:install
   bin/console doctrine:migrations:migrate
   ```

5. Describe your cookies (see [Configuration](#configuration)), block your scripts and embedded contents (see
   [Blocking](#blocking-scripts-and-embedded-contents)), and add the preferences link to your footer:

   ```twig
   {{ include('@AtriametalSyliusCookieConsentPlugin/shop/preferences_link.html.twig') }}
   ```

6. Remove the consent records older than the retention period every day, for instance with cron:

   ```bash
   bin/console cookie-consent:purge-records
   ```

The banner, the style sheet and the script are rendered by the layout template events (Sylius 1) or Twig hooks
(Sylius 2) of the shop: a theme that keeps them needs nothing else.

| Template | Sylius 1 template event | Sylius 2 hook |
|----------|-------------------------|---------------|
| `shop/stylesheets.html.twig` | `sylius.shop.layout.stylesheets` | `sylius_shop.base#stylesheets` |
| `shop/banner.html.twig` | `sylius.shop.layout.before_body` | `sylius_shop.base.header` |
| `shop/javascripts.html.twig` | `sylius.shop.layout.javascripts` | `sylius_shop.base#javascripts` |

## Configuration

```yaml
# config/packages/atriametal_sylius_cookie_consent.yaml
atriametal_sylius_cookie_consent:
    # Change it to ask every visitor again, for instance after adding a category or a service
    policy_version: '1'
    cookie:
        name: cookie_consent
        lifetime: 182 # days, 395 at most
        domain: ~ # ".example.com" shares the consent between subdomains
    privacy_policy:
        route: ~ # the page describing your cookies, linked from the banner
        parameters: {}
    # Replace the default categories: necessary (required), analytics, marketing and media
    categories:
        necessary:
            required: true # always granted: only for the cookies strictly necessary to the shop
        analytics:
            services: ['Google Analytics'] # shown to the visitor
            cookies: ['_ga', '_ga_*'] # deleted when the visitor withdraws the consent
        media:
            services: ['YouTube']
    google_consent_mode:
        enabled: false
        signals: # the category granting each signal
            analytics_storage: analytics
            ad_storage: marketing
            ad_user_data: marketing
            ad_personalization: marketing
    records:
        retention: 395 # days
```

The label and the description of each category are translated from
`atriametal_cookie_consent.categories.<name>.label` and `.description`, in the `messages` domain. The plugin
translates the default categories in English, French and German. Override any text in your
`translations/messages.<locale>.yaml`: the texts of the banner must describe the purposes you actually use.

## Blocking scripts and embedded contents

A script runs once a visitor consents to any of the categories listed in its `data-cookie-consent` attribute.
Give it the `text/plain` type, so that the browser does not run it before:

```html
<script type="text/plain" data-cookie-consent="analytics marketing">
    (function(w,d,s,l,i){ /* Google Tag Manager */ })(window,document,'script','dataLayer','GTM-XXXX');
</script>
<script type="text/plain" data-cookie-consent="analytics" src="https://example.com/tracker.js" async></script>
```

A module script keeps its type in `data-type="module"`.

An embedded content gets its address from `data-src`. Until the visitor consents, a placeholder explains why it is
hidden and lets the visitor allow its category; `data-cookie-consent-service` names the third party in it:

```html
<iframe data-cookie-consent="media" data-cookie-consent-service="YouTube"
        data-src="https://www.youtube-nocookie.com/embed/VIDEO_ID" title="…"></iframe>
```

On the server side, `cookie_consent_granted('media')` tells whether the visitor consented. Do not use it in pages
cached for every visitor.

### Google consent mode

With `google_consent_mode.enabled`, the style sheet template denies the configured signals before any Google tag loads,
and the script sends the choice of the visitor with `gtag('consent', 'update', …)`. Render the style sheet template
before your Google tags in the `<head>`. The consent mode does not replace the blocking: without consent, Google tags
still send cookieless pings.

### JavaScript

```js
window.cookieConsent.isGranted('analytics'); // once the script has run
window.cookieConsent.open(); // opens the preferences

// Dispatched when the script starts, then after each choice
document.addEventListener('cookie-consent:update', (event) => {
  if (event.detail.categories.includes('analytics')) {
    // start what needs this consent
  }
});
```

Any element with `data-cookie-consent-action="open"` opens the preferences. A script cannot be unloaded: when the
visitor withdraws a consent, the page is loaded again, after the cookies of the category are deleted.

## Theming

Override the custom properties of `.cookie-consent` and `.cookie-consent-placeholder`:

```css
.cookie-consent,
.cookie-consent-placeholder {
  --cookie-consent-font-family: 'Inter', sans-serif;
  --cookie-consent-text: #121212;
  --cookie-consent-button-background: #121212;
  --cookie-consent-radius: 0;
}
```

See [public/cookie-consent.css](public/cookie-consent.css) for the full list. Keep accepting and rejecting with the
same style.

## Proof of consent

Each choice is recorded with a random identifier, the version of the policy, the granted categories, the date and the
channel: no IP address, no customer. The identifier stays the same when the visitor changes the choice, and is
shown in the preferences, so that the visitor can refer to it. The admin lists the records under Configuration ›
Cookie consents.

## Limitations

- Without JavaScript, nothing is unblocked and the banner is not shown.
- The texts of the banner are yours: they must match the categories and services you configure.
- Another site cannot make a visitor consent: the choice is only accepted as JSON, which browsers cannot send from
  another site without a CORS preflight.

## Development

```bash
docker compose up -d # MySQL on port 3306, or set MYSQL_PORT
vendor/bin/console doctrine:database:create --env=test
vendor/bin/console doctrine:schema:create --env=test
vendor/bin/phpunit
node --test tests/Js/*.test.js
vendor/bin/ecs check
vendor/bin/phpstan analyse
```

## License

[MIT](LICENSE)
