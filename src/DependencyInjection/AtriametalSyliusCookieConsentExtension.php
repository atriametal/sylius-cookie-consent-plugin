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

namespace Atriametal\SyliusCookieConsentPlugin\DependencyInjection;

use Atriametal\SyliusCookieConsentPlugin\Channel\SyliusChannelCodeProvider;
use Atriametal\SyliusCookieConsentPlugin\Entity\ConsentRecord;
use Sylius\Bundle\CoreBundle\SyliusCoreBundle;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * The consent is configured under florentin_garnier_cookie_consent, the key of the bundle.
 */
final class AtriametalSyliusCookieConsentExtension extends Extension implements PrependExtensionInterface
{
    private const TEMPLATES = '@FlorentinGarnierCookieConsent/';

    /**
     * The banner is rendered at the top of the page, so that keyboard and screen reader users reach it first.
     *
     * @var array<string, array{0: string, 1: string}> By template: the Sylius 1 template event and the Sylius 2 hook
     */
    private const BLOCKS = [
        'stylesheets' => ['sylius.shop.layout.stylesheets', 'sylius_shop.base#stylesheets'],
        'banner' => ['sylius.shop.layout.before_body', 'sylius_shop.base.header'],
        'javascripts' => ['sylius.shop.layout.javascripts', 'sylius_shop.base#javascripts'],
    ];

    public function load(array $configs, ContainerBuilder $container): void
    {
        (new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config')))->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $pluginDirectory = \dirname(__DIR__, 2);

        $container->prependExtensionConfig('florentin_garnier_cookie_consent', [
            'records' => ['class' => ConsentRecord::class],
            'channel_code_provider' => SyliusChannelCodeProvider::class,
        ]);

        $container->prependExtensionConfig('doctrine', ['orm' => ['mappings' => ['AtriametalSyliusCookieConsentPlugin' => [
            'type' => 'xml',
            'dir' => $pluginDirectory . '/config/doctrine',
            'prefix' => 'Atriametal\SyliusCookieConsentPlugin\Entity',
            'is_bundle' => false,
        ]]]]);
        $container->prependExtensionConfig('doctrine_migrations', ['migrations_paths' => [
            'Atriametal\SyliusCookieConsentPlugin\Migrations' => $pluginDirectory . '/migrations',
        ]]);
        $container->prependExtensionConfig('sylius_resource', ['resources' => ['atriametal_cookie_consent.consent_record' => [
            'classes' => ['model' => ConsentRecord::class],
        ]]]);
        $container->prependExtensionConfig('sylius_grid', ['grids' => ['atriametal_cookie_consent_admin_consent_record' => self::grid()]]);

        if ((int) SyliusCoreBundle::MAJOR_VERSION >= 2) {
            $hooks = [];
            foreach (self::BLOCKS as $block => [, $hook]) {
                $hooks[$hook]['atriametal_cookie_consent_' . $block] = ['template' => self::TEMPLATES . $block . '.html.twig', 'priority' => 1000];
            }
            $container->prependExtensionConfig('sylius_twig_hooks', ['hooks' => $hooks]);

            return;
        }

        $events = [];
        foreach (self::BLOCKS as $block => [$event]) {
            $events[$event]['blocks']['atriametal_cookie_consent_' . $block] = ['template' => self::TEMPLATES . $block . '.html.twig', 'priority' => 1000];
        }
        $container->prependExtensionConfig('sylius_ui', ['events' => $events]);
    }

    /**
     * @return array<string, mixed>
     */
    private static function grid(): array
    {
        return [
            'driver' => ['name' => 'doctrine/orm', 'options' => ['class' => '%atriametal_cookie_consent.model.consent_record.class%']],
            'sorting' => ['createdAt' => 'desc'],
            'fields' => [
                'createdAt' => ['type' => 'datetime', 'label' => 'atriametal_cookie_consent.ui.date', 'sortable' => null, 'options' => ['format' => 'Y-m-d H:i:s']],
                'consentId' => ['type' => 'string', 'label' => 'atriametal_cookie_consent.ui.consent_id_column'],
                'categories' => ['type' => 'twig', 'label' => 'atriametal_cookie_consent.ui.granted_categories', 'options' => [
                    'template' => '@AtriametalSyliusCookieConsentPlugin/admin/grid/categories.html.twig',
                ]],
                'policyVersion' => ['type' => 'string', 'label' => 'atriametal_cookie_consent.ui.policy_version'],
                'channelCode' => ['type' => 'string', 'label' => 'sylius.ui.channel'],
            ],
            'filters' => [
                'consentId' => ['type' => 'string', 'label' => 'atriametal_cookie_consent.ui.consent_id_column', 'options' => ['type' => 'equal']],
                'createdAt' => ['type' => 'date', 'label' => 'atriametal_cookie_consent.ui.date'],
            ],
        ];
    }
}
