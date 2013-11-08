<?php

namespace Jack\SystemBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class HolidayControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/index');
    }

    public function testAddholiday()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/addHoliday');
    }

    public function testShowholiday()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/showHoliday');
    }

}
