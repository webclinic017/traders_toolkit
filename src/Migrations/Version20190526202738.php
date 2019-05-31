<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20190526202738 extends AbstractMigration
{
    public function getDescription() : string
    {
        return 'Added onDelete="CASCADE" directive to the Quote Entity';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ohlcvquote DROP FOREIGN KEY FK_69EF258ECF11D9C');
        $this->addSql('ALTER TABLE ohlcvquote ADD CONSTRAINT FK_69EF258ECF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->abortIf($this->connection->getDatabasePlatform()->getName() !== 'mysql', 'Migration can only be executed safely on \'mysql\'.');

        $this->addSql('ALTER TABLE ohlcvquote DROP FOREIGN KEY FK_69EF258ECF11D9C');
        $this->addSql('ALTER TABLE ohlcvquote ADD CONSTRAINT FK_69EF258ECF11D9C FOREIGN KEY (instrument_id) REFERENCES instruments (id)');
    }
}
