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

namespace Atriametal\SyliusCookieConsentPlugin\Repository;

interface ConsentRecordRepositoryInterface
{
    /**
     * @return int The number of removed records
     */
    public function removeCreatedBefore(\DateTimeImmutable $date): int;
}
