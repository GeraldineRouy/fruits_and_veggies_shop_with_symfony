<?php

declare(strict_types=1);

namespace App\Tests\Unit\Form;

use App\Entity\Category;
use App\Form\CategoryType;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class CategoryTypeTest extends KernelTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->formFactory = static::getContainer()->get('form.factory');
    }

    private function createForm(mixed $data = null): \Symfony\Component\Form\FormInterface
    {
        return $this->formFactory->create(CategoryType::class, $data, [
            'csrf_protection' => false,
        ]);
    }

    #[Test]
    public function validSubmissionMapsDataToCategory(): void
    {
        $category = new Category();
        $form = $this->createForm($category);

        $form->submit([
            'name' => 'Fruits',
            'description' => 'Tous les fruits frais',
        ]);

        $this->assertTrue($form->isSynchronized());
        $this->assertTrue($form->isValid());

        $this->assertSame('Fruits', $category->getName());
        $this->assertSame('Tous les fruits frais', $category->getDescription());
    }

    #[Test]
    public function blankNameShowsError(): void
    {
        $category = new Category();
        $form = $this->createForm($category);

        $form->submit([
            'name' => '',
            'description' => 'Test',
        ]);

        $this->assertFalse($form->isValid());
        $this->assertCount(1, $form->get('name')->getErrors());
    }
}
