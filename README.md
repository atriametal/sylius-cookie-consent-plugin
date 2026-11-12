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

The consent itself is provided by
[florentingarnier/cookie-consent-bundle](https://github.com/FlorentinGarnier/cookie-consent-bundle), which works in
any Symfony application. The plugin renders its banner in the shop, records the channel of each choice, lists the
records in the admin and words the texts for a shop.

| Package | Purpose |
|---------|---------|
| [florentingarnier/cookie-consent](https://github.com/FlorentinGarnier/cookie-consent) | The consent policy, the cookie and the records, for any PHP application |
| [florentingarnier/cookie-consent-bundle](https://github.com/FlorentinGarnier/cookie-consent-bundle) | Symfony integration: banner, browser script, Doctrine records, console command |
| **atriametal/sylius-cookie-consent-plugin** (this one) | Renders the banner in the Sylius shop and lists the records in the admin |

## Upgrading from 0.1

The configuration, the templates, the browser script and the translations moved to the bundle in 0.2.0: follow the
[CHANGELOG](CHANGELOG.md#upgrading-from-01) to rename them.

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

2. If you do not use Symfony Flex, enable the bundle and the plugin, in this order:

   ```php
   // config/bundles.php
   return [
       // ...
       FlorentinGarnier\CookieConsentBundle\FlorentinGarnierCookieConsentBundle::class => ['all' => true],
       Atriametal\SyliusCookieConsentPlugin\AtriametalSyliusCookieConsentPlugin::class => ['all' => true],
   ];
   ```

3. Import the routes. The shop route, which receives the choices, is outside the locale prefix; the admin route goes
   under the admin prefix:

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
   {{ include('@FlorentinGarnierCookieConsent/preferences_link.html.twig') }}
   ```

6. Remove the consent records older than the retention period every day, for instance with cron:

   ```bash
   bin/console cookie-consent:purge-records
   ```

The banner, the style sheet and the script are rendered by the layout template events (Sylius 1) or Twig hooks
(Sylius 2) of the shop: a theme that keeps them needs nothing else.

| Template | Sylius 1 template event | Sylius 2 hook |
|----------|-------------------------|---------------|
| `@FlorentinGarnierCookieConsent/stylesheets.html.twig` | `sylius.shop.layout.stylesheets` | `sylius_shop.base#stylesheets` |
| `@FlorentinGarnierCookieConsent/banner.html.twig` | `sylius.shop.layout.before_body` | `sylius_shop.base.header` |
| `@FlorentinGarnierCookieConsent/javascripts.html.twig` | `sylius.shop.layout.javascripts` | `sylius_shop.base#javascripts` |

The blocks are named `atriametal_cookie_consent_stylesheets`, `atriametal_cookie_consent_banner` and
`atriametal_cookie_consent_javascripts`.

## Configuration

The consent is configured under `florentin_garnier_cookie_consent`: see the
[bundle documentation](https://github.com/FlorentinGarnier/cookie-consent-bundle#configuration) for the categories, the cookie, the privacy policy page and the Google
consent mode. The plugin sets `records.class` and `channel_code_provider`: leave them.

```yaml
# config/packages/florentin_garnier_cookie_consent.yaml
florentin_garnier_cookie_consent:
    policy_version: '1'
    privacy_policy:
        route: app_cookie_policy # the page describing your cookies, linked from the banner
    categories:
        necessary:
            required: true
        analytics:
            services: ['Google Analytics']
            cookies: ['_ga', '_ga_*']
        media:
            services: ['YouTube']
    google_consent_mode:
        enabled: true
```

The bundle translates the texts in English, French and German; the plugin words those about the purposes for a shop.
Override any text in your `translations/messages.<locale>.yaml`, under `florentin_garnier_cookie_consent`: the
texts of the banner must describe the purposes you actually use.

To block your scripts and embedded contents until the visitor consents, to use the Google consent mode, the
JavaScript API or to theme the banner, see the bundle documentation:
[blocking](https://github.com/FlorentinGarnier/cookie-consent-bundle#blocking-scripts-and-embedded-contents), [theming](https://github.com/FlorentinGarnier/cookie-consent-bundle#theming).

## Proof of consent

Each choice is recorded with a random identifier, the version of the policy, the granted categories, the date and the
code of the channel: no IP address, no customer. The identifier stays the same when the visitor changes the choice, and is
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
vendor/bin/ecs check
vendor/bin/phpstan analyse
```

## License

[MIT](LICENSE)
