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

use Sylius\Bundle\CoreBundle\SyliusCoreBundle;
use Symfony\Component\Routing\Loader\Configurator\RoutingConfigurator;

/*
 * The consent records are read only: they are the proof of the choices of the visitors.
 */
return static function (RoutingConfigurator $routes): void {
    $templates = '1' === SyliusCoreBundle::MAJOR_VERSION ? '@SyliusAdmin\\\\Crud' : '@SyliusAdmin\\\\shared\\\\crud';

    $routes->import(<<<YAML
        alias: atriametal_cookie_consent.consent_record
        section: admin
        templates: "{$templates}"
        only: ['index']
        grid: atriametal_cookie_consent_admin_consent_record
        vars:
            all:
                subheader: atriametal_cookie_consent.ui.consent_records_subheader
            index:
                icon: shield alternate
        YAML, 'sylius.resource');
};
