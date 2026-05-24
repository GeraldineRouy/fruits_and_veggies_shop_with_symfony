<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\Product;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ProductDescriptionUnitTest extends TestCase
{
    private const UNITS = ['à la pièce', 'au bouquet', 'barquette de 250g', 'au kilogramme', 'à la bouteille'];

    #[Test]
    public function allProductDescriptionsContainAtLeastOneValidUnit(): void
    {
        $products = $this->createAllProducts();

        foreach ($products as $product) {
            $description = $product->getDescription();

            $hasUnit = false;
            foreach (self::UNITS as $unit) {
                if (str_contains($description, $unit)) {
                    $hasUnit = true;
                    break;
                }
            }

            $this->assertTrue(
                $hasUnit,
                sprintf(
                    'La description du produit "%s" ne contient aucune unité d\'achat valide. Description: "%s"',
                    $product->getName(),
                    $description,
                ),
            );
        }
    }

    #[Test]
    #[DataProvider('productUnitProvider')]
    public function productDescriptionContainsCorrectUnit(
        string $name,
        string $description,
        string $expectedUnit,
        ?string $forbiddenString,
    ): void {
        $product = new Product();
        $product->setName($name);
        $product->setDescription($description);

        $this->assertStringContainsString(
            $expectedUnit,
            $product->getDescription(),
            sprintf('Le produit "%s" devrait mentionner "%s"', $name, $expectedUnit),
        );

        if ($forbiddenString !== null) {
            $this->assertStringNotContainsString(
                $forbiddenString,
                $product->getDescription(),
                sprintf('Le produit "%s" ne devrait pas contenir "%s"', $name, $forbiddenString),
            );
        }
    }

    /**
     * @return iterable<array{string, string, string, string|null}>
     */
    public static function productUnitProvider(): iterable
    {
        yield 'Avocat' => ['Avocat', 'Avocat prêt à déguster — Vendu à la pièce', 'à la pièce', null];
        yield 'Mangue' => ['Mangue', 'Mangue bio du Pérou — Vendue à la pièce', 'à la pièce', null];
        yield 'Ananas' => ['Ananas', 'Ananas Victoria — Vendu à la pièce', 'à la pièce', null];
        yield 'Fraise' => ['Fraise', 'Fraise gariguette parfumée — Vendu en barquette de 250g', 'barquette de 250g', null];
        yield 'Basilic' => ['Basilic', 'Basilic frais — Vendu au bouquet', 'au bouquet', 'en pot'];
        yield 'Menthe' => ['Menthe', 'Menthe fraîche — Vendue au bouquet', 'au bouquet', 'en pot'];
        yield 'Persil' => ['Persil', 'Persil plat — Vendu au bouquet', 'au bouquet', 'en botte'];
        yield 'Pomme Golden' => ['Pomme Golden', 'Pomme Golden sucrée et juteuse — Vendu au kilogramme', 'au kilogramme', null];
        yield 'Banane' => ['Banane', 'Banane mûre à point — Vendu au kilogramme', 'au kilogramme', null];
        yield 'Orange' => ['Orange', 'Orange sanguine de Sicile — Vendu au kilogramme', 'au kilogramme', null];
        yield 'Carotte' => ['Carotte', 'Carotte bio, lot de 500g — Vendu au kilogramme', 'au kilogramme', null];
        yield 'Salade' => ['Salade', 'Salade verte croquante — Vendu au kilogramme', 'au kilogramme', null];
        yield 'Tomate' => ['Tomate', 'Tomate cœur de bœuf bio — Vendu au kilogramme', 'au kilogramme', null];
        yield 'Concombre' => ['Concombre', 'Concombre long — Vendu au kilogramme', 'au kilogramme', null];
        yield 'Courgette' => ['Courgette', 'Courgette verte bio — Vendu au kilogramme', 'au kilogramme', null];
        yield 'Noix de Grenoble AOC' => [
            'Noix de Grenoble AOC',
            'Noix de Grenoble AOC, cerneaux de qualité supérieure — Vendu au kilogramme',
            'au kilogramme',
            null,
        ];
        yield 'Huile de noix de Grenoble AOC' => [
            'Huile de noix de Grenoble AOC',
            'Huile de noix de Grenoble AOC, bouteille 250ml — Vendue à la bouteille',
            'à la bouteille',
            null,
        ];
        yield 'Bleu du Vercors-Sassenage' => [
            'Fromage Bleu du Vercors-Sassenage',
            'Bleu du Vercors-Sassenage AOP, fromage persillé — Vendu à la pièce',
            'à la pièce',
            null,
        ];
        yield 'Saint-Marcellin' => [
            'Fromage Saint-Marcellin',
            'Saint-Marcellin AOP, fromage doux et crémeux — Vendu à la pièce',
            'à la pièce',
            null,
        ];
        yield 'Chocolat Bonnat' => [
            'Chocolat Bonnat',
            'Chocolat Bonnat, tablette de 100g — Vendu à la pièce',
            'à la pièce',
            null,
        ];
    }

    /**
     * @return Product[]
     */
    private function createAllProducts(): array
    {
        $products = [];

        foreach (self::productUnitProvider() as $data) {
            $product = new Product();
            $product->setName($data[0]);
            $product->setDescription($data[1]);
            $products[] = $product;
        }

        return $products;
    }
}
