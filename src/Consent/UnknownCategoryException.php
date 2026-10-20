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

final class UnknownCategoryException extends \InvalidArgumentException
{
    public function __construct(public readonly string $category)
    {
        parent::__construct(\sprintf('The consent policy has no "%s" category.', $category));
    }
}
