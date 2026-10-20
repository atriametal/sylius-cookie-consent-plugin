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

namespace Atriametal\SyliusCookieConsentPlugin\Repository;

use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;

class ConsentRecordRepository extends EntityRepository implements ConsentRecordRepositoryInterface
{
    public function removeCreatedBefore(\DateTimeImmutable $date): int
    {
        $removed = $this->createQueryBuilder('o')
            ->delete()
            ->where('o.createdAt < :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->execute()
        ;

        return \is_int($removed) ? $removed : 0;
    }
}
