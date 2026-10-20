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
 * A purpose the visitor consents to, such as audience measurement or embedded videos.
 */
final class Category
{
    /**
     * @param list<string> $services Names of the third parties shown to the visitor, such as "Google Analytics"
     * @param list<string> $cookies  Names of the cookies deleted when the visitor withdraws the consent, "*" matching any characters
     */
    public function __construct(
        public readonly string $name,
        public readonly bool $required = false,
        public readonly array $services = [],
        public readonly array $cookies = [],
    ) {
    }
}
