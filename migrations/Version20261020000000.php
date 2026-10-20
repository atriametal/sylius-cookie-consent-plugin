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

namespace Atriametal\SyliusCookieConsentPlugin\Migrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\DBAL\Types\Types;
use Doctrine\Migrations\AbstractMigration;

/**
 * Built with the schema API, so that it runs on every database supported by Sylius.
 */
final class Version20261020000000 extends AbstractMigration
{
    private const TABLE = 'atriametal_cookie_consent_record';

    public function getDescription(): string
    {
        return 'Creates the table of the cookie consent records';
    }

    public function up(Schema $schema): void
    {
        $table = $schema->createTable(self::TABLE);
        $table->addColumn('id', Types::INTEGER, ['autoincrement' => true]);
        $table->addColumn('consent_id', Types::STRING, ['length' => 36]);
        $table->addColumn('policy_version', Types::STRING, ['length' => 64]);
        $table->addColumn('categories', Types::JSON);
        $table->addColumn('created_at', Types::DATETIME_IMMUTABLE);
        $table->addColumn('channel_code', Types::STRING, ['length' => 255, 'notnull' => false]);
        $table->setPrimaryKey(['id']);
        $table->addIndex(['consent_id'], 'atriametal_cookie_consent_record_consent_id_idx');
        $table->addIndex(['created_at'], 'atriametal_cookie_consent_record_created_at_idx');
    }

    public function down(Schema $schema): void
    {
        $schema->dropTable(self::TABLE);
    }
}
