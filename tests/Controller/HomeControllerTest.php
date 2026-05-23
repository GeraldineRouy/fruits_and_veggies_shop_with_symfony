<?php

namespace App\Tests\Controller;

use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HomeControllerTest extends WebTestCase
{
    #[Test]
    public function homepageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Bienvenue chez Fruits & Veggies');
    }

    #[Test]
    public function homepageUsesBaseTemplate(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');
        $this->assertResponseIsSuccessful();
        $this->assertStringContainsStringIgnoringCase('<!DOCTYPE html>', $client->getResponse()->getContent());
    }
}
