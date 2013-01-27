<?php

namespace Kryptos\KryptosBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
        return $this->render('KryptosKryptosBundle:Default:index.html.twig', array('name' => 'nidstin'));
    }
}
