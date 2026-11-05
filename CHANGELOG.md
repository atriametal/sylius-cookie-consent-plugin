# Changelog

All notable changes to this project are documented in this file. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

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
