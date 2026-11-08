<?php

declare(strict_types=1);

namespace App\Tests\Form;

use App\DTO\Article\CreateArticleDTO;
use App\DTO\Category\CreateCategoryDTO;
use App\DTO\Demande\CreateDemandeDTO;
use App\Entity\Category;
use App\Form\Article\ArticleType;
use App\Form\Category\CategoryType;
use App\Form\Demande\DemandeType;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;

class FormCreationAndValidationTest extends KernelTestCase
{
    private FormFactoryInterface $formFactory;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->formFactory = static::getContainer()->get('test.form.factory');
    }

    public function testCategoryFormBuildAndSubmit(): void
    {
        $dto = new CreateCategoryDTO();
        $form = $this->formFactory->create(CategoryType::class, $dto, [
            'csrf_protection' => false,
        ]);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('description'));
        $this->assertTrue($form->has('active'));
        $this->assertTrue($form->has('parent'));

        $formData = [
            'name' => 'Chaussures Hommes',
            'description' => 'Rayon chaussures hommes',
            'active' => '1',
            'parent' => '',
        ];

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame('Chaussures Hommes', $dto->name);
        $this->assertSame('Rayon chaussures hommes', $dto->description);
        $this->assertTrue($dto->active);
        $this->assertNull($dto->parent);
    }

    public function testDemandeFormBuildAndSubmit(): void
    {
        $dto = new CreateDemandeDTO();
        $form = $this->formFactory->create(DemandeType::class, $dto, [
            'csrf_protection' => false,
        ]);

        $this->assertTrue($form->has('clientName'));
        $this->assertTrue($form->has('phone'));
        $this->assertTrue($form->has('city'));
        // L'article ne doit pas figurer dans le formulaire
        $this->assertFalse($form->has('article'));

        $formData = [
            'clientName' => 'Sarah Connor',
            'phone' => '+33612345678',
            'city' => 'Lyon',
        ];

        $form->submit($formData);

        $this->assertTrue($form->isSynchronized());
        $this->assertSame('Sarah Connor', $dto->clientName);
        $this->assertSame('+33612345678', $dto->phone);
        $this->assertSame('Lyon', $dto->city);
    }

    public function testArticleFormBuild(): void
    {
        $dto = new CreateArticleDTO();
        $form = $this->formFactory->create(ArticleType::class, $dto, [
            'csrf_protection' => false,
        ]);

        $this->assertTrue($form->has('name'));
        $this->assertTrue($form->has('description'));
        $this->assertTrue($form->has('price'));
        $this->assertTrue($form->has('imageFile'));
        $this->assertTrue($form->has('available'));
        $this->assertTrue($form->has('category'));
    }
}
