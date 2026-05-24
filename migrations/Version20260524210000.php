<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260524210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Enrichit les données produits : unités d\'achat, nouvelle catégorie régionale, top 3';
    }

    public function up(Schema $schema): void
    {
        // 1. Mise à jour des descriptions avec unités d'achat
        $this->addSql("UPDATE product SET description = 'Avocat prêt à déguster — Vendu à la pièce' WHERE id = 5");
        $this->addSql("UPDATE product SET description = 'Mangue bio du Pérou — Vendue à la pièce' WHERE id = 6");
        $this->addSql("UPDATE product SET description = 'Ananas Victoria — Vendu à la pièce' WHERE id = 15");
        $this->addSql("UPDATE product SET description = 'Fraise gariguette parfumée — Vendu en barquette de 250g' WHERE id = 4");
        $this->addSql("UPDATE product SET description = 'Basilic frais — Vendu au bouquet' WHERE id = 12");
        $this->addSql("UPDATE product SET description = 'Menthe fraîche — Vendue au bouquet' WHERE id = 13");
        $this->addSql("UPDATE product SET description = 'Persil plat — Vendu au bouquet' WHERE id = 14");
        $this->addSql("UPDATE product SET description = CONCAT(description, ' — Vendu au kilogramme') WHERE id IN (1, 2, 3, 7, 8, 9, 10, 11) AND description NOT LIKE '% — Vendu au kilogramme'");

        // 2. Supprimer la catégorie "Légumes bio" (id=4)
        $this->addSql('DELETE FROM product_category WHERE category_id = 4');
        $this->addSql('DELETE FROM category WHERE id = 4');

        // 3. Créer la catégorie "Produits locaux d'exception"
        $this->addSql("INSERT INTO category (id, name, description) VALUES (6, 'Produits locaux d''exception', 'Produits régionaux d''exception de nos terroirs')
            ON CONFLICT (id) DO NOTHING");

        // 4. Insérer les 5 nouveaux produits régionaux
        $this->addSql("INSERT INTO product (id, name, description, image, price) VALUES
            (16, 'Noix de Grenoble AOC', 'Noix de Grenoble AOC, cerneaux de qualité supérieure — Vendu au kilogramme', 'images/products/noix-de-grenoble.jpg', 12.00),
            (17, 'Huile de noix de Grenoble AOC', 'Huile de noix de Grenoble AOC, bouteille 250ml — Vendue à la bouteille', 'images/products/huile-noix-grenoble.jpg', 8.50),
            (18, 'Fromage Bleu du Vercors-Sassenage', 'Bleu du Vercors-Sassenage AOP, fromage persillé — Vendu à la pièce', 'images/products/bleu-vercors.jpg', 6.50),
            (19, 'Fromage Saint-Marcellin', 'Saint-Marcellin AOP, fromage doux et crémeux — Vendu à la pièce', 'images/products/saint-marcellin.jpg', 4.50),
            (20, 'Chocolat Bonnat', 'Chocolat Bonnat, tablette de 100g — Vendu à la pièce', 'images/products/chocolat-bonnat.jpg', 5.50)
            ON CONFLICT (id) DO NOTHING");

        // 5. Associer les nouveaux produits à la catégorie 6
        $this->addSql('INSERT INTO product_category (product_id, category_id) VALUES
            (16, 6), (17, 6), (18, 6), (19, 6), (20, 6)
            ON CONFLICT DO NOTHING');

        // 6. Créer des commandes de seed pour le top 3 (user admin id=1)
        // Utiliser des IDs élevés pour éviter les conflits avec les données existantes
        $this->addSql("INSERT INTO \"order\" (id, user_id, ordered_at, status) VALUES
            (1001, 1, CURRENT_TIMESTAMP, 'delivered'),
            (1002, 1, CURRENT_TIMESTAMP, 'delivered'),
            (1003, 1, CURRENT_TIMESTAMP, 'delivered')
            ON CONFLICT (id) DO NOTHING");

        $this->addSql("INSERT INTO order_line (id, order_id, product_id, quantity, price) VALUES
            (10001, 1001, 4, 80, 4.50),
            (10002, 1001, 1, 10, 2.50),
            (10003, 1001, 2, 5, 1.80),
            (10004, 1002, 19, 55, 4.50),
            (10005, 1002, 3, 8, 3.00),
            (10006, 1002, 7, 6, 1.50),
            (10007, 1003, 15, 25, 3.90),
            (10008, 1003, 9, 10, 3.80),
            (10009, 1003, 8, 5, 1.20)
            ON CONFLICT (id) DO NOTHING");
    }

    public function down(Schema $schema): void
    {
        // Supprimer les order_lines qui référencent nos nouveaux produits (couvre aussi les données de test existantes)
        $this->addSql('DELETE FROM order_line WHERE product_id IN (16, 17, 18, 19, 20)');
        // Supprimer les OrderLines de seed
        $this->addSql('DELETE FROM order_line WHERE id IN (10001, 10002, 10003, 10004, 10005, 10006, 10007, 10008, 10009)');
        // Supprimer les commandes de seed
        $this->addSql('DELETE FROM "order" WHERE id IN (1001, 1002, 1003)');

        // Supprimer les associations des nouveaux produits
        $this->addSql('DELETE FROM product_category WHERE product_id IN (16, 17, 18, 19, 20)');
        // Supprimer les nouveaux produits
        $this->addSql('DELETE FROM product WHERE id IN (16, 17, 18, 19, 20)');
        // Supprimer la catégorie "Produits locaux d'exception"
        $this->addSql('DELETE FROM category WHERE id = 6');

        // Recréer la catégorie "Légumes bio"
        $this->addSql("INSERT INTO category (id, name, description) VALUES (4, 'Légumes bio', 'Légumes issus de l''agriculture biologique')
            ON CONFLICT (id) DO NOTHING");
        // Restaurer les associations pour les produits qui étaient dans les deux catégories
        $this->addSql('INSERT INTO product_category (product_id, category_id) VALUES (7, 4), (9, 4), (11, 4)
            ON CONFLICT DO NOTHING');
    }
}
