/*
 * This file is part of the atriametal/sylius-cookie-consent-plugin package.
 *
 * (c) Atriametal <contact@kemp.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import {
  categoriesOf,
  consentModeState,
  cookieDomains,
  cookieMatches,
  isUnblocked,
  readConsent,
  requiredCategories,
  revokedCategories,
} from '../../public/consent.js';

const cookieValue = (consent) => encodeURIComponent(JSON.stringify(consent));
const consent = { id: '0c7a3f9e-1b2d-4c5e-8f9a-0b1c2d3e4f5a', version: '2', categories: ['necessary', 'media'], date: '2026-10-20T10:00:00+00:00' };

describe('readConsent', () => {
  it('reads the consent to the current version', () => {
    assert.deepEqual(
      readConsent(`other=1; cookie_consent=${cookieValue(consent)}`, 'cookie_consent', '2'),
      { id: consent.id, categories: ['necessary', 'media'] },
    );
  });

  it('ignores a consent to an earlier version, so that the visitor is asked again', () => {
    assert.equal(readConsent(`cookie_consent=${cookieValue(consent)}`, 'cookie_consent', '3'), null);
  });

  it('ignores a missing or malformed cookie', () => {
    assert.equal(readConsent('other=1', 'cookie_consent', '2'), null);
    assert.equal(readConsent('cookie_consent=%7Bnot-json', 'cookie_consent', '2'), null);
    assert.equal(readConsent(`cookie_consent=${cookieValue({ ...consent, categories: 'media' })}`, 'cookie_consent', '2'), null);
  });

  it('does not mistake a cookie whose name ends with the cookie name', () => {
    assert.equal(readConsent(`old_cookie_consent=${cookieValue(consent)}`, 'cookie_consent', '2'), null);
  });
});

describe('isUnblocked', () => {
  it('unblocks an element when any of its categories is granted', () => {
    assert.equal(isUnblocked('analytics marketing', ['necessary', 'marketing']), true);
    assert.equal(isUnblocked('analytics', ['necessary', 'marketing']), false);
  });

  it('keeps an element without category blocked', () => {
    assert.deepEqual(categoriesOf(null), []);
    assert.equal(isUnblocked('  ', ['necessary']), false);
  });
});

describe('categories', () => {
  it('lists the required categories', () => {
    assert.deepEqual(requiredCategories({ necessary: { required: true }, media: { required: false } }), ['necessary']);
  });

  it('lists the categories the visitor no longer consents to', () => {
    assert.deepEqual(revokedCategories(['necessary', 'analytics', 'media'], ['necessary', 'media']), ['analytics']);
  });
});

describe('cookies', () => {
  it('matches the cookie names, "*" matching any characters', () => {
    assert.equal(cookieMatches('_ga', '_ga'), true);
    assert.equal(cookieMatches('_ga', '_gat'), false);
    assert.equal(cookieMatches('_ga_*', '_ga_ABC123'), true);
    assert.equal(cookieMatches('_gcl.au', '_gclxau'), false);
  });

  it('lists the domains the cookies may have been set on', () => {
    assert.deepEqual(cookieDomains('www.shop.example.com'), ['www.shop.example.com', 'shop.example.com', 'example.com']);
    assert.deepEqual(cookieDomains('localhost'), []);
  });
});

describe('consentModeState', () => {
  it('grants the Google consent mode signals of the granted categories', () => {
    assert.deepEqual(
      consentModeState({ analytics_storage: 'analytics', ad_storage: 'marketing' }, ['necessary', 'analytics']),
      { analytics_storage: 'granted', ad_storage: 'denied' },
    );
  });
});
