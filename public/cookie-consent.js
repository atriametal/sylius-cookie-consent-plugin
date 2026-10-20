/*
 * This file is part of the atriametal/sylius-cookie-consent-plugin package.
 *
 * (c) Atriametal <contact@kemp.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Shows the banner, records the choice of the visitor and unblocks what the visitor consents to:
// <script type="text/plain" data-cookie-consent="analytics"> and <iframe data-cookie-consent="media" data-src="…">.

import {
  consentModeState,
  cookieDomains,
  cookieMatches,
  isUnblocked,
  readConsent,
  requiredCategories,
  revokedCategories,
} from './consent.js';

const root = document.querySelector('[data-cookie-consent-root]');

if (root) {
  const config = JSON.parse(root.dataset.config);
  const banner = root.querySelector('[data-cookie-consent-banner]');
  const dialog = root.querySelector('[data-cookie-consent-dialog]');
  const status = root.querySelector('[data-cookie-consent-status]');
  const allCategories = Object.keys(config.categories);

  let consent = readConsent(document.cookie, config.cookie.name, config.version);
  let granted = consent ? consent.categories : requiredCategories(config.categories);

  const gtag = function gtag() {
    window.dataLayer = window.dataLayer || [];
    // Google tags expect the arguments object, not an array
    // eslint-disable-next-line prefer-rest-params
    window.dataLayer.push(arguments);
  };

  const updateConsentMode = () => {
    if (Object.keys(config.googleConsentMode).length > 0) {
      gtag('consent', 'update', consentModeState(config.googleConsentMode, granted));
    }
  };

  const unblockScript = (blocked) => {
    const script = document.createElement('script');

    [...blocked.attributes]
      .filter(({ name }) => !['type', 'data-cookie-consent', 'data-type'].includes(name))
      .forEach(({ name, value }) => script.setAttribute(name, value));
    if (blocked.dataset.type) {
      script.type = blocked.dataset.type;
    }
    // Scripts inserted by a script run as soon as they load: keep the order of the page
    script.async = blocked.hasAttribute('async');
    script.textContent = blocked.textContent;
    blocked.replaceWith(script);
  };

  const placeholderOf = (element) => {
    const previous = element.previousElementSibling;

    return previous && previous.hasAttribute('data-cookie-consent-placeholder-of') ? previous : null;
  };

  const showPlaceholder = (element) => {
    const [category] = element.dataset.cookieConsent.split(/\s+/);
    const template = root.querySelector(`template[data-cookie-consent-placeholder="${category}"]`);

    if (!template || placeholderOf(element)) {
      return;
    }

    const placeholder = template.content.firstElementChild.cloneNode(true);
    const text = placeholder.querySelector('[data-cookie-consent-placeholder-text]');
    const { cookieConsentService: service } = element.dataset;

    if (service) {
      text.textContent = text.dataset.withService.replace('{service}', service);
    }
    element.hidden = true;
    element.before(placeholder);
  };

  const unblockEmbed = (element) => {
    placeholderOf(element)?.remove();
    element.hidden = false;
    element.setAttribute('src', element.dataset.src);
    element.removeAttribute('data-src');
  };

  const applyConsent = () => {
    document.querySelectorAll('script[type="text/plain"][data-cookie-consent]').forEach((script) => {
      if (isUnblocked(script.dataset.cookieConsent, granted)) {
        unblockScript(script);
      }
    });
    document.querySelectorAll('[data-cookie-consent][data-src]:not(script)').forEach((element) => {
      if (isUnblocked(element.dataset.cookieConsent, granted)) {
        unblockEmbed(element);
      } else {
        showPlaceholder(element);
      }
    });
  };

  const deleteCookiesOf = (categories) => {
    const patterns = categories.flatMap((category) => config.categories[category]?.cookies || []);
    const names = document.cookie.split(';').map((cookie) => cookie.split('=')[0].trim()).filter(Boolean);

    names
      .filter((name) => patterns.some((pattern) => cookieMatches(pattern, name)))
      .forEach((name) => {
        const expired = `${name}=; Max-Age=0; Path=/`;
        document.cookie = expired;
        cookieDomains(window.location.hostname).forEach((domain) => {
          document.cookie = `${expired}; Domain=${domain}`;
        });
      });
  };

  const showError = (visible) => {
    root.querySelectorAll('[data-cookie-consent-error]').forEach((error) => {
      error.hidden = !visible;
    });
  };

  const announce = (message) => {
    status.textContent = '';
    window.setTimeout(() => {
      status.textContent = message;
    }, 100);
  };

  const save = async (chosen) => {
    showError(false);
    root.setAttribute('aria-busy', 'true');

    try {
      const response = await fetch(config.endpoint, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ categories: chosen }),
        credentials: 'same-origin',
      });
      if (!response.ok) {
        throw new Error(`The consent was not saved: HTTP ${response.status}`);
      }
    } catch {
      showError(true);

      return;
    } finally {
      root.removeAttribute('aria-busy');
    }

    const previous = granted;
    consent = readConsent(document.cookie, config.cookie.name, config.version);
    granted = consent ? consent.categories : requiredCategories(config.categories);

    banner.hidden = true;
    if (dialog.open) {
      dialog.close();
    }
    updateConsentMode();
    document.dispatchEvent(new CustomEvent('cookie-consent:update', { detail: { categories: granted } }));

    const revoked = revokedCategories(previous, granted);
    if (revoked.length > 0) {
      // A script cannot be unloaded: the page is loaded again without it
      deleteCookiesOf(revoked);
      window.location.reload();

      return;
    }

    applyConsent();
    announce(status.dataset.saved);
  };

  const openPreferences = () => {
    dialog.querySelectorAll('[data-cookie-consent-category-input]').forEach((input) => {
      input.checked = granted.includes(input.value);
    });

    const idContainer = dialog.querySelector('[data-cookie-consent-id-container]');
    idContainer.hidden = !consent;
    idContainer.querySelector('[data-cookie-consent-id]').textContent = consent ? consent.id : '';

    showError(false);
    dialog.showModal();
  };

  const chosenInDialog = () => [...dialog.querySelectorAll('[data-cookie-consent-category-input]:checked')]
    .map((input) => input.value);

  const actions = {
    'accept-all': () => save(allCategories),
    'reject-all': () => save([]),
    save: () => save(chosenInDialog()),
    open: openPreferences,
    close: () => dialog.close(),
    allow: (button) => save([...new Set([...granted, button.dataset.cookieConsentCategory])]),
  };

  document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-cookie-consent-action]');
    const action = button && actions[button.dataset.cookieConsentAction];

    if (action) {
      event.preventDefault();
      action(button);
    }
  });

  window.cookieConsent = {
    isGranted: (category) => granted.includes(category),
    open: openPreferences,
  };

  updateConsentMode();
  applyConsent();
  banner.hidden = consent !== null;
  document.dispatchEvent(new CustomEvent('cookie-consent:update', { detail: { categories: granted } }));
}
