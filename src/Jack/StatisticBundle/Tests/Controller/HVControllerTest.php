<?php

namespace Jack\StatisticBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HVControllerTest extends WebTestCase
{
    public function testResult()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/result');
    }

}
