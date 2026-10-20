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

use Atriametal\SyliusCookieConsentPlugin\Entity\ConsentRecord;
use Atriametal\SyliusCookieConsentPlugin\Repository\ConsentRecordRepository;
use Sylius\Bundle\CoreBundle\SyliusCoreBundle;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\Extension;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

final class AtriametalSyliusCookieConsentExtension extends Extension implements PrependExtensionInterface
{
    private const TEMPLATES = '@AtriametalSyliusCookieConsentPlugin/shop/';

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
        /** @var array{policy_version: string, cookie: array{name: string, lifetime: int, domain: ?string}, privacy_policy: array{route: ?string, parameters: array<string, mixed>}, categories: array<string, array{required: bool, services: list<string>, cookies: list<string>}>, google_consent_mode: array{enabled: bool, signals: array<string, string>}, records: array{retention: int}} $config */
        $config = $this->processConfiguration(new Configuration(), $configs);

        if ($config['google_consent_mode']['enabled']) {
            self::checkGoogleConsentModeSignals($config['google_consent_mode']['signals'], $config['categories']);
        }

        $parameters = [
            'policy_version' => $config['policy_version'],
            'categories' => $config['categories'],
            'cookie.name' => $config['cookie']['name'],
            'cookie.lifetime' => $config['cookie']['lifetime'],
            'cookie.domain' => $config['cookie']['domain'],
            'privacy_policy.route' => $config['privacy_policy']['route'],
            'privacy_policy.parameters' => $config['privacy_policy']['parameters'],
            'google_consent_mode.signals' => $config['google_consent_mode']['enabled'] ? $config['google_consent_mode']['signals'] : [],
            'records.retention' => $config['records']['retention'],
        ];
        foreach ($parameters as $name => $value) {
            $container->setParameter('atriametal_cookie_consent.' . $name, $value);
        }

        (new PhpFileLoader($container, new FileLocator(\dirname(__DIR__, 2) . '/config')))->load('services.php');
    }

    public function prepend(ContainerBuilder $container): void
    {
        $pluginDirectory = \dirname(__DIR__, 2);

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
            'classes' => ['model' => ConsentRecord::class, 'repository' => ConsentRecordRepository::class],
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

    /**
     * @param array<string, string> $signals
     * @param array<string, mixed>  $categories
     */
    private static function checkGoogleConsentModeSignals(array $signals, array $categories): void
    {
        foreach ($signals as $signal => $category) {
            if (!isset($categories[$category])) {
                throw new InvalidConfigurationException(\sprintf('The Google consent mode signal "%s" refers to the "%s" category, which is not configured.', $signal, $category));
            }
        }
    }
}
