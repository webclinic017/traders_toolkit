<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190510023431 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Crated OHLCVHistory Entity';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('CREATE TABLE ohlcvhistory (id INT AUTO_INCREMENT NOT NULL, symbol_id INT NOT NULL, open DOUBLE PRECISION NOT NULL, high DOUBLE PRECISION NOT NULL, low DOUBLE PRECISION NOT NULL, close DOUBLE PRECISION NOT NULL, volume INT DEFAULT NULL, timeinterval VARCHAR(255) NOT NULL COMMENT \'(DC2Type:dateinterval)\', timestamp DATETIME NOT NULL, INDEX IDX_B5D82CD6C0F75674 (symbol_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci ENGINE = InnoDB');
        $this->addSql('ALTER TABLE ohlcvhistory ADD CONSTRAINT FK_B5D82CD6C0F75674 FOREIGN KEY (symbol_id) REFERENCES instruments (id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('DROP TABLE ohlcvhistory');
    }
}
