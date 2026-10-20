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

use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    /** The CNIL recommends keeping the choice of the visitor for 6 months, and the consent for 13 months at most. */
    private const MAX_COOKIE_LIFETIME_IN_DAYS = 395;

    /** A default value is not normalized: every key must be set. */
    private const DEFAULT_CATEGORIES = [
        'necessary' => ['required' => true, 'services' => [], 'cookies' => []],
        'analytics' => ['required' => false, 'services' => [], 'cookies' => []],
        'marketing' => ['required' => false, 'services' => [], 'cookies' => []],
        'media' => ['required' => false, 'services' => [], 'cookies' => []],
    ];

    private const DEFAULT_GOOGLE_CONSENT_MODE_SIGNALS = [
        'analytics_storage' => 'analytics',
        'ad_storage' => 'marketing',
        'ad_user_data' => 'marketing',
        'ad_personalization' => 'marketing',
    ];

    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('atriametal_sylius_cookie_consent');
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->scalarNode('policy_version')
                    ->info('Changing it asks every visitor for consent again, for instance after adding a category or a service.')
                    ->defaultValue('1')
                    ->cannotBeEmpty()
                ->end()
                ->arrayNode('cookie')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('name')->defaultValue('cookie_consent')->cannotBeEmpty()->end()
                        ->integerNode('lifetime')
                            ->info('In days. The visitor is asked again once it expires.')
                            ->defaultValue(182)
                            ->min(1)
                            ->max(self::MAX_COOKIE_LIFETIME_IN_DAYS)
                        ->end()
                        ->scalarNode('domain')
                            ->info('Set it, for instance to ".example.com", to share the consent between subdomains.')
                            ->defaultNull()
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('privacy_policy')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->scalarNode('route')->info('The route of the page describing the cookies, linked from the banner.')->defaultNull()->end()
                        ->arrayNode('parameters')->normalizeKeys(false)->variablePrototype()->end()->end()
                    ->end()
                ->end()
                ->append($this->categoriesNode())
                ->arrayNode('google_consent_mode')
                    ->info('Sends the choice of the visitor to the Google tags (Google consent mode v2).')
                    ->canBeEnabled()
                    ->children()
                        ->arrayNode('signals')
                            ->info('The category granting each signal.')
                            ->normalizeKeys(false)
                            ->useAttributeAsKey('signal')
                            ->scalarPrototype()->end()
                            ->defaultValue(self::DEFAULT_GOOGLE_CONSENT_MODE_SIGNALS)
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('records')
                    ->addDefaultsIfNotSet()
                    ->children()
                        ->integerNode('retention')
                            ->info('In days: the cookie-consent:purge-records command removes the older records.')
                            ->defaultValue(self::MAX_COOKIE_LIFETIME_IN_DAYS)
                            ->min(1)
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }

    private function categoriesNode(): ArrayNodeDefinition
    {
        $node = (new TreeBuilder('categories'))->getRootNode();

        $node
            ->info('Replaces the default categories. Their labels and descriptions are translated from atriametal_cookie_consent.categories.<name>.')
            ->useAttributeAsKey('name')
            ->performNoDeepMerging()
            ->requiresAtLeastOneElement()
            ->defaultValue(self::DEFAULT_CATEGORIES)
            ->validate()
                ->ifTrue(static fn (array $categories): bool => [] !== array_filter(
                    array_keys($categories),
                    static fn (int|string $name): bool => 1 !== preg_match('/^[a-z][a-z0-9_]*$/', (string) $name),
                ))
                ->thenInvalid('The category names must be made of lowercase letters, digits and underscores: %s')
            ->end()
            ->arrayPrototype()
                ->children()
                    ->booleanNode('required')->info('A required category is always granted: only for the cookies strictly necessary to the shop.')->defaultFalse()->end()
                    ->arrayNode('services')->info('The third parties shown to the visitor.')->scalarPrototype()->end()->end()
                    ->arrayNode('cookies')->info('The cookies deleted when the visitor withdraws the consent, "*" matching any characters.')->scalarPrototype()->end()->end()
                ->end()
            ->end()
        ;

        return $node;
    }
}
