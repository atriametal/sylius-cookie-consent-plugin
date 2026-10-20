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

namespace Tests\Atriametal\SyliusCookieConsentPlugin\Unit\Controller;

use Atriametal\SyliusCookieConsentPlugin\Consent\Category;
use Atriametal\SyliusCookieConsentPlugin\Consent\ConsentCookie;
use Atriametal\SyliusCookieConsentPlugin\Consent\ConsentPolicy;
use Atriametal\SyliusCookieConsentPlugin\Controller\SaveConsentAction;
use Atriametal\SyliusCookieConsentPlugin\Entity\ConsentRecord;
use Doctrine\Persistence\ObjectManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Sylius\Component\Channel\Context\ChannelContextInterface;
use Sylius\Component\Channel\Context\ChannelNotFoundException;
use Sylius\Component\Channel\Model\Channel;
use Symfony\Component\HttpFoundation\Request;

/**
 * The HTTP behaviour is covered by the integration tests: these tests cover what depends on the channel.
 */
#[CoversClass(SaveConsentAction::class)]
final class SaveConsentActionTest extends TestCase
{
    public function testTheRecordTellsOnWhichChannelTheVisitorConsented(): void
    {
        $channel = new Channel();
        $channel->setCode('FASHION_WEB');
        $channelContext = self::createStub(ChannelContextInterface::class);
        $channelContext->method('getChannel')->willReturn($channel);

        self::assertSame('FASHION_WEB', $this->recordSavedWith($channelContext)->getChannelCode());
    }

    public function testAConsentIsRecordedOutsideAnyChannel(): void
    {
        $channelContext = self::createStub(ChannelContextInterface::class);
        $channelContext->method('getChannel')->willThrowException(new ChannelNotFoundException());

        self::assertNull($this->recordSavedWith($channelContext)->getChannelCode());
    }

    private function recordSavedWith(ChannelContextInterface $channelContext): ConsentRecord
    {
        $policy = new ConsentPolicy('1', [new Category('necessary', true)]);
        $saved = null;
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::once())->method('persist')->willReturnCallback(static function (object $record) use (&$saved): void {
            $saved = $record;
        });
        $manager->expects(self::once())->method('flush');

        $action = new SaveConsentAction($policy, new ConsentCookie($policy, 'cookie_consent', 182), $manager, $channelContext);
        $action(new Request(server: ['CONTENT_TYPE' => 'application/json'], content: '{"categories": []}'));

        self::assertInstanceOf(ConsentRecord::class, $saved);

        return $saved;
    }
}
