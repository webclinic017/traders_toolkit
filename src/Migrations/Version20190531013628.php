<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190531013628 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Added OneToOne relationship between instrument and OHLCVQuote';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ohlcvquote ADD instrument_id INT NOT NULL');
        $this->addSql('ALTER TABLE ohlcvquote ADD CONSTRAINT FK_69EF258ECF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id) ON DELETE CASCADE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_69EF258ECF11D9C ON ohlcvquote (instrument_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ohlcvquote DROP FOREIGN KEY FK_69EF258ECF11D9C');
        $this->addSql('DROP INDEX UNIQ_69EF258ECF11D9C ON ohlcvquote');
        $this->addSql('ALTER TABLE ohlcvquote DROP instrument_id');
    }
}
