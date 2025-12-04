<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251204142023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE customer_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE "order_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE order_line_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE stock_movement_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE supplier_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE customer (id INT NOT NULL, name VARCHAR(255) NOT NULL, email VARCHAR(255) DEFAULT NULL, phone VARCHAR(255) DEFAULT NULL, address TEXT DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE TABLE "order" (id INT NOT NULL, user_id_id INT DEFAULT NULL, customer_id INT DEFAULT NULL, supplier_id INT DEFAULT NULL, reference VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, status VARCHAR(100) NOT NULL, total_amount NUMERIC(10, 0) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_F52993989D86650F ON "order" (user_id_id)');
        $this->addSql('CREATE INDEX IDX_F52993989395C3F3 ON "order" (customer_id)');
        $this->addSql('CREATE INDEX IDX_F52993982ADD6D8C ON "order" (supplier_id)');
        $this->addSql('COMMENT ON COLUMN "order".date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE order_line (id INT NOT NULL, order_id_id INT DEFAULT NULL, product_id_id INT DEFAULT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 0) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9CE58EE1FCDAEAAA ON order_line (order_id_id)');
        $this->addSql('CREATE INDEX IDX_9CE58EE1DE18E50B ON order_line (product_id_id)');
        $this->addSql('CREATE TABLE stock_movement (id INT NOT NULL, product_id INT NOT NULL, user_id_id INT DEFAULT NULL, order_line_id INT DEFAULT NULL, type VARCHAR(255) NOT NULL, quantity INT NOT NULL, date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, reason VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_BB1BC1B54584665A ON stock_movement (product_id)');
        $this->addSql('CREATE INDEX IDX_BB1BC1B59D86650F ON stock_movement (user_id_id)');
        $this->addSql('CREATE INDEX IDX_BB1BC1B5BB01DC09 ON stock_movement (order_line_id)');
        $this->addSql('COMMENT ON COLUMN stock_movement.date IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE TABLE supplier (id INT NOT NULL, name VARCHAR(255) NOT NULL, contact_name VARCHAR(255) DEFAULT NULL, contact VARCHAR(255) DEFAULT NULL, address VARCHAR(255) DEFAULT NULL, PRIMARY KEY(id))');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F52993989D86650F FOREIGN KEY (user_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F52993989395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE "order" ADD CONSTRAINT FK_F52993982ADD6D8C FOREIGN KEY (supplier_id) REFERENCES supplier (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_line ADD CONSTRAINT FK_9CE58EE1FCDAEAAA FOREIGN KEY (order_id_id) REFERENCES "order" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE order_line ADD CONSTRAINT FK_9CE58EE1DE18E50B FOREIGN KEY (product_id_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE stock_movement ADD CONSTRAINT FK_BB1BC1B54584665A FOREIGN KEY (product_id) REFERENCES product (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE stock_movement ADD CONSTRAINT FK_BB1BC1B59D86650F FOREIGN KEY (user_id_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE stock_movement ADD CONSTRAINT FK_BB1BC1B5BB01DC09 FOREIGN KEY (order_line_id) REFERENCES order_line (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE customer_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE "order_id_seq" CASCADE');
        $this->addSql('DROP SEQUENCE order_line_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE stock_movement_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE supplier_id_seq CASCADE');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F52993989D86650F');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F52993989395C3F3');
        $this->addSql('ALTER TABLE "order" DROP CONSTRAINT FK_F52993982ADD6D8C');
        $this->addSql('ALTER TABLE order_line DROP CONSTRAINT FK_9CE58EE1FCDAEAAA');
        $this->addSql('ALTER TABLE order_line DROP CONSTRAINT FK_9CE58EE1DE18E50B');
        $this->addSql('ALTER TABLE stock_movement DROP CONSTRAINT FK_BB1BC1B54584665A');
        $this->addSql('ALTER TABLE stock_movement DROP CONSTRAINT FK_BB1BC1B59D86650F');
        $this->addSql('ALTER TABLE stock_movement DROP CONSTRAINT FK_BB1BC1B5BB01DC09');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE "order"');
        $this->addSql('DROP TABLE order_line');
        $this->addSql('DROP TABLE stock_movement');
        $this->addSql('DROP TABLE supplier');
    }
}
