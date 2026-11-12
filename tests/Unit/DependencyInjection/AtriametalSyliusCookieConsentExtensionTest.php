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

use Atriametal\SyliusCookieConsentPlugin\Channel\SyliusChannelCodeProvider;
use Atriametal\SyliusCookieConsentPlugin\DependencyInjection\AtriametalSyliusCookieConsentExtension;
use Atriametal\SyliusCookieConsentPlugin\Entity\ConsentRecord;
use Atriametal\SyliusCookieConsentPlugin\Menu\AdminMenuListener;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Bundle\CoreBundle\SyliusCoreBundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

#[CoversClass(AtriametalSyliusCookieConsentExtension::class)]
final class AtriametalSyliusCookieConsentExtensionTest extends TestCase
{
    private const TEMPLATES = [
        'atriametal_cookie_consent_stylesheets' => '@FlorentinGarnierCookieConsent/stylesheets.html.twig',
        'atriametal_cookie_consent_banner' => '@FlorentinGarnierCookieConsent/banner.html.twig',
        'atriametal_cookie_consent_javascripts' => '@FlorentinGarnierCookieConsent/javascripts.html.twig',
    ];

    public function testItLoadsTheServicesOfTheShop(): void
    {
        $container = new ContainerBuilder();

        (new AtriametalSyliusCookieConsentExtension())->load([], $container);

        self::assertTrue($container->hasDefinition(SyliusChannelCodeProvider::class));
        self::assertTrue($container->hasDefinition(AdminMenuListener::class));
    }

    public function testTheBundleStoresTheRecordsAsAResourceWithTheChannel(): void
    {
        self::assertSame(
            [['records' => ['class' => ConsentRecord::class], 'channel_code_provider' => SyliusChannelCodeProvider::class]],
            self::prepended('florentin_garnier_cookie_consent'),
        );
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
