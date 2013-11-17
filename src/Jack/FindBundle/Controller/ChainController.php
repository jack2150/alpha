<?php

namespace Jack\FindBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use Jack\FindBundle\Controller\FindController;

class ChainController extends FindController
{
    public function indexAction(Request $request)
    {
        $findChainData = array(
            'symbol' => '',
            'action' => '------',
        );

        $findChainForm = $this->createFormBuilder($findChainData)
            ->add('symbol', 'choice', array(
                'choices' => $this->getSymbolArray(),
                'required' => true,
                'multiple' => false,
            ))
            ->add('action', 'choice', array(
                'choices' => array(
                    'findByCategory' => 'Find By Category',
                    'findAll' => 'Find All',
                ),
                'required' => true,
                'multiple' => false,
            ))
            ->add('find', 'submit')
            ->getForm();

        $findChainForm->handleRequest($request);

        if ($findChainForm->isValid()) {
            $findChainData = $findChainForm->getData();

            $symbol = $findChainData['symbol'];
            $action = $findChainData['action'];

            $returnUrl = '';
            $params = array();
            switch ($action) {
                case 'findAll':
                default:
                    $returnUrl = '';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower($action),
                    );
                    break;


            }

            return $this->redirect(
                $this->generateUrl(
                    $returnUrl,
                    $params
                )
            );


        }

        return $this->render(
            'JackFindBundle:Strike:index.html.twig',
            array(
                'findStrikeForm' => $findChainForm->createView(),
            )
        );

    }

    public function resultAction()
    {

    }

}
