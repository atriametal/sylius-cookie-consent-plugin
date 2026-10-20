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

use Atriametal\SyliusCookieConsentPlugin\Command\PurgeConsentRecordsCommand;
use Atriametal\SyliusCookieConsentPlugin\Entity\ConsentRecord;
use Atriametal\SyliusCookieConsentPlugin\Repository\ConsentRecordRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\CommandTester;

#[CoversClass(PurgeConsentRecordsCommand::class)]
#[CoversClass(ConsentRecordRepository::class)]
final class PurgeConsentRecordsCommandTest extends KernelTestCase
{
    public function testItRemovesTheRecordsOlderThanTheRetentionPeriod(): void
    {
        $kernel = self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        self::assertInstanceOf(EntityManagerInterface::class, $entityManager);
        $entityManager->createQuery(\sprintf('DELETE FROM %s', ConsentRecord::class))->execute();

        // The default retention period is 395 days
        foreach (['-396 days' => 'expired', '-394 days' => 'kept'] as $age => $consentId) {
            $entityManager->persist(new ConsentRecord($consentId, '2', ['necessary'], new \DateTimeImmutable($age)));
        }
        $entityManager->flush();
        $entityManager->clear();

        $command = new CommandTester((new Application($kernel))->find('cookie-consent:purge-records'));
        $command->execute([]);

        $command->assertCommandIsSuccessful();
        self::assertStringContainsString('Removed 1 consent record(s) older than 395 days.', $command->getDisplay());
        self::assertSame(
            ['kept'],
            array_map(static fn (ConsentRecord $record): string => $record->getConsentId(), $entityManager->getRepository(ConsentRecord::class)->findAll()),
        );
    }
}
