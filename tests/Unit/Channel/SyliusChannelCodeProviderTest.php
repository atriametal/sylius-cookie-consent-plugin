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

namespace Tests\Atriametal\SyliusCookieConsentPlugin\Unit\Channel;

use Atriametal\SyliusCookieConsentPlugin\Channel\SyliusChannelCodeProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\Channel;

#[CoversClass(SyliusChannelCodeProvider::class)]
final class SyliusChannelCodeProviderTest extends TestCase
{
    public function testTheRecordTellsOnWhichChannelTheVisitorConsented(): void
    {
        $channel = new Channel();
        $channel->setCode('FASHION_WEB');
        $channelContext = self::createStub(ChannelContextInterface::class);
        $channelContext->method('getChannel')->willReturn($channel);

        self::assertSame('FASHION_WEB', (new SyliusChannelCodeProvider($channelContext))->getChannelCode());
    }

    public function testAConsentIsRecordedOutsideAnyChannel(): void
    {
        $channelContext = self::createStub(ChannelContextInterface::class);
        $channelContext->method('getChannel')->willThrowException(new ChannelNotFoundException());

        self::assertNull((new SyliusChannelCodeProvider($channelContext))->getChannelCode());
    }
}
