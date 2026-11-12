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

use FlorentinGarnier\CookieConsent\ConsentRecord as BaseConsentRecord;
use Sylius\Resource\Model\ResourceInterface;

/**
 * The record of the bundle, as a Sylius resource listed in the admin. Its table keeps the name of the earlier versions.
 */
class ConsentRecord extends BaseConsentRecord implements ResourceInterface
{
}
