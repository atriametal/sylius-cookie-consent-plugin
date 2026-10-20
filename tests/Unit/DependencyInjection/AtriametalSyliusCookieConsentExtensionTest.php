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

namespace Tests\Atriametal\SyliusCookieConsentPlugin\Unit\DependencyInjection;

use Atriametal\SyliusCookieConsentPlugin\DependencyInjection\AtriametalSyliusCookieConsentExtension;
use Atriametal\SyliusCookieConsentPlugin\DependencyInjection\Configuration;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\SyliusCoreBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(AtriametalSyliusCookieConsentExtension::class)]
#[CoversClass(Configuration::class)]
final class AtriametalSyliusCookieConsentExtensionTest extends TestCase
{
    private const TEMPLATES = [
        'atriametal_cookie_consent_stylesheets' => '@AtriametalSyliusCookieConsentPlugin/shop/stylesheets.html.twig',
        'atriametal_cookie_consent_banner' => '@AtriametalSyliusCookieConsentPlugin/shop/banner.html.twig',
        'atriametal_cookie_consent_javascripts' => '@AtriametalSyliusCookieConsentPlugin/shop/javascripts.html.twig',
    ];

    public function testTheDefaultCategoriesAskForEveryUsualPurpose(): void
    {
        $container = self::load([]);

        self::assertSame('1', $container->getParameter('atriametal_cookie_consent.policy_version'));
        self::assertSame(
            [
                'necessary' => ['required' => true, 'services' => [], 'cookies' => []],
                'analytics' => ['required' => false, 'services' => [], 'cookies' => []],
                'marketing' => ['required' => false, 'services' => [], 'cookies' => []],
                'media' => ['required' => false, 'services' => [], 'cookies' => []],
            ],
            $container->getParameter('atriametal_cookie_consent.categories'),
        );
        self::assertSame(182, $container->getParameter('atriametal_cookie_consent.cookie.lifetime'));
        self::assertSame([], $container->getParameter('atriametal_cookie_consent.google_consent_mode.signals'));
    }

    public function testTheConfiguredCategoriesReplaceTheDefaultOnes(): void
    {
        $container = self::load([
            ['categories' => ['necessary' => ['required' => true], 'media' => ['services' => ['YouTube']]]],
        ]);

        self::assertEquals(
            [
                'necessary' => ['required' => true, 'services' => [], 'cookies' => []],
                'media' => ['required' => false, 'services' => ['YouTube'], 'cookies' => []],
            ],
            $container->getParameter('atriametal_cookie_consent.categories'),
        );
    }

    public function testTheGoogleConsentModeSendsTheConfiguredSignals(): void
    {
        $container = self::load([['google_consent_mode' => ['enabled' => true]]]);

        self::assertSame(
            ['analytics_storage' => 'analytics', 'ad_storage' => 'marketing', 'ad_user_data' => 'marketing', 'ad_personalization' => 'marketing'],
            $container->getParameter('atriametal_cookie_consent.google_consent_mode.signals'),
        );
    }

    public function testAGoogleConsentModeSignalMustReferToAConfiguredCategory(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionMessage('The Google consent mode signal "ad_storage" refers to the "marketing" category, which is not configured.');

        self::load([['categories' => ['necessary' => ['required' => true], 'analytics' => []], 'google_consent_mode' => ['enabled' => true]]]);
    }

    public function testACategoryNameMustBeUsableInTheTranslationKeysAndTheAttributes(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        self::load([['categories' => ['Social networks' => []]]]);
    }

    public function testTheCookieCannotOutliveTheConsent(): void
    {
        $this->expectException(InvalidConfigurationException::class);

        self::load([['cookie' => ['lifetime' => 396]]]);
    }

    public function testItRegistersTheConsentRecordsAsAResourceWithItsMappingAndMigrations(): void
    {
        $container = new ContainerBuilder();

        (new AtriametalSyliusCookieConsentExtension())->prepend($container);

        self::assertIsArray(self::get($container->getExtensionConfig('sylius_resource'), 0, 'resources', 'atriametal_cookie_consent.consent_record'));
        self::assertIsArray(self::get($container->getExtensionConfig('sylius_grid'), 0, 'grids', 'atriametal_cookie_consent_admin_consent_record'));
        self::assertDirectoryExists(self::stringAt($container->getExtensionConfig('doctrine'), 0, 'orm', 'mappings', 'AtriametalSyliusCookieConsentPlugin', 'dir'));
        self::assertDirectoryExists(self::stringAt($container->getExtensionConfig('doctrine_migrations'), 0, 'migrations_paths', 'Atriametal\SyliusCookieConsentPlugin\Migrations'));
    }

    public function testItRendersTheBannerInTheLayoutTemplateEventsOfTheShop(): void
    {
        if ('1' !== SyliusCoreBundle::MAJOR_VERSION) {
            self::markTestSkipped('Sylius 1 only.');
        }

        $config = self::prepended('sylius_ui');

        foreach (['sylius.shop.layout.stylesheets', 'sylius.shop.layout.before_body', 'sylius.shop.layout.javascripts'] as $index => $event) {
            $block = array_keys(self::TEMPLATES)[$index];
            self::assertSame(self::TEMPLATES[$block], self::get($config, 0, 'events', $event, 'blocks', $block, 'template'), $event);
        }
    }

    public function testItRendersTheBannerInTheLayoutHooksOfTheShop(): void
    {
        if ('1' === SyliusCoreBundle::MAJOR_VERSION) {
            self::markTestSkipped('Sylius 2 only.');
        }

        $config = self::prepended('sylius_twig_hooks');

        foreach (['sylius_shop.base#stylesheets', 'sylius_shop.base.header', 'sylius_shop.base#javascripts'] as $index => $hook) {
            $block = array_keys(self::TEMPLATES)[$index];
            self::assertSame(self::TEMPLATES[$block], self::get($config, 0, 'hooks', $hook, $block, 'template'), $hook);
        }
    }

    /**
     * @param list<array<string, mixed>> $configs
     */
    private static function load(array $configs): ContainerBuilder
    {
        $container = new ContainerBuilder();

        (new AtriametalSyliusCookieConsentExtension())->load($configs, $container);

        return $container;
    }

    /**
     * @return array<array<string, mixed>>
     */
    private static function prepended(string $extension): array
    {
        $container = new ContainerBuilder();

        (new AtriametalSyliusCookieConsentExtension())->prepend($container);

        return $container->getExtensionConfig($extension);
    }

    private static function stringAt(mixed $config, int|string ...$path): string
    {
        $value = self::get($config, ...$path);
        self::assertIsString($value);

        return $value;
    }

    private static function get(mixed $config, int|string ...$path): mixed
    {
        foreach ($path as $key) {
            self::assertIsArray($config);
            self::assertArrayHasKey($key, $config);
            $config = $config[$key];
        }

        return $config;
    }
}
