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

namespace Atriametal\SyliusCookieConsentPlugin\Entity;

use Atriametal\SyliusCookieConsentPlugin\Consent\Consent;

/**
 * The proof of a choice of a visitor. It holds no personal data: neither the IP address nor the customer.
 */
class ConsentRecord implements ConsentRecordInterface
{
    private ?int $id = null;

    /**
     * @param list<string> $categories
     */
    public function __construct(
        private string $consentId,
        private string $policyVersion,
        private array $categories,
        private \DateTimeImmutable $createdAt,
        private ?string $channelCode = null,
    ) {
    }

    public static function fromConsent(Consent $consent, ?string $channelCode): self
    {
        return new self($consent->id, $consent->policyVersion, $consent->categories, $consent->givenAt, $channelCode);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConsentId(): string
    {
        return $this->consentId;
    }

    public function getPolicyVersion(): string
    {
        return $this->policyVersion;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getChannelCode(): ?string
    {
        return $this->channelCode;
    }
}
