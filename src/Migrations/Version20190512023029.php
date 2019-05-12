<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190512023029 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Changed $symbol to $instrument in OHLCVQuote Entity';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ohlcvquote DROP FOREIGN KEY FK_69EF258EC0F75674');
        $this->addSql('DROP INDEX IDX_69EF258EC0F75674 ON ohlcvquote');
        $this->addSql('ALTER TABLE ohlcvquote CHANGE symbol_id instrument_id INT NOT NULL');
        $this->addSql('ALTER TABLE ohlcvquote ADD CONSTRAINT FK_69EF258ECF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id)');
        $this->addSql('CREATE INDEX IDX_69EF258ECF11D9C ON ohlcvquote (instrument_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ohlcvquote DROP FOREIGN KEY FK_69EF258ECF11D9C');
        $this->addSql('DROP INDEX IDX_69EF258ECF11D9C ON ohlcvquote');
        $this->addSql('ALTER TABLE ohlcvquote CHANGE instrument_id symbol_id INT NOT NULL');
        $this->addSql('ALTER TABLE ohlcvquote ADD CONSTRAINT FK_69EF258EC0F75674 FOREIGN KEY (symbol_id) REFERENCES instruments (id)');
        $this->addSql('CREATE INDEX IDX_69EF258EC0F75674 ON ohlcvquote (symbol_id)');
    }
}
