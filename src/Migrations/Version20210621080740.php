<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210621080740 extends AbstractMigration
{
    public function up(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE delivery_options (
                            id SERIAL NOT NULL, 
                            delivery_id INT NOT NULL, 
                            code VARCHAR(255) NOT NULL, 
                            name VARCHAR(255) NOT NULL, 
                            value VARCHAR(255) NOT NULL, 
                            description TEXT DEFAULT NULL,
                            date_insert TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                            date_update TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                            created_by INT DEFAULT NULL, 
                            updated_by INT DEFAULT NULL, 
                            PRIMARY KEY(id))
        ');
        $this->addSql('CREATE INDEX IDX_4497F93E12136921 ON delivery_options (delivery_id)');
        $this->addSql('ALTER TABLE delivery_options ADD CONSTRAINT FK_4497F93E12136921 FOREIGN KEY (delivery_id) REFERENCES delivery_deliveries (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema) : void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('DROP TABLE delivery_options');
    }
}
