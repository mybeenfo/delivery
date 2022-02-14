<?php declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20210606115514 extends AbstractMigration
{
    public function up(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('CREATE TABLE delivery_delivery_locations (
                            id SERIAL NOT NULL, 
                            delivery_id INT NOT NULL, 
                            country_name VARCHAR(20) NOT NULL, 
                            country_iso_code VARCHAR(10) NOT NULL, 
                            federal_district VARCHAR(50) DEFAULT NULL, 
                            region_kladr_id VARCHAR(50) DEFAULT NULL, 
                            region_name VARCHAR(50) DEFAULT NULL, 
                            city_kladr_id VARCHAR(50) NOT NULL, 
                            city_name VARCHAR(100) DEFAULT NULL, 
                            city_area VARCHAR(100) DEFAULT NULL, 
                            city_district VARCHAR(100) DEFAULT NULL, 
                            free_delivery_from_summ INT DEFAULT NULL, 
                            active BOOLEAN DEFAULT \'true\' NOT NULL, 
                            delivery_time_from TIME NOT NULL, 
                            delivery_time_to TIME NOT NULL, 
                            time_zone VARCHAR(10) NOT NULL , 
                            date_insert TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                            date_update TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                            created_by INT DEFAULT NULL, 
                            updated_by INT DEFAULT NULL, 
                            PRIMARY KEY(id))
        ');
        $this->addSql('CREATE INDEX IDX_8495900A12136921 ON delivery_delivery_locations (delivery_id)');
        $this->addSql('CREATE TABLE delivery_deliveries (
                            id SERIAL NOT NULL, 
                            delivery_type_id INT NOT NULL, 
                            name VARCHAR(255) NOT NULL, 
                            description TEXT DEFAULT NULL, 
                            code VARCHAR(255) NOT NULL, 
                            active BOOLEAN DEFAULT \'true\' NOT NULL, 
                            sort INT DEFAULT NULL, 
                            date_insert TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                            date_update TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                            created_by INT DEFAULT NULL, 
                            updated_by INT DEFAULT NULL, 
                            PRIMARY KEY(id))
        ');
        $this->addSql('CREATE INDEX IDX_7142124CCF52334D ON delivery_deliveries (delivery_type_id)');
        $this->addSql('CREATE TABLE delivery_delivery_tariffs (
                            id SERIAL NOT NULL, 
                            location_id INT NOT NULL, 
                            trade_point_id BIGINT NOT NULL, 
                            trade_point_address VARCHAR(255) DEFAULT NULL,
                            trade_point_post_code VARCHAR (50) NOT NULL, 
                            work_of_trade_point_with TIME NOT NULL,
                            work_of_trade_point_on TIME NOT NULL,
                            price_per_kilometer INT DEFAULT NULL, 
                            min_delivery_price INT NOT NULL, 
                            order_pickup_time TIME DEFAULT NULL, 
                            cost_rules_delivery_by_radius JSONB DEFAULT NULL, 
                            calculation_by_radius BOOLEAN NOT NULL, 
                            active BOOLEAN DEFAULT \'true\' NOT NULL, 
                            delivery_time INT DEFAULT NULL,
                            trade_point_latitude DOUBLE PRECISION,
                            trade_point_longitude DOUBLE PRECISION,
                            date_insert TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                            date_update TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                            created_by INT DEFAULT NULL, 
                            updated_by INT DEFAULT NULL, 
                            PRIMARY KEY(id))
        ');
        $this->addSql('CREATE INDEX IDX_7A59822164D218E ON delivery_delivery_tariffs (location_id)');
        $this->addSql('COMMENT ON COLUMN delivery_delivery_tariffs.cost_rules_delivery_by_radius IS \'(DC2Type:array)\'');
        $this->addSql('CREATE TABLE delivery_delivery_types (
                            id SERIAL NOT NULL, 
                            name VARCHAR(50) NOT NULL, 
                            code VARCHAR(50) NOT NULL, 
                            PRIMARY KEY(id))
        ');
        $this->addSql('ALTER TABLE delivery_delivery_locations 
                            ADD CONSTRAINT FK_8495900A12136921 
                            FOREIGN KEY (delivery_id) 
                            REFERENCES delivery_deliveries (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('ALTER TABLE delivery_deliveries 
                            ADD CONSTRAINT FK_7142124CCF52334D 
                            FOREIGN KEY (delivery_type_id) 
                            REFERENCES delivery_delivery_types (id) NOT DEFERRABLE INITIALLY IMMEDIATE
        ');
        $this->addSql('ALTER TABLE delivery_delivery_tariffs 
                            ADD CONSTRAINT FK_7A59822164D218E 
                            FOREIGN KEY (location_id) 
                            REFERENCES delivery_delivery_locations (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'postgresql', 'Migration can only be executed safely on \'postgresql\'.');

        $this->addSql('ALTER TABLE delivery_delivery_tariffs DROP CONSTRAINT FK_7A59822164D218E');
        $this->addSql('ALTER TABLE delivery_delivery_locations DROP CONSTRAINT FK_8495900A12136921');
        $this->addSql('ALTER TABLE delivery_deliveries DROP CONSTRAINT FK_7142124CCF52334D');
        $this->addSql('DROP TABLE delivery_delivery_locations');
        $this->addSql('DROP TABLE delivery_deliveries');
        $this->addSql('DROP TABLE delivery_delivery_tariffs');
        $this->addSql('DROP TABLE delivery_delivery_types');
    }
}
