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

namespace Atriametal\SyliusCookieConsentPlugin;

use Sylius\Bundle\CoreBundle\Application\SyliusPluginTrait;
use Symfony\Component\HttpKernel\Bundle\Bundle;

final class AtriametalSyliusCookieConsentPlugin extends Bundle
{
    use SyliusPluginTrait;

    /**
     * The plugin lives at the package root (config/, public/, templates/, translations/), not in src/.
     */
    public function getPath(): string
    {
        return \dirname(__DIR__);
    }
}
