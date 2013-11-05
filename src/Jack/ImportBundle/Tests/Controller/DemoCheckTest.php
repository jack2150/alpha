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

        $this->assertRegExp('/generate/', $client->getResponse()->getContent());
    }
}