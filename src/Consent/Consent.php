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
 * The choice of a visitor. The identifier stays the same when the visitor changes the choice, so that the records of
 * the successive choices of a visitor can be found from the identifier shown in the preferences.
 */
final class Consent
{
    /**
     * @param list<string> $categories The granted categories, including the required ones
     */
    public function __construct(
        public readonly string $id,
        public readonly string $policyVersion,
        public readonly array $categories,
        public readonly \DateTimeImmutable $givenAt,
    ) {
    }

    public static function generateId(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = \chr(\ord($bytes[6]) & 0x0F | 0x40);
        $bytes[8] = \chr(\ord($bytes[8]) & 0x3F | 0x80);

        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($bytes), 4));
    }

    public function grants(string $category): bool
    {
        return \in_array($category, $this->categories, true);
    }
}
