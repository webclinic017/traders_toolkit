<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190510042437 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ohlcvquote (id INT AUTO_INCREMENT NOT NULL, symbol_id INT NOT NULL, open DOUBLE PRECISION DEFAULT NULL, high DOUBLE PRECISION DEFAULT NULL, low DOUBLE PRECISION DEFAULT NULL, close DOUBLE PRECISION DEFAULT NULL, volume DOUBLE PRECISION DEFAULT NULL, timeinterval VARCHAR(255) DEFAULT NULL COMMENT \'(DC2Type:dateinterval)\', timestamp DATETIME NOT NULL, INDEX IDX_69EF258EC0F75674 (symbol_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ohlcvquote ADD CONSTRAINT FK_69EF258EC0F75674 FOREIGN KEY (symbol_id) REFERENCES instruments (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ohlcvquote');
    }
}
