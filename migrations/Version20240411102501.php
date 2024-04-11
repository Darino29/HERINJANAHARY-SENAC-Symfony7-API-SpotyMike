<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20240411102501 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE user DROP sexe, DROP date_birth, CHANGE tel tel VARCHAR(15) DEFAULT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_8D93D6496B3CA4B ON user (id_user)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_8D93D6496B3CA4B ON user');
        $this->addSql('ALTER TABLE user ADD sexe VARCHAR(15) DEFAULT \'NULL\', ADD date_birth DATE DEFAULT \'NULL\', CHANGE tel tel VARCHAR(15) DEFAULT \'NULL\'');
    }
}
