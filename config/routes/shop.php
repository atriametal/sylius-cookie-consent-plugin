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

use Atriametal\SyliusCookieConsentPlugin\Controller\SaveConsentAction;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/*
 * Outside the locale prefix of the shop: the same address serves every locale.
 */
return static function (RoutingConfigurator $routes): void {
    $routes->add('atriametal_cookie_consent_shop_save', '/cookie-consent')
        ->controller(SaveConsentAction::class)
        ->methods(['POST'])
    ;
};
