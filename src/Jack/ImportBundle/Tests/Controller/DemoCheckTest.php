<?php

namespace Jack\ImportBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

use Jack\ImportBundle\Controller\CheckController;

class CheckControllerTest extends WebTestCase
{
    public function testIndex()
    {
        $client = static::createClient();

        $crawler = $client->request('GET', '/jack/import/check/form');

        $form = $crawler->selectButton('form[check]')->form();
        $client->submit($form, array('form[name]' => 'SNDK'));
        //$crawler = $client->followRedirect();

        // TODO: wrong page after submit done
        /*
        $this->assertGreaterThan(
            0, $crawler->filter('html:contains("You currently generate report for")')->count()
        );
        */
        $this->assertRegExp('/generate/', $client->getResponse()->getContent());
        //$this->assertTrue($crawler->filter('h1')->count() > 0);


    }
}