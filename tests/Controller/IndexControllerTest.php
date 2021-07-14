<?php

namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class IndexControllerTest extends WebTestCase
{
    public function testSomething(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        $this->assertResponseIsSuccessful();
        $this->assertCount(2, $crawler->filter('h2'));
        $this->assertSelectorTextContains('.api-container h2', 'Generated files');

        $this->assertCount(2, $crawler->filter('li'));
        $this->assertSelectorTextContains('li:nth-child(1) a', 'test.csv');
        $this->assertSelectorTextContains('li:nth-child(2) a', 'test.txt');
        $this->assertSelectorTextNotContains('body', 'test.tsv');
    }
}
