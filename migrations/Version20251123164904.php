<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251123164904 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forecast ADD location_id INT NOT NULL');
        $this->addSql('ALTER TABLE forecast ADD CONSTRAINT FK_2A9C784464D218E FOREIGN KEY (location_id) REFERENCES location (id)');
        $this->addSql('CREATE INDEX IDX_2A9C784464D218E ON forecast (location_id)');
        $this->addSql("INSERT INTO forecast(location_id, date, celsius) VALUES(1, '2024-01-01', 21)");
        $this->addSql("INSERT INTO forecast(location_id, date, celsius) VALUES(2, '2024-01-02', 22)");
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE forecast DROP FOREIGN KEY FK_2A9C784464D218E');
        $this->addSql('DROP INDEX IDX_2A9C784464D218E ON forecast');
        $this->addSql('ALTER TABLE forecast DROP location_id');
    }
}
