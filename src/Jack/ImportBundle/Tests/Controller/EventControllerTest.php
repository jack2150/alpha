<?php

namespace Jack\ImportBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class EventControllerTest extends WebTestCase
{
    public function testAddevent()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/addEvent');
    }

    public function testSelectsymbol()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/selectSymbol');
    }

    public function testShowevent()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/showEvent');
    }

    public function testRemoveevent()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/removeEvent');
    }

    public function testUpdateevent()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/updateEvent');
    }

}
