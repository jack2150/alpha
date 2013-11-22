<?php

namespace Jack\StatisticBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use Jack\StatisticBundle\Controller\DefaultController;

use Jack\ImportBundle\Entity\Underlying;
use Jack\ImportBundle\Entity\Cycle;
use Jack\ImportBundle\Entity\Strike;
use Jack\ImportBundle\Entity\Chain;

class DailyIVController extends DefaultController
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

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
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
        //$this->underlyings = $this->findUnderlyingAll();
        $this->underlyings = $this->findUnderlyingByDateRange('2012-1-3', '2012-1-31');
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

            /* original multiple calculation
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
                $underlying->getLast(), $strike->getPrice()
            );
            */

            // 2 cycles mean calculation
            $cycleRecursive0 = $this->findOneCycleByDTE(
                $underlying->getId(), $underlying->getDate()->format('Y-m-d'), $sampleSize, 'closest', 0
            );

            $cycleRecursive1 = $this->findOneCycleByDTE(
                $underlying->getId(), $underlying->getDate()->format('Y-m-d'), $sampleSize, 'closest', 1
            );

            // 2 strike using 2 difference cycles
            $strikeCycle0 = $this->findOneStrikeByPrice(
                $underlying->getId(), $cycleRecursive0->getId(), $underlying->getLast(), 'put', 'atm'
            );

            $strikeCycle1 = $this->findOneStrikeByPrice(
                $underlying->getId(), $cycleRecursive1->getId(), $underlying->getLast(), 'put', 'atm'
            );

            if (!($strikeCycle0 instanceof Strike && $strikeCycle1 instanceof Strike)) {
                throw $this->createNotFoundException(
                    'Error [ Strike ] object from entity manager!'
                );
            }

            // get 2 chain from using 1st and 2nd cycle, strike
            $chainStrikeCycle0 = $this->findChainOneByIds(
                $underlying->getId(), $cycleRecursive0->getId(), $strikeCycle0->getId()
            );

            $chainStrikeCycle1 = $this->findChainOneByIds(
                $underlying->getId(), $cycleRecursive1->getId(), $strikeCycle1->getId()
            );

            if (!($chainStrikeCycle0 instanceof Chain && $chainStrikeCycle1 instanceof Chain)) {
                throw $this->createNotFoundException(
                    'Error [ Chain ] object from entity manager!'
                );
            }

            // now calculate iv using all data
            $iv = $this->ivBy2SampleMean(
                $chainStrikeCycle0->getImpl(), $chainStrikeCycle1->getImpl(),
                $chainStrikeCycle0->getDte(), $chainStrikeCycle1->getDte(), $sampleSize,
                $strikeCycle0->getPrice(), $strikeCycle1->getPrice(), $underlying->getLast()
            );

            // format into 2 decimal
            $iv = floatval(number_format($iv, 2));


            // calculate, iv using mean of 2 cycles iv
            // date difference is sample size / (mean of 2 expire date)
            // example: (sample size) 30 / ((cycle date 1) 27 * (cycle date 2) 40)
            // price difference is current price / (mean of 2 strike price)
            // example: (price) 12.50 / ((strike price 1) 17 * (strike price 2) 18.5)


            $a = 1;


            // debug use only

            $debugResults[] =
                "Date: " . $underlying->getDate()->format('Y-m-d') .
                " Cycle1 exDate: " . $cycleRecursive0->getExpiredate()->format('Y-m-d') .
                " Cycle2 ExDate: " . $cycleRecursive1->getExpiredate()->format('Y-m-d') .
                "\n Last Price: " . $underlying->getLast() .
                " Strike1 Price: " . $strikeCycle0->getPrice() .
                " Strike2 Price: " . $strikeCycle1->getPrice() .
                "\n Chain1 IV: " . $chainStrikeCycle0->getImpl() .
                " Chain2 IV: " . $chainStrikeCycle1->getImpl() .
                " Chain1 DTE: " . $chainStrikeCycle0->getDte() .
                " Chain2 DTE: " . $chainStrikeCycle1->getDte() .
                "\n Sample Size: " . $sampleSize .
                " Exact IV: " . $iv . "\n\n";

        }


        $a = 1;

        return $this->render(
            'JackStatisticBundle:DailyIV:display.html.twig',
            array(
                'debugResults' => $debugResults,
            )
        );
    }


    // TODO: no calculation formula and example, wait
    // using before and after month to calculate mean iv
    // for example, ((jan iv + feb iv) / 2) * dayDiff * priceDiff
    public function ivBy2SampleMean(
        $iv1, $iv2, $exDate1, $exDate2, $sampleSize, $strike1, $strike2, $currentPrice
    )
    {
        $meanIV = ($iv1 + $iv2) / 2;

        $meanDTE = (($exDate1 + $exDate2) / 2) / $sampleSize;

        $meanStrike = (($strike1 + $strike2) / 2) / $currentPrice;

        return $meanIV * $meanDTE * $meanStrike;
    }


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


}
