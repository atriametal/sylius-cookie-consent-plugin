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

namespace Tests\Atriametal\SyliusCookieConsentPlugin\Integration;

use Atriametal\SyliusCookieConsentPlugin\Controller\SaveConsentAction;
use Atriametal\SyliusCookieConsentPlugin\Entity\ConsentRecord;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Response;

/**
 * The test application configures the policy version "2" with the necessary, analytics and media categories.
 */
#[CoversClass(SaveConsentAction::class)]
#[CoversClass(ConsentRecord::class)]
final class SaveConsentActionTest extends WebTestCase
{
    private const PREVIOUS_ID = '0c7a3f9e-1b2d-4c5e-8f9a-0b1c2d3e4f5a';

    private KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = self::createClient();
        self::entityManager()->createQuery(\sprintf('DELETE FROM %s', ConsentRecord::class))->execute();
    }

    public function testItRecordsTheChoiceOfTheVisitorAndStoresItInTheCookie(): void
    {
        $this->postConsent(['analytics']);

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        $consent = $this->consentCookie();
        self::assertSame('2', $consent['version']);
        self::assertSame(['necessary', 'analytics'], $consent['categories']);

        $record = self::onlyRecord();
        self::assertSame($consent['id'], $record->getConsentId());
        self::assertSame('2', $record->getPolicyVersion());
        self::assertSame(['necessary', 'analytics'], $record->getCategories());
        self::assertNull($record->getChannelCode());
    }

    public function testARefusalIsRecordedToo(): void
    {
        $this->postConsent([]);

        self::assertResponseStatusCodeSame(Response::HTTP_NO_CONTENT);
        self::assertSame(['necessary'], self::onlyRecord()->getCategories());
    }

    public function testAVisitorChangingTheChoiceKeepsTheSameIdentifier(): void
    {
        $this->client->getCookieJar()->set(new Cookie('cookie_consent', (string) json_encode([
            'id' => self::PREVIOUS_ID, 'version' => '1', 'categories' => ['necessary', 'media'], 'date' => '2026-01-01T10:00:00+00:00',
        ])));

        $this->postConsent(['media']);

        self::assertSame(self::PREVIOUS_ID, $this->consentCookie()['id']);
        self::assertSame(self::PREVIOUS_ID, self::onlyRecord()->getConsentId());
    }

    public function testAnotherSiteCannotPostAFormToMakeTheVisitorConsent(): void
    {
        $this->client->request('POST', '/cookie-consent', ['categories' => ['analytics']]);

        self::assertResponseStatusCodeSame(Response::HTTP_UNSUPPORTED_MEDIA_TYPE);
        self::assertSame([], self::records());
    }

    public function testAnUnknownCategoryIsRejected(): void
    {
        $this->postConsent(['marketing']);

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertSame('{"error":"The consent policy has no \\u0022marketing\\u0022 category."}', $this->client->getResponse()->getContent());
        self::assertSame([], self::records());
        self::assertNull($this->client->getCookieJar()->get('cookie_consent'));
    }

    public function testAMalformedChoiceIsRejected(): void
    {
        $this->client->request('POST', '/cookie-consent', server: ['CONTENT_TYPE' => 'application/json'], content: '{"categories": "analytics"}');

        self::assertResponseStatusCodeSame(Response::HTTP_BAD_REQUEST);
        self::assertSame([], self::records());
    }

    /**
     * @param list<string> $categories
     */
    private function postConsent(array $categories): void
    {
        $this->client->request(
            'POST',
            '/cookie-consent',
            server: ['CONTENT_TYPE' => 'application/json'],
            content: (string) json_encode(['categories' => $categories]),
        );
    }

    /**
     * @return array{id: string, version: string, categories: list<string>, date: string}
     */
    private function consentCookie(): array
    {
        $cookie = $this->client->getCookieJar()->get('cookie_consent');
        self::assertNotNull($cookie);

        /** @var array{id: string, version: string, categories: list<string>, date: string} $consent */
        $consent = json_decode($cookie->getValue(), true, 3, \JSON_THROW_ON_ERROR);

        return $consent;
    }

    private static function onlyRecord(): ConsentRecord
    {
        $records = self::records();
        self::assertCount(1, $records);

        return $records[0];
    }

    /**
     * @return list<ConsentRecord>
     */
    private static function records(): array
    {
        self::entityManager()->clear();

        return self::entityManager()->getRepository(ConsentRecord::class)->findAll();
    }

    private static function entityManager(): EntityManagerInterface
    {
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);

        return $entityManager;
    }
}
