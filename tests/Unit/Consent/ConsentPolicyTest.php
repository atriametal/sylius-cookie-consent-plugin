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
use Atriametal\SyliusCookieConsentPlugin\Consent\ConsentPolicy;
use Atriametal\SyliusCookieConsentPlugin\Consent\UnknownCategoryException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

#[CoversClass(ConsentPolicy::class)]
#[CoversClass(Category::class)]
#[CoversClass(UnknownCategoryException::class)]
final class ConsentPolicyTest extends TestCase
{
    public function testTheRequiredCategoriesAreAlwaysGranted(): void
    {
        self::assertSame(['necessary'], self::policy()->grantedCategories([]));
    }

    public function testTheGrantedCategoriesFollowTheOrderOfThePolicy(): void
    {
        self::assertSame(['necessary', 'analytics', 'media'], self::policy()->grantedCategories(['media', 'analytics', 'media']));
    }

    public function testAnUnknownCategoryIsRejected(): void
    {
        $this->expectException(UnknownCategoryException::class);
        $this->expectExceptionMessage('The consent policy has no "advertising" category.');

        self::policy()->grantedCategories(['analytics', 'advertising']);
    }

    public function testACategoryMustBeAName(): void
    {
        $this->expectException(UnknownCategoryException::class);

        self::policy()->grantedCategories([['analytics']]);
    }

    public function testItIsBuiltFromTheConfiguration(): void
    {
        $policy = ConsentPolicy::fromConfiguration('3', [
            'necessary' => ['required' => true, 'services' => [], 'cookies' => []],
            'analytics' => ['required' => false, 'services' => ['Google Analytics'], 'cookies' => ['_ga', '_ga_*']],
        ]);

        self::assertSame('3', $policy->version);
        self::assertEquals(new Category('analytics', false, ['Google Analytics'], ['_ga', '_ga_*']), $policy->categories()['analytics']);
        self::assertTrue($policy->categories()['necessary']->required);
    }

    private static function policy(): ConsentPolicy
    {
        return new ConsentPolicy('1', [new Category('necessary', true), new Category('analytics'), new Category('media')]);
    }
}
