<?php

namespace Jack\StatisticBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ImpVolControllerTest extends WebTestCase
{
    public function testGetdata()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/getData');
    }

    public function testCaldata()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/calData');
    }

    public function testFormatdata()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/formatData');
    }

    public function testSavedata()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/saveData');
    }

}
