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

use Sylius\Resource\Model\ResourceInterface;

interface ConsentRecordInterface extends ResourceInterface
{
    public function getConsentId(): string;

    public function getPolicyVersion(): string;

    /**
     * @return list<string>
     */
    public function getCategories(): array;

    public function getCreatedAt(): \DateTimeImmutable;

    public function getChannelCode(): ?string;
}
