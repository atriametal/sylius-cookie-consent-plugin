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

namespace Atriametal\SyliusCookieConsentPlugin\Command;

use Atriametal\SyliusCookieConsentPlugin\Repository\ConsentRecordRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'cookie-consent:purge-records', description: 'Removes the consent records older than the retention period')]
final class PurgeConsentRecordsCommand extends Command
{
    public function __construct(
        private readonly ConsentRecordRepositoryInterface $repository,
        private readonly int $retentionInDays,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $removed = $this->repository->removeCreatedBefore(new \DateTimeImmutable(\sprintf('-%d days', $this->retentionInDays)));

        $output->writeln(\sprintf('Removed %d consent record(s) older than %d days.', $removed, $this->retentionInDays));

        return Command::SUCCESS;
    }
}
