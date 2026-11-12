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

namespace Atriametal\SyliusCookieConsentPlugin\Channel;

use FlorentinGarnier\CookieConsentBundle\Channel\ChannelCodeProviderInterface;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;

/**
 * Records the channel of the shop on which the visitor consented.
 */
final class SyliusChannelCodeProvider implements ChannelCodeProviderInterface
{
    public function __construct(private readonly ChannelContextInterface $channelContext)
    {
    }

    public function getChannelCode(): ?string
    {
        try {
            return $this->channelContext->getChannel()->getCode();
        } catch (ChannelNotFoundException) {
            return null;
        }
    }
}
