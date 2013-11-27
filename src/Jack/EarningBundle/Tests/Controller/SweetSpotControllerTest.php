<?php

namespace Jack\EarningBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SweetSpotControllerTest extends WebTestCase
{
    public function testResult()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/result');
    }

    public function testRedirect()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/redirect');
    }

}
