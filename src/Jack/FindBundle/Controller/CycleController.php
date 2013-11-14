<?php

namespace Jack\FindBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Jack\FindBundle\Controller\FindController;

use Jack\ImportBundle\Entity\Symbol;
use Jack\ImportBundle\Entity\Cycle;

/**
 * Class CycleController
 * @package Jack\FindBundle\Controller
 */
class CycleController extends FindController
{
    public function indexAction(Request $request)
    {
        $findCycleData = array(
            'symbol' => '',
            'action' => '------',
        );

        $findCycleForm = $this->createFormBuilder($findCycleData)
            ->add('symbol', 'choice', array(
                'choices' => $this->getSymbolArray(),
                'required' => true,
                'multiple' => false,
            ))
            ->add('action', 'choice', array(
                'choices' => array(
                    'findAll' => 'Find All',
                    'findByWeekNo' => 'Find By Week No.',
                ),
                'required' => true,
                'multiple' => false,
            ))
            ->add('find', 'submit')
            ->getForm();

        $findCycleForm->handleRequest($request);

        if ($findCycleForm->isValid()) {
            $findCycleData = $findCycleForm->getData();

            $symbol = $findCycleData['symbol'];
            $action = $findCycleData['action'];

            $searchName = '';
            $returnUrl = '';
            $params = array();
            switch ($action) {
                case 'findAll':
                    $returnUrl = 'jack_find_cycle_result_findall';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower($action),
                    );
                    break;
                default:

            }

            return $this->redirect(
                $this->generateUrl(
                    $returnUrl,
                    $params
                )
            );


        }

        return $this->render(
            'JackFindBundle:Cycle:index.html.twig',
            array(
                'findCycleForm' => $findCycleForm->createView(),
            )
        );
    }


    public function resultAction($symbol, $action)
    {
        $this->symbol = $symbol;
        $this->getSymbolObject($symbol);

        $this->get('jack_service.fastdb')->switchSymbolDb($symbol);

        switch ($action) {
            case 'findall':
            default:
                $searchName = "Find All";
                $cycles = $this->findCycleAll('desc');

        }

        // count underlying
        $resultCount = 0;
        if (!empty($cycles)) {
            $resultCount = count($cycles);
        }

        return $this->render(
            'JackFindBundle:Cycle:result.html.twig',
            array(
                'symbol' => $symbol,
                'searchName' => $searchName,
                'cycles' => $cycles,
                'resultCount' => $resultCount,
            )
        );
    }

    public function findCycleAll($sort = 'asc')
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Cycle')
            ->findBy(array(), array('expiredate' => $sort));
    }


}
