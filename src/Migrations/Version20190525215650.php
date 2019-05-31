<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190525215650 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Added onDelete cascade directive for OHLCVHistory. I.e. when an instrument gets deleted, its associated OHLCV History will be gone as well.';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ohlcvhistory DROP FOREIGN KEY FK_B5D82CD6CF11D9C');
        $this->addSql('ALTER TABLE ohlcvhistory ADD CONSTRAINT FK_B5D82CD6CF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ohlcvhistory DROP FOREIGN KEY FK_B5D82CD6CF11D9C');
        $this->addSql('ALTER TABLE ohlcvhistory ADD CONSTRAINT FK_B5D82CD6CF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id)');
    }
}
