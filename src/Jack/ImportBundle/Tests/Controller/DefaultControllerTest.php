<?php

namespace Jack\ImportBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Jack\ImportBundle\Controller\DefaultController;

class DefaultControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/hello/Fabien');

        //$this->assertTrue($crawler->filter('html:contains("Hello Fabien")')->count() > 0);
    }

}
