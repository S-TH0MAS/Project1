<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251127091624 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE client_recipe (client_id INTEGER NOT NULL, recipe_id INTEGER NOT NULL, PRIMARY KEY (client_id, recipe_id), CONSTRAINT FK_8F2EFA8419EB6921 FOREIGN KEY (client_id) REFERENCES client (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE, CONSTRAINT FK_8F2EFA8459D8A214 FOREIGN KEY (recipe_id) REFERENCES recipe (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_8F2EFA8419EB6921 ON client_recipe (client_id)');
        $this->addSql('CREATE INDEX IDX_8F2EFA8459D8A214 ON client_recipe (recipe_id)');
        $this->addSql('CREATE TABLE recipe (id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL, name VARCHAR(255) NOT NULL, description VARCHAR(255) NOT NULL, date INTEGER NOT NULL, matching SMALLINT NOT NULL, preparation_time SMALLINT NOT NULL, ingredients CLOB NOT NULL, steps CLOB NOT NULL, image VARCHAR(255) DEFAULT NULL, author_id INTEGER NOT NULL, CONSTRAINT FK_DA88B137F675F31B FOREIGN KEY (author_id) REFERENCES client (id) NOT DEFERRABLE INITIALLY IMMEDIATE)');
        $this->addSql('CREATE INDEX IDX_DA88B137F675F31B ON recipe (author_id)');
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
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP TABLE client_recipe');
        $this->addSql('DROP TABLE recipe');
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
    }
}
