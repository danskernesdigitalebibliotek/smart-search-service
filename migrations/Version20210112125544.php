<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210112125544 extends AbstractMigration
{
    public function getDescription() : string
    {
        return '';
    }

    public function up(Schema $schema) : void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE search_feed (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, year INTEGER NOT NULL, week INTEGER NOT NULL, search VARCHAR(255) NOT NULL, long_period INTEGER NOT NULL, short_period INTEGER NOT NULL)');
        $this->addSql('CREATE INDEX search_idx ON search_feed (search)');
        $this->addSql('CREATE TABLE user_clicked_feed (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, search VARCHAR(255) NOT NULL, pid VARCHAR(255) NOT NULL, clicks INTEGER NOT NULL)');
        $this->addSql('CREATE INDEX search_pid_idx ON user_clicked_feed (search, pid)');
    }

    public function down(Schema $schema) : void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE search_feed');
        $this->addSql('DROP TABLE user_clicked_feed');
    }
}
