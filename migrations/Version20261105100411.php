<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20261105100411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add the order creation timestamp';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` ADD created_at DATETIME DEFAULT NULL');
        $this->addSql('UPDATE `order` SET created_at = CURRENT_TIMESTAMP WHERE created_at IS NULL');
        $this->addSql('ALTER TABLE `order` MODIFY created_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `order` DROP created_at');
    }
}
