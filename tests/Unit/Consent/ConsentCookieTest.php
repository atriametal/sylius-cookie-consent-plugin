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

namespace Tests\Atriametal\SyliusCookieConsentPlugin\Unit\Consent;

use Atriametal\SyliusCookieConsentPlugin\Consent\Category;
use Atriametal\SyliusCookieConsentPlugin\Consent\Consent;
use Atriametal\SyliusCookieConsentPlugin\Consent\ConsentCookie;
use Atriametal\SyliusCookieConsentPlugin\Consent\ConsentPolicy;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;

#[CoversClass(ConsentCookie::class)]
#[CoversClass(Consent::class)]
final class ConsentCookieTest extends TestCase
{
    private const ID = '0c7a3f9e-1b2d-4c5e-8f9a-0b1c2d3e4f5a';

    public function testItReadsTheConsentItStored(): void
    {
        $consent = new Consent(self::ID, '2', ['necessary', 'media'], new \DateTimeImmutable('2026-10-20T10:00:00+00:00'));

        $read = self::cookie('2')->read(self::requestWith(self::cookie('2')->create($consent, true)));

        self::assertEquals($consent, $read);
        self::assertNotNull($read);
        self::assertTrue($read->grants('media'));
        self::assertFalse($read->grants('analytics'));
    }

    public function testAConsentToAnEarlierVersionOfThePolicyIsIgnoredButKeepsItsIdentifier(): void
    {
        $request = self::requestWith(self::cookie('1')->create(new Consent(self::ID, '1', ['necessary'], new \DateTimeImmutable()), true));

        self::assertNull(self::cookie('2')->read($request));
        self::assertSame(self::ID, self::cookie('2')->readId($request));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function malformedValues(): iterable
    {
        yield 'not JSON' => ['{not-json'];
        yield 'not an object' => ['"consent"'];
        yield 'invalid identifier' => ['{"id":"1","version":"2","categories":[],"date":"2026-10-20T10:00:00+00:00"}'];
        yield 'categories not a list' => ['{"id":"' . self::ID . '","version":"2","categories":{"a":"media"},"date":"2026-10-20T10:00:00+00:00"}'];
        yield 'category not a name' => ['{"id":"' . self::ID . '","version":"2","categories":[1],"date":"2026-10-20T10:00:00+00:00"}'];
        yield 'invalid date' => ['{"id":"' . self::ID . '","version":"2","categories":[],"date":"yesterday"}'];
        yield 'too deep' => ['{"id":"' . self::ID . '","version":"2","categories":[[["media"]]],"date":"2026-10-20T10:00:00+00:00"}'];
    }

    #[DataProvider('malformedValues')]
    public function testAMalformedCookieIsIgnored(string $value): void
    {
        $request = new Request(cookies: ['cookie_consent' => $value]);

        self::assertNull(self::cookie('2')->read($request));
        self::assertNull(self::cookie('2')->readId($request));
    }

    public function testTheBrowserScriptCanReadTheCookieOfTheWholeSite(): void
    {
        $givenAt = new \DateTimeImmutable('2026-10-20T10:00:00+00:00');

        $cookie = (new ConsentCookie(self::policy('2'), 'cookie_consent', 182, '.example.com'))
            ->create(new Consent(self::ID, '2', ['necessary'], $givenAt), false);

        self::assertFalse($cookie->isHttpOnly());
        self::assertFalse($cookie->isSecure());
        self::assertSame(Cookie::SAMESITE_LAX, $cookie->getSameSite());
        self::assertSame('/', $cookie->getPath());
        self::assertSame('.example.com', $cookie->getDomain());
        self::assertSame($givenAt->modify('+182 days')->getTimestamp(), $cookie->getExpiresTime());
        self::assertSame(
            ['id' => self::ID, 'version' => '2', 'categories' => ['necessary'], 'date' => '2026-10-20T10:00:00+00:00'],
            json_decode((string) $cookie->getValue(), true),
        );
    }

    public function testTheIdentifiersAreRandomVersion4Uuids(): void
    {
        $id = Consent::generateId();

        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $id);
        self::assertNotSame($id, Consent::generateId());
    }

    private static function cookie(string $version): ConsentCookie
    {
        return new ConsentCookie(self::policy($version), 'cookie_consent', 182);
    }

    private static function policy(string $version): ConsentPolicy
    {
        return new ConsentPolicy($version, [new Category('necessary', true), new Category('media')]);
    }

    private static function requestWith(Cookie $cookie): Request
    {
        return new Request(cookies: [$cookie->getName() => $cookie->getValue()]);
    }
}
