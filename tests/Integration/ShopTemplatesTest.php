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

namespace Tests\Atriametal\SyliusCookieConsentPlugin\Integration;

use Atriametal\SyliusCookieConsentPlugin\Twig\CookieConsentExtension;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

/**
 * The test application configures the policy version "2" with the necessary, analytics and media categories, a
 * privacy policy and the Google consent mode for the analytics_storage signal.
 */
#[CoversClass(CookieConsentExtension::class)]
final class ShopTemplatesTest extends KernelTestCase
{
    public function testTheBannerGivesTheBrowserScriptItsConfiguration(): void
    {
        $html = self::render('banner');

        self::assertSame(1, preg_match('/data-config="([^"]+)"/', $html, $matches));
        self::assertSame(
            [
                'endpoint' => '/cookie-consent',
                'cookie' => ['name' => 'cookie_consent', 'domain' => null],
                'version' => '2',
                'categories' => [
                    'necessary' => ['required' => true, 'cookies' => []],
                    'analytics' => ['required' => false, 'cookies' => ['_ga', '_ga_*']],
                    'media' => ['required' => false, 'cookies' => []],
                ],
                'googleConsentMode' => ['analytics_storage' => 'analytics'],
            ],
            json_decode(html_entity_decode($matches[1]), true),
        );
    }

    public function testRejectingIsAsEasyAsAccepting(): void
    {
        $html = self::render('banner');

        self::assertStringContainsString('<button type="button" class="cookie-consent__button" data-cookie-consent-action="reject-all">Reject all</button>', $html);
        self::assertStringContainsString('<button type="button" class="cookie-consent__button" data-cookie-consent-action="accept-all">Accept all</button>', $html);
    }

    public function testThePreferencesLetTheVisitorChooseEachOptionalCategory(): void
    {
        $html = self::render('banner');

        self::assertStringContainsString('<span class="cookie-consent__category-label">Strictly necessary</span>', $html);
        self::assertStringNotContainsString('value="necessary"', $html);
        self::assertStringContainsString('role="switch" id="cookie-consent-category-analytics" value="analytics"', $html);
        self::assertStringContainsString('Services: Google Analytics', $html);
        self::assertStringContainsString('<template data-cookie-consent-placeholder="media">', $html);
        self::assertStringNotContainsString('<template data-cookie-consent-placeholder="necessary">', $html);
        self::assertStringContainsString('class="cookie-consent__link" href="/en_US/"', $html);
    }

    public function testTheGoogleTagsAreDeniedEverythingUntilTheVisitorConsents(): void
    {
        self::assertStringContainsString(
            "gtag('consent', 'default', {\"analytics_storage\":\"denied\",\"wait_for_update\":500});",
            self::render('stylesheets'),
        );
    }

    public function testATemplateCanTellWhetherTheVisitorConsented(): void
    {
        $consent = (string) json_encode(['id' => '0c7a3f9e-1b2d-4c5e-8f9a-0b1c2d3e4f5a', 'version' => '2', 'categories' => ['necessary', 'media'], 'date' => '2026-10-20T10:00:00+00:00']);
        $template = "{{ cookie_consent_granted('media') ? 'media' }} {{ cookie_consent_granted('analytics') ? 'analytics' }}";

        self::assertSame('media ', self::renderTemplate($template, ['cookie_consent' => $consent]));
        self::assertSame(' ', self::renderTemplate($template, []));
    }

    private static function render(string $template): string
    {
        return self::renderTemplate(\sprintf("{{ include('@AtriametalSyliusCookieConsentPlugin/shop/%s.html.twig') }}", $template), []);
    }

    /**
     * @param array<string, string> $cookies
     */
    private static function renderTemplate(string $template, array $cookies): string
    {
        self::ensureKernelShutdown();
        self::bootKernel();

        $request = new Request(attributes: ['_locale' => 'en_US'], cookies: $cookies);
        $request->setLocale('en_US');
        $requestStack = self::getContainer()->get('request_stack');
        self::assertInstanceOf(RequestStack::class, $requestStack);
        $requestStack->push($request);

        $twig = self::getContainer()->get('twig');
        self::assertInstanceOf(Environment::class, $twig);

        return $twig->createTemplate($template)->render();
    }
}
