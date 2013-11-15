<?php

namespace Jack\StatisticBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use Jack\FindBundle\Controller\FindController;

use Jack\ImportBundle\Entity\Underlying;
use Jack\ImportBundle\Entity\Cycle;
use Jack\ImportBundle\Entity\Strike;
use Jack\ImportBundle\Entity\Chain;

class DailyIVController extends FindController
{
    // for cycles and strikes data
    protected $underlyings;
    protected $cycles;
    protected $strikes;

    public function indexAction(Request $request)
    {
        $selectData = array(
            'symbol' => '',
            'action' => '------',
        );

        $selectForm = $this->createFormBuilder($selectData)
            ->add('symbol', 'choice', array(
                'choices' => $this->getSymbolArray(),
                'required' => true,
                'multiple' => false,
            ))
            ->add('action', 'choice', array(
                'choices' => array(
                    'generate' => 'Generate Daily IV',
                    //'dailyHV' => 'Generate Daily HV',
                    //'dailyPC' => 'Generate Daily PC Ratio',
                    //'dailyGreek' => 'Generate Daily Greek',
                    //'dailySizzle' => 'Generate Daily Sizzle',
                ),
                'required' => true,
                'multiple' => false,
            ))
            ->add('find', 'submit')
            ->getForm();

        $selectForm->handleRequest($request);

        if ($selectForm->isValid()) {
            $selectData = $selectForm->getData();

            $symbol = $selectData['symbol'];
            $action = $selectData['action'];

            switch ($action) {
                case 'dailyIV':
                default:
                    $returnUrl = 'jack_stat_daily_iv_display';
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
            'JackStatisticBundle:DailyIV:index.html.twig',
            array(
                'selectForm' => $selectForm->createView(),
            )
        );
    }

    public function displayAction($symbol, $action)
    {
        // set symbol data
        $this->symbol = $symbol;
        $this->getSymbolObject($symbol);

        // switch db into symbol db
        $this->get('jack_service.fastdb')->switchSymbolDb($symbol);

        // get cycles data
        $this->underlyings = $this->findUnderlyingAll();
        $this->cycles = $this->findCycleAll();
        $this->strikes = $this->findStrikeAll();

        //$chains = $this->findOneCycleByDTE(30, '2009-2-10', 120, 'closest');
        //$chains = $this->findOneCycleByDTE(30, '2009-2-10', 120, 'forward');
        //$chains = $this->findOneCycleByDTE(30, '2009-2-10', 120, 'backward');

        // using strike method to get closest to price


        // using chain method to get closest to price


        return $this->render(
            'JackStatisticBundle:DailyIV:display.html.twig'
        );
    }


    /**
     * @param $underlyingId
     * must be exist in underlying table, if not nothing will be found
     * @param $date
     * the date must be same row as underlying id for searching
     * @param $dte
     * in that date, we forward looking +dte to select cycle
     * @param string $type
     * the search type for cycle dte (underlying date+dte - cycle expire date)
     * closest - will get positive or negative cycle closest to dte
     * forward - will only get positive cycle closest to dte
     * backward - will only get negative cycle closest to dte
     * @return Cycle object
     * return one cycle object that is closest to DTE
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error happen when getting object from db
     */
    public function findOneCycleByDTE($underlyingId, $date, $dte, $type = 'closest')
    {
        // set default time zone to utc
        date_default_timezone_set('UTC');

        // empty array to return
        $chainResult = null;

        // set search date
        $findDate = new \DateTime($date);
        $findDate = $findDate->modify("+$dte days");

        // get the day diff between find date and cycle date
        $dayDiffData = array();
        $dayDiffs = array();
        foreach ($this->cycles as $cycle) {
            if (!($cycle instanceof Cycle)) {
                throw $this->createNotFoundException(
                    'Error [ Cycle ] object from entity manager!'
                );
            }

            $cycleDate = $cycle->getExpiredate()->format('Y-m-d');
            $cycleDate = new \DateTime($cycleDate);

            // reverse
            //$dayDiff[] = $cycleDate->diff($findDate)->format('%R%a');
            $dayDiffs[$cycle->getId()] = intval($findDate->diff($cycleDate)->format('%R%a'));

            // debug use only
            $dayDiffData[$cycle->getId()] =
                $cycle->getExpiredate()->format('Y-m-d') . " - " .
                $findDate->format('Y-m-d')
                . " = " . $findDate->diff($cycleDate)->format('%R%a');
        }


        // now set the type of closest and sort array
        switch ($type) {
            case 'closest':
                foreach ($dayDiffs as $cycleId => $dayDiff) {
                    if ($dayDiff < 0) {
                        $dayDiffs[$cycleId] = -$dayDiff;
                    }
                }
                asort($dayDiffs);
                break;
            case 'forward':
                foreach ($dayDiffs as $cycleId => $dayDiff) {
                    if ($dayDiff < 0) {
                        unset($dayDiffs[$cycleId]);
                    }
                }
                asort($dayDiffs);
                break;
            case 'backward':
                foreach ($dayDiffs as $cycleId => $dayDiff) {
                    if ($dayDiff > 0) {
                        unset($dayDiffs[$cycleId]);
                    }
                }
                arsort($dayDiffs);
                break;
        }

        // find it in chain
        foreach ($dayDiffs as $cycleId => $dayDiff) {
            $chain = $this->findChainByIds($underlyingId, $cycleId);

            if ($chain) {
                // check chain exist, if yes add into data
                $chainResult = $chain;

                $dayDiffData[$cycleId] .= ' Use this!';

                break;
            } else {
                $dayDiffData[$cycleId] .= ' Not Found!';
            }
        }

        $a = 1;

        return $chainResult;
    }

    public function findChainByIds($underlyingId = 0, $cycleId = 0, $strikeId = 0)
    {
        // generate search array
        $searchTerm = array();
        if ($underlyingId) {
            $searchTerm += array('underlyingid' => $underlyingId);
        }

        if ($cycleId) {
            $searchTerm += array('cycleid' => $cycleId);
        }

        if ($strikeId) {
            $searchTerm += array('strikeid' => $strikeId);
        }


        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Chain')
            ->findOneBy(
                $searchTerm
            );
    }

}
