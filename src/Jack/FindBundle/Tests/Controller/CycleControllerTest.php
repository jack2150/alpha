<?php

namespace Jack\FindBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class CycleControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/index');
    }

    public function testResult()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/result');
    }

}
