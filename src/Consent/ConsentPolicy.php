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

namespace Atriametal\SyliusCookieConsentPlugin\Consent;

/**
 * The categories the visitors are asked to consent to. Changing its version asks every visitor for consent again.
 */
final class ConsentPolicy
{
    /** @var array<string, Category> */
    private readonly array $categories;

    /**
     * @param list<Category> $categories
     */
    public function __construct(
        public readonly string $version,
        array $categories,
    ) {
        $byName = [];
        foreach ($categories as $category) {
            $byName[$category->name] = $category;
        }
        $this->categories = $byName;
    }

    /**
     * @param array<string, array{required: bool, services: list<string>, cookies: list<string>}> $categories As configured, by name
     */
    public static function fromConfiguration(string $version, array $categories): self
    {
        return new self($version, array_map(
            static fn (string $name, array $category): Category => new Category($name, $category['required'], $category['services'], $category['cookies']),
            array_keys($categories),
            $categories,
        ));
    }

    /**
     * @return array<string, Category>
     */
    public function categories(): array
    {
        return $this->categories;
    }

    public function has(string $category): bool
    {
        return isset($this->categories[$category]);
    }

    /**
     * Completes the categories chosen by the visitor with the required ones, in the order of the policy.
     *
     * @param array<array-key, mixed> $chosen
     *
     * @return list<string>
     *
     * @throws UnknownCategoryException
     */
    public function grantedCategories(array $chosen): array
    {
        foreach ($chosen as $category) {
            if (!\is_string($category) || !$this->has($category)) {
                throw new UnknownCategoryException(\is_scalar($category) ? (string) $category : get_debug_type($category));
            }
        }

        return array_keys(array_filter(
            $this->categories,
            static fn (Category $category): bool => $category->required || \in_array($category->name, $chosen, true),
        ));
    }
}
