/*
 * This file is part of the atriametal/sylius-cookie-consent-plugin package.
 *
 * (c) Atriametal <contact@kemp.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Rules of the consent, without any access to the page: tested with "node --test tests/Js".

/**
 * Returns the consent stored in the cookie header, or null if the visitor has not consented to the current version.
 *
 * @returns {{id: string, categories: string[]}|null}
 */
export function readConsent(cookieHeader, cookieName, version) {
  const prefix = `${cookieName}=`;
  const cookie = cookieHeader.split(';').map((part) => part.trim()).find((part) => part.startsWith(prefix));

  if (!cookie) {
    return null;
  }

  try {
    const consent = JSON.parse(decodeURIComponent(cookie.slice(prefix.length)));

    if (consent.version !== version || typeof consent.id !== 'string' || !Array.isArray(consent.categories)) {
      return null;
    }

    return { id: consent.id, categories: consent.categories };
  } catch {
    return null;
  }
}

/**
 * The categories listed in a data-cookie-consent attribute, separated by spaces: any of them unblocks the element.
 */
export function categoriesOf(attribute) {
  return (attribute || '').split(/\s+/).filter(Boolean);
}

export function isUnblocked(attribute, grantedCategories) {
  return categoriesOf(attribute).some((category) => grantedCategories.includes(category));
}

export function requiredCategories(categories) {
  return Object.keys(categories).filter((name) => categories[name].required);
}

/**
 * The categories granted before and no longer granted: their cookies must be deleted.
 */
export function revokedCategories(previous, next) {
  return previous.filter((category) => !next.includes(category));
}

/**
 * Whether a cookie name matches a configured name, "*" matching any characters.
 */
export function cookieMatches(pattern, name) {
  const escaped = pattern.split('*').map((part) => part.replace(/[.+?^${}()|[\]\\]/g, '\\$&'));

  return new RegExp(`^${escaped.join('.*')}$`).test(name);
}

/**
 * The domains a cookie may have been set on from this host: the host itself and each parent domain.
 */
export function cookieDomains(hostname) {
  const labels = hostname.split('.');

  return labels.slice(0, -1).map((_, index) => labels.slice(index).join('.'));
}

/**
 * The state of each Google consent mode signal, from the category granting it.
 */
export function consentModeState(signals, grantedCategories) {
  return Object.fromEntries(
    Object.entries(signals).map(([signal, category]) => [signal, grantedCategories.includes(category) ? 'granted' : 'denied']),
  );
}
