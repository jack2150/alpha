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

    protected static $sampleSize = array(
        'oneDay' => 1,
        'twoDay' => 2,
        'oneWeek' => 7,
        'twoWeek' => 14,
        'oneMonth' => 30,
        '45Day' => 45,
        'twoMonth' => 60,
        'threeMonth' => 90,
        'halfYear' => 126,
        'oneYear' => 252,
    );

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

        // get sample size use for dte
        $sampleSize = self::$sampleSize['oneMonth'];

        //$cycle = $this->findOneCycleByDTE(146, '2012-8-31', $sampleSize, 'closest');
        //$cycle = $this->findOneCycleByDTE(30, '2009-2-10', 120, 'forward');
        //$cycle = $this->findOneCycleByDTE(30, '2009-2-10', 120, 'backward');

        // using strike method to get closest to price
        // $strike = $this->findOneStrikeByPrice(146, $cycle->getId(), 47.47, 'put', 'atm');
        //$chain = $this->findOneStrikeByPrice(146, $cycle->getId(), 47.47, 'put', 'otm');
        //$chain = $this->findOneStrikeByPrice(146, $cycle->getId(), 47.47, 'put', 'itm');

        // using chain method to get closest to price
        // remain


        // get the exact chain from db
        //$chain = $this->findOneChainByIds(146, $cycle->getId(), $strike->getId());


        // get the iv and dte from chain
        //$chainIV = $chain->getImpl();
        //$chainDTE = $chain->getDte();

        //$iv = $this->impliedVolatilityBySampleSize($chainIV, $chainDTE, $sampleSize);

        // now run it in underlying loop
        $debugResults = "";
        foreach ($this->underlyings as $underlying) {
            if (!($underlying instanceof Underlying)) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager!'
                );
            }

            // get closest cycle to sample size
            $cycle = $this->findOneCycleByDTE(
                $underlying->getId(), $underlying->getDate()->format('Y-m-d'), $sampleSize, 'closest'
            );

            // get closest price to underlying price
            $strike = $this->findOneStrikeByPrice(
                $underlying->getId(), $cycle->getId(), $underlying->getLast(), 'put', 'atm'
            );

            // get the exact chain from db
            $chain = $this->findOneChainByIds(
                $underlying->getId(), $cycle->getId(), $strike->getId()
            );

            // chain error checking
            if (!($chain instanceof Chain)) {
                throw $this->createNotFoundException(
                    'Error [ Chain ] object from entity manager!'
                );
            }

            // calculate the exact iv
            $iv = $this->impliedVolatilityBySampleSize(
                $chain->getImpl(), $chain->getDte(), $sampleSize,
                $underlying->getLast(), $strike->getStrike()
            );

            // debug use only
            $debugResults[] =
                "Date: " . $underlying->getDate()->format('Y-m-d') .
                " Cycle ExDate: " . $cycle->getExpiredate()->format('Y-m-d') .
                ", Last Price: " . $underlying->getLast() .
                " Strike Price: " . $strike->getStrike() .
                ", Chain IV: " . $chain->getImpl() .
                " Chain DTE: " . $chain->getDte() .
                " Sample Size: " . $sampleSize .
                " Exact IV: " . $iv . "\n";
        }


        $a = 1;

        return $this->render(
            'JackStatisticBundle:DailyIV:display.html.twig',
            array(
                'debugResults' => $debugResults,
            )
        );
    }


    // TODO: wrong calculate iv formula
    // using before and after month to calculate mean iv
    // for example, ((jan iv + feb iv) / 2) * dayDiff * priceDiff


    /**
     * @param $iv
     * @param $dte
     * @param $sampleSize
     * @param $strike
     * @param $price
     * @return float
     */
    public function impliedVolatilityBySampleSize($iv, $dte, $sampleSize, $price, $strike)
    {
        // calculate exact sampleSize iv using chain data
        $exactIV = 0;


        // calculate day difference
        $calDiff = 1;
        $calDiff = floatval($sampleSize / $dte);

        // 28, 30
        /*
        if ($dte < $sampleSize) {
            $calDiff = floatval($sampleSize / $dte);
        }
        // 30, 32
        elseif ($dte > $sampleSize) {
            $calDiff = floatval($dte / $sampleSize);
        }
        */


        // calculate price difference
        $priceDiff = 1;
        $priceDiff = floatval($price / $strike);
        /*
        if ($strike < $price) {
            $priceDiff = floatval($strike/$price);
        }
        elseif ($strike > $price) {
            $priceDiff = floatval($price/$strike);
        }
        */


        $exactIV = number_format($iv * $calDiff * $priceDiff, 2);

        $debug = "$iv * $calDiff * $priceDiff = " . $exactIV;

        return floatval($exactIV);
    }


    // using the chain data value method
    /**
     * @param $underlyingId
     * @param $cycleId
     * @param $findPrice
     * @param string $category
     * @param string $type
     * @return null
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function findOneStrikeByPrice
    ($underlyingId, $cycleId, $findPrice, $category = 'call', $type = 'atm')
    {
        $chain = null;

        // format input price
        $findPrice = floatval($findPrice);
        $category = strtoupper($category);

        // compare underlying price and strike price
        $priceDiffs = array();
        $priceDiffData = array();
        foreach ($this->strikes as $strike) {
            if (!($strike instanceof Strike)) {
                throw $this->createNotFoundException(
                    'Error [ Strike ] object from entity manager!'
                );
            }

            // format strike price in object and get diff in price

            $strikePrice = floatval($strike->getStrike());

            if ($strike->getCategory() == $category) {
                if ($category == 'call') {
                    // because is 'call' so it use reverse
                    $priceDiffs[$strike->getId()] = $strikePrice - $findPrice;

                    // debug only
                    $diff = $strikePrice - $findPrice;
                    $priceDiffData[$strike->getId()] =
                        "(strike) $strikePrice - (find) $findPrice = (diff) $diff";
                } else {
                    // is put
                    $priceDiffs[$strike->getId()] = $findPrice - $strikePrice;

                    // debug only
                    $diff = $findPrice - $strikePrice;
                    $priceDiffData[$strike->getId()] =
                        "(find) $findPrice - (strike) $strikePrice = (diff) $diff";
                }
            }

        }

        // format price diff array using type method
        switch ($type) {
            // only use out of money
            case 'otm':
                foreach ($priceDiffs as $strikeId => $priceDiff) {
                    if ($priceDiff < 0) {
                        unset($priceDiffs[$strikeId]);
                    }
                }
                asort($priceDiffs);
                break;
            case 'itm':
                foreach ($priceDiffs as $strikeId => $priceDiff) {
                    if ($priceDiff > 0) {
                        unset($priceDiffs[$strikeId]);
                    }
                }
                arsort($priceDiffs);
                break;
            case 'atm':
            default:
                foreach ($priceDiffs as $strikeId => $priceDiff) {
                    if ($priceDiff < 0) {
                        $priceDiffs[$strikeId] = -$priceDiff;
                    }
                }
                asort($priceDiffs);
                break;
        }

        // loop price diff to check exist chain
        $foundStrikeId = 0;
        foreach ($priceDiffs as $strikeId => $priceDiff) {
            $chain = $this->findOneChainByIds($underlyingId, $cycleId, $strikeId);

            if ($chain) {
                // check chain exist, if yes add into data
                $foundStrikeId = $strikeId;

                $priceDiffData[$strikeId] .= ' Use this!';

                break;
            } else {
                $priceDiffData[$strikeId] .= ' Not Found!';
            }

        }

        // return strike object
        $foundStrike = null;
        foreach ($this->strikes as $strike) {
            if ($strike->getId() == $foundStrikeId) {
                $foundStrike = $strike;
            }
        }

        return $foundStrike;
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
            case 'closest':
            default:
                foreach ($dayDiffs as $cycleId => $dayDiff) {
                    if ($dayDiff < 0) {
                        $dayDiffs[$cycleId] = -$dayDiff;
                    }
                }
                asort($dayDiffs);
                break;
        }

        // find it in chain
        $foundCycleId = null;
        foreach ($dayDiffs as $cycleId => $dayDiff) {
            $chain = $this->findOneChainByIds($underlyingId, $cycleId);

            if ($chain) {
                // check chain exist, if yes add into data
                $foundCycleId = $cycleId;

                $dayDiffData[$cycleId] .= ' Use this!';

                break;
            } else {
                $dayDiffData[$cycleId] .= ' Not Found!';
            }
        }

        // return cycle object
        $foundCycle = null;
        foreach ($this->cycles as $cycle) {
            if ($cycle->getId() == $foundCycleId) {
                $foundCycle = $cycle;
            }
        }

        return $foundCycle;
    }

    /**
     * @param int $underlyingId
     * underlying id from underlying table
     * @param int $cycleId
     * cycle id from cycle id table
     * @param int $strikeId
     * strike id from strike id table
     * @return object
     * use to search is the data 'exist' for
     * both or all ids in chain table
     */
    public function findOneChainByIds($underlyingId = 0, $cycleId = 0, $strikeId = 0)
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