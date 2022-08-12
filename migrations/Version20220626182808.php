<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220626182808 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE search_feed (id INT AUTO_INCREMENT NOT NULL, year INT NOT NULL, week INT NOT NULL, search VARCHAR(255) NOT NULL, long_period INT NOT NULL, short_period INT NOT NULL, INDEX search_idx (search), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('CREATE TABLE user_clicked_feed (id INT AUTO_INCREMENT NOT NULL, search VARCHAR(255) NOT NULL, pid VARCHAR(255) NOT NULL, clicks INT NOT NULL, INDEX search_pid_idx (search, pid), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE search_feed');
        $this->addSql('DROP TABLE user_clicked_feed');
    }
}
