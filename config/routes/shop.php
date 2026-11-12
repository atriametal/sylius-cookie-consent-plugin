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

use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/*
 * The route of the bundle receiving the choices of the visitors.
 */
return static function (RoutingConfigurator $routes): void {
    $routes->import('@FlorentinGarnierCookieConsentBundle/config/routes.php');
};
