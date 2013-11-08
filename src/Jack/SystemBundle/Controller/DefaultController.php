<?php

namespace Jack\SystemBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction($name)
    {
        return $this->render('JackSystemBundle:Default:index.html.twig', array('name' => $name));
    }
}
