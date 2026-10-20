<?php

/*
 * This file is part of the atriametal/sylius-cookie-consent-plugin package.
 *
 * (c) Atriametal <contact@kemp.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Atriametal\SyliusCookieConsentPlugin\Twig;

use Atriametal\SyliusCookieConsentPlugin\Consent\Category;
use Atriametal\SyliusCookieConsentPlugin\Consent\ConsentCookie;
use Atriametal\SyliusCookieConsentPlugin\Consent\ConsentPolicy;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class CookieConsentExtension extends AbstractExtension
{
    /**
     * @param array<string, mixed>  $privacyPolicyRouteParameters
     * @param array<string, string> $googleConsentModeSignals     Category of each Google consent mode signal, empty if the consent mode is disabled
     */
    public function __construct(
        private readonly ConsentPolicy $policy,
        private readonly ConsentCookie $cookie,
        private readonly RequestStack $requestStack,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly ?string $privacyPolicyRoute,
        private readonly array $privacyPolicyRouteParameters,
        private readonly array $googleConsentModeSignals,
    ) {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('cookie_consent_granted', $this->isGranted(...)),
            new TwigFunction('cookie_consent_categories', $this->policy->categories(...)),
            new TwigFunction('cookie_consent_privacy_policy_url', $this->privacyPolicyUrl(...)),
            new TwigFunction('cookie_consent_client_config', $this->clientConfig(...)),
        ];
    }

    /**
     * Whether the visitor has consented to the category. Pages cached for every visitor must instead block their
     * scripts with the data-cookie-consent attribute, which the browser script checks.
     */
    public function isGranted(string $category): bool
    {
        $request = $this->requestStack->getMainRequest();

        return null !== $request && true === $this->cookie->read($request)?->grants($category);
    }

    public function privacyPolicyUrl(): ?string
    {
        return null === $this->privacyPolicyRoute ? null : $this->urlGenerator->generate($this->privacyPolicyRoute, $this->privacyPolicyRouteParameters);
    }

    /**
     * The configuration of the browser script.
     *
     * @return array{endpoint: string, cookie: array{name: string, domain: ?string}, version: string, categories: array<string, array{required: bool, cookies: list<string>}>, googleConsentMode: array<string, string>}
     */
    public function clientConfig(): array
    {
        return [
            'endpoint' => $this->urlGenerator->generate('atriametal_cookie_consent_shop_save'),
            'cookie' => ['name' => $this->cookie->name(), 'domain' => $this->cookie->domain()],
            'version' => $this->policy->version,
            'categories' => array_map(
                static fn (Category $category): array => ['required' => $category->required, 'cookies' => $category->cookies],
                $this->policy->categories(),
            ),
            'googleConsentMode' => $this->googleConsentModeSignals,
        ];
    }
}
