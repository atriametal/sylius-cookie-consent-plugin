# Changelog

All notable changes to this project are documented in this file. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- The plugin is split into three packages, like the spam protection: the consent policy, the cookie and the records
  move to [florentingarnier/cookie-consent](https://github.com/FlorentinGarnier/cookie-consent), and the banner, the
  browser script, the endpoint receiving the choices, the purge command and the configuration to
  [florentingarnier/cookie-consent-bundle](https://github.com/FlorentinGarnier/cookie-consent-bundle), which work
  without Sylius. The plugin keeps what is specific to Sylius: the template events and Twig hooks of the shop, the
  channel of each record, the records in the admin and the texts worded for a shop.
- The records keep their table, `atriametal_cookie_consent_record`, and its columns: no migration is needed.

### Upgrading from 0.1

- Enable `FlorentinGarnier\CookieConsentBundle\FlorentinGarnierCookieConsentBundle` before the plugin in your
  `config/bundles.php`, if you do not use Symfony Flex.
- Rename the configuration key `atriametal_sylius_cookie_consent` to `florentin_garnier_cookie_consent`, and the file
  holding it. Its options are unchanged.
- Rename the translation keys of the banner, the preferences, the categories and the placeholders from
  `atriametal_cookie_consent.*` to `florentin_garnier_cookie_consent.*` where you override them. The texts of the
  admin keep their `atriametal_cookie_consent.ui.*` keys.
- Replace `@AtriametalSyliusCookieConsentPlugin/shop/<template>` with `@FlorentinGarnierCookieConsent/<template>`,
  for instance in the include of the preferences link of your footer.
- Run `bin/console assets:install`: the script and the style sheet are now served from
  `bundles/florentingarniercookieconsent/`. Update the path where your theme loads them itself.
- The route receiving the choices is renamed `florentin_garnier_cookie_consent_save`; its path, `/cookie-consent`, is
  unchanged. The import of `@AtriametalSyliusCookieConsentPlugin/config/routes/shop.php` keeps working.
- The PHP classes moved: `Atriametal\SyliusCookieConsentPlugin\Consent\*` to `FlorentinGarnier\CookieConsent\*`,
  and the controller, the command and the Twig extension to `FlorentinGarnier\CookieConsentBundle\*`.
  `ConsentRecordInterface` and `ConsentRecordRepository` are removed: the entity extends
  `FlorentinGarnier\CookieConsent\ConsentRecord`, and `FlorentinGarnier\CookieConsent\ConsentRecordRepositoryInterface`
  records and purges the consents.

## [0.1.1] - 2026-11-05

### Fixed

- Google consent mode: the default state sends the choice already made by the visitor instead of denying everything.
  Google tags evaluate each event of the data layer with the consent of that moment, so the events a page pushes
  before the browser script runs (`add_to_cart`, `begin_checkout`, `purchase`…) were denied even for a visitor who
  had accepted.

## [0.1.0] - 2026-10-20

### Added

- Banner and preferences asking for the consent of the visitor, purpose by purpose, with rejecting as easy as
  accepting.
- Blocking of the scripts and embedded contents until the visitor consents, with a placeholder for the embedded
  contents.
- Deletion of the cookies of a withdrawn purpose.
- Google consent mode v2.
- Records of the choices, listed in the admin, and the `cookie-consent:purge-records` command.
- Translations in English, French and German.
- Support of Sylius 1.14 and 2.x.

[Unreleased]: https://github.com/atriametal/sylius-cookie-consent-plugin/compare/v0.1.1...HEAD
[0.1.1]: https://github.com/atriametal/sylius-cookie-consent-plugin/compare/v0.1.0...v0.1.1
[0.1.0]: https://github.com/atriametal/sylius-cookie-consent-plugin/releases/tag/v0.1.0
