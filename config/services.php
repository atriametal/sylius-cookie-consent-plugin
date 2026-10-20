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

use Atriametal\SyliusCookieConsentPlugin\Command\PurgeConsentRecordsCommand;
use Atriametal\SyliusCookieConsentPlugin\Consent\ConsentCookie;
use Atriametal\SyliusCookieConsentPlugin\Consent\ConsentPolicy;
use Atriametal\SyliusCookieConsentPlugin\Controller\SaveConsentAction;
use Atriametal\SyliusCookieConsentPlugin\Menu\AdminMenuListener;
use Atriametal\SyliusCookieConsentPlugin\Twig\CookieConsentExtension;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use function Symfony\Component\DependencyInjection\Loader\Configurator\param;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

return static function (ContainerConfigurator $container): void {
    $services = $container->services();

    $services->set(ConsentPolicy::class)
        ->factory([ConsentPolicy::class, 'fromConfiguration'])
        ->args([param('atriametal_cookie_consent.policy_version'), param('atriametal_cookie_consent.categories')])
    ;

    $services->set(ConsentCookie::class)->args([
        service(ConsentPolicy::class),
        param('atriametal_cookie_consent.cookie.name'),
        param('atriametal_cookie_consent.cookie.lifetime'),
        param('atriametal_cookie_consent.cookie.domain'),
    ]);

    $services->set(SaveConsentAction::class)
        ->args([
            service(ConsentPolicy::class),
            service(ConsentCookie::class),
            service('atriametal_cookie_consent.manager.consent_record'),
            service('sylius.context.channel'),
        ])
        ->tag('controller.service_arguments')
        ->public()
    ;

    $services->set(CookieConsentExtension::class)
        ->args([
            service(ConsentPolicy::class),
            service(ConsentCookie::class),
            service('request_stack'),
            service('router'),
            param('atriametal_cookie_consent.privacy_policy.route'),
            param('atriametal_cookie_consent.privacy_policy.parameters'),
            param('atriametal_cookie_consent.google_consent_mode.signals'),
        ])
        ->tag('twig.extension')
    ;

    $services->set(PurgeConsentRecordsCommand::class)
        ->args([service('atriametal_cookie_consent.repository.consent_record'), param('atriametal_cookie_consent.records.retention')])
        ->tag('console.command')
    ;

    $services->set(AdminMenuListener::class)->tag('kernel.event_listener', ['event' => 'sylius.menu.admin.main']);
};
