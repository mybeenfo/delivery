<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210629134514 extends AbstractMigration
{
    public function getDescription()
    {
        return 'Добавление колонок возможного смещения лимитов готовности к доставке';
    }

    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE delivery_locality_tradepoint ADD COLUMN before_boundary_offset_hours INT NOT NULL DEFAULT 0');
        $this->addSql('ALTER TABLE delivery_locality_tradepoint ADD COLUMN after_boundary_offset_hours INT NOT NULL DEFAULT 24');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE delivery_locality_tradepoint DROP COLUMN before_boundary_offset_hours');
        $this->addSql('ALTER TABLE delivery_locality_tradepoint DROP COLUMN after_boundary_offset_hours');
    }
}
