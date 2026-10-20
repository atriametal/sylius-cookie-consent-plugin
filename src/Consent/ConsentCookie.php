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

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * Stores the consent in a cookie that the browser script reads too, hence not HTTP only. The value is the JSON
 * {"id": "…", "version": "…", "categories": […], "date": "…"}: changing it is a breaking change.
 */
final class ConsentCookie
{
    private const ID_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/';

    public function __construct(
        private readonly ConsentPolicy $policy,
        private readonly string $name,
        private readonly int $lifetimeInDays,
        private readonly ?string $domain = null,
    ) {
    }

    /**
     * Returns the consent of the visitor, or null if the visitor has not consented to the current version of the policy.
     */
    public function read(Request $request): ?Consent
    {
        $consent = $this->decode($request->cookies->get($this->name));

        return null !== $consent && $consent->policyVersion === $this->policy->version ? $consent : null;
    }

    /**
     * Returns the identifier of the previous consent of the visitor, even to an earlier version of the policy.
     */
    public function readId(Request $request): ?string
    {
        return $this->decode($request->cookies->get($this->name))?->id;
    }

    public function create(Consent $consent, bool $secure): Cookie
    {
        $value = json_encode([
            'id' => $consent->id,
            'version' => $consent->policyVersion,
            'categories' => $consent->categories,
            'date' => $consent->givenAt->format(\DATE_ATOM),
        ], \JSON_THROW_ON_ERROR | \JSON_UNESCAPED_SLASHES);

        return Cookie::create(
            $this->name,
            $value,
            $consent->givenAt->modify(\sprintf('+%d days', $this->lifetimeInDays)),
            '/',
            $this->domain,
            $secure,
            false,
            false,
            Cookie::SAMESITE_LAX,
        );
    }

    public function name(): string
    {
        return $this->name;
    }

    public function domain(): ?string
    {
        return $this->domain;
    }

    private function decode(mixed $value): ?Consent
    {
        if (!\is_string($value)) {
            return null;
        }

        try {
            $data = json_decode($value, true, 3, \JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return null;
        }

        if (!\is_array($data) || !self::isValid($data)) {
            return null;
        }

        $givenAt = \DateTimeImmutable::createFromFormat(\DATE_ATOM, $data['date']);

        return false === $givenAt ? null : new Consent($data['id'], $data['version'], array_values($data['categories']), $givenAt);
    }

    /**
     * @param array<array-key, mixed> $data
     *
     * @phpstan-assert-if-true array{id: string, version: string, categories: array<string>, date: string} $data
     */
    private static function isValid(array $data): bool
    {
        return \is_string($data['id'] ?? null) && 1 === preg_match(self::ID_PATTERN, $data['id']) &&
            \is_string($data['version'] ?? null) &&
            \is_array($data['categories'] ?? null) && array_is_list($data['categories']) &&
            [] === array_filter($data['categories'], static fn (mixed $category): bool => !\is_string($category)) &&
            \is_string($data['date'] ?? null);
    }
}
