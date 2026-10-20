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

namespace Atriametal\SyliusCookieConsentPlugin\Menu;

use Sylius\Bundle\CoreBundle\SyliusCoreBundle;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

/**
 * Adds the consent records to the configuration menu of the admin.
 */
final class AdminMenuListener
{
    public function __invoke(MenuBuilderEvent $event): void
    {
        $event->getMenu()->getChild('configuration')
            ?->addChild('cookie_consent_records', ['route' => 'atriametal_cookie_consent_admin_consent_record_index'])
            ->setLabel('atriametal_cookie_consent.ui.consent_records')
            ->setLabelAttribute('icon', '1' === SyliusCoreBundle::MAJOR_VERSION ? 'shield alternate' : 'tabler:cookie')
        ;
    }
}
