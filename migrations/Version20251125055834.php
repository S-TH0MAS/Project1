<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251125055834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE inventory (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, quantity INTEGER NOT NULL, client_id INTEGER NOT NULL, item_id INTEGER NOT NULL, CONSTRAINT FK_B12D4A3619EB6921 FOREIGN KEY (client_id) REFERENCES client (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_B12D4A36126F525E FOREIGN KEY (item_id) REFERENCES item (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_B12D4A3619EB6921 ON inventory (client_id)');
        $this->addSql('CREATE INDEX IDX_B12D4A36126F525E ON inventory (item_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__client AS SELECT name, id FROM client');
        $this->addSql('DROP TABLE client');
        $this->addSql('CREATE TABLE client (name VARCHAR(255) NOT NULL, id INTEGER NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_C7440455BF396750 FOREIGN KEY (id) REFERENCES user (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO client (name, id) SELECT name, id FROM __temp__client');
        $this->addSql('DROP TABLE __temp__client');
        $this->addSql('CREATE TEMPORARY TABLE __temp__client_item AS SELECT client_id, id FROM client_item');
        $this->addSql('DROP TABLE client_item');
        $this->addSql('CREATE TABLE client_item (client_id INTEGER NOT NULL, id INTEGER NOT NULL, PRIMARY KEY (id), CONSTRAINT FK_CE87E67C19EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON UPDATE NO ACTION ON DELETE NO ACTION NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CE87E67CBF396750 FOREIGN KEY (id) REFERENCES item (id) ON UPDATE NO ACTION ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO client_item (client_id, id) SELECT client_id, id FROM __temp__client_item');
        $this->addSql('DROP TABLE __temp__client_item');
        $this->addSql('CREATE INDEX IDX_CE87E67C19EB6921 ON client_item (client_id)');
        $this->addSql('ALTER TABLE item ADD COLUMN img VARCHAR(255) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE inventory');
        $this->addSql('CREATE TEMPORARY TABLE __temp__client AS SELECT name, id FROM client');
        $this->addSql('DROP TABLE client');
        $this->addSql('CREATE TABLE client (name VARCHAR(255) NOT NULL, id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, CONSTRAINT FK_C7440455BF396750 FOREIGN KEY (id) REFERENCES user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO client (name, id) SELECT name, id FROM __temp__client');
        $this->addSql('DROP TABLE __temp__client');
        $this->addSql('CREATE TEMPORARY TABLE __temp__client_item AS SELECT client_id, id FROM client_item');
        $this->addSql('DROP TABLE client_item');
        $this->addSql('CREATE TABLE client_item (client_id INTEGER NOT NULL, id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, CONSTRAINT FK_CE87E67C19EB6921 FOREIGN KEY (client_id) REFERENCES client (id) NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_CE87E67CBF396750 FOREIGN KEY (id) REFERENCES item (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO client_item (client_id, id) SELECT client_id, id FROM __temp__client_item');
        $this->addSql('DROP TABLE __temp__client_item');
        $this->addSql('CREATE INDEX IDX_CE87E67C19EB6921 ON client_item (client_id)');
        $this->addSql('CREATE TEMPORARY TABLE __temp__item AS SELECT id, name, category_id, discr FROM item');
        $this->addSql('DROP TABLE item');
        $this->addSql('CREATE TABLE item (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, category_id INTEGER NOT NULL, discr VARCHAR(255) NOT NULL, CONSTRAINT FK_1F1B251E12469DE2 FOREIGN KEY (category_id) REFERENCES category (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('INSERT INTO item (id, name, category_id, discr) SELECT id, name, category_id, discr FROM __temp__item');
        $this->addSql('DROP TABLE __temp__item');
        $this->addSql('CREATE INDEX IDX_1F1B251E12469DE2 ON item (category_id)');
    }
}
