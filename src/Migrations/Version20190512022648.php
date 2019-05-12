<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190512022648 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Changed $symbol to $instrument in OHLCVHistory Entity';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ohlcvhistory DROP FOREIGN KEY FK_B5D82CD6C0F75674');
        $this->addSql('DROP INDEX IDX_B5D82CD6C0F75674 ON ohlcvhistory');
        $this->addSql('ALTER TABLE ohlcvhistory CHANGE symbol_id instrument_id INT NOT NULL');
        $this->addSql('ALTER TABLE ohlcvhistory ADD CONSTRAINT FK_B5D82CD6CF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id)');
        $this->addSql('CREATE INDEX IDX_B5D82CD6CF11D9C ON ohlcvhistory (instrument_id)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ohlcvhistory DROP FOREIGN KEY FK_B5D82CD6CF11D9C');
        $this->addSql('DROP INDEX IDX_B5D82CD6CF11D9C ON ohlcvhistory');
        $this->addSql('ALTER TABLE ohlcvhistory CHANGE instrument_id symbol_id INT NOT NULL');
        $this->addSql('ALTER TABLE ohlcvhistory ADD CONSTRAINT FK_B5D82CD6C0F75674 FOREIGN KEY (symbol_id) REFERENCES instruments (id)');
        $this->addSql('CREATE INDEX IDX_B5D82CD6C0F75674 ON ohlcvhistory (symbol_id)');
    }
}
