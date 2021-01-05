<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210105091818 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__search_feed AS SELECT id, year, week, search, count FROM search_feed');
        $this->addSql('DROP TABLE search_feed');
        $this->addSql('CREATE TABLE search_feed (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, year INTEGER NOT NULL, week INTEGER NOT NULL, search VARCHAR(255) NOT NULL COLLATE BINARY, long_period INTEGER NOT NULL, short_period INTEGER NOT NULL)');
        $this->addSql('INSERT INTO search_feed (id, year, week, search, long_period) SELECT id, year, week, search, count FROM __temp__search_feed');
        $this->addSql('DROP TABLE __temp__search_feed');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TEMPORARY TABLE __temp__search_feed AS SELECT id, year, week, search FROM search_feed');
        $this->addSql('DROP TABLE search_feed');
        $this->addSql('CREATE TABLE search_feed (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, year INTEGER NOT NULL, week INTEGER NOT NULL, search VARCHAR(255) NOT NULL, count INTEGER NOT NULL)');
        $this->addSql('INSERT INTO search_feed (id, year, week, search) SELECT id, year, week, search FROM __temp__search_feed');
        $this->addSql('DROP TABLE __temp__search_feed');
    }
}
