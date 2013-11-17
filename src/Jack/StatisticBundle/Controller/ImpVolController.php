<?php

namespace Jack\StatisticBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Jack\ImportBundle\Entity\Underlying;
use Jack\ImportBundle\Entity\Cycle;
use Jack\ImportBundle\Entity\Strike;
use Jack\ImportBundle\Entity\Chain;

class ImpVolController extends Controller
{
    /*
     * 1. get data from underlying (all data with day)
     * 2. generate iv for every day using function with day input
     * 3.
     */
    protected static $sampleSize = array(
        'oneDay' => 1,
        'twoDay' => 2,
        'oneWeek' => 5,
        'twoWeek' => 10,
        'oneMonth' => 20,
        '45Day' => 30,
        'twoMonth' => 40,
        'threeMonth' => 60,
        'halfYear' => 120,
        'oneYear' => 252,
    );

    protected static $largestDTE = 3650;
    protected static $largestPrice = 10000;

    public function indexAction($symbol)
    {
        /*
         * 1. switch db to symbol db
         * 2. loop sample size
         * 3. get closest to expire date cycle
         * 4. calculate the iv
         * 5. format save it into db
         */
        // self::$sampleSize;

        $this->get('jack_service.fastdb')->switchSymbolDb($symbol);

        // TODO: negative strike modify do not work, add it
        // TODO: test price modify too
        // day modify cannot negative
        // strike selection still not write yet
        // get all result is different from get 1 result
        $this->getClosestStrike(45, 2, -1);

        // TODO: create multiple functions
        // past underlying into cycle function
        // past cycle into strike function
        // return a list of correct chain

        /*
         * function find underlying
         * example: findUnderlyingAll, findUnderlyingByDTE
         * select all
         * select by day to expire ($day)
         * select by past movement ($day, $move)
         * select by iv ** iv% **
         * select by earning
         * and more
         */

        /*
         * function find cycle
         * select all
         * select closest to day to expire ($date)
         *
         */


        // return
        return $this->render(
            'JackStatisticBundle:ImpVol:index.html.twig',
            array()
        );
    }


    /**
     * @param int $dayModify
     * use to modify current date + day to get the closest day to expire cycle
     * @param int $priceModify
     * use to modify current price + price to get the closest strike contract
     * for example: using the current price 169.3 to get the closest strike contract 170 not 165
     * because 170 is closest even thought it will be in the money
     * @param int $strikeModify
     * use to modify current strike + strike to get the up or down closest strike contract
     * for example: closest strike contract is 175, if set strike modify +1, it will
     * jump to the next strike contract which is +5 for strike contract 180
     * @param string $strikeSelection
     *
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error when getting data from db and error calculation
     */

    private function getClosestStrike($dayModify = 0, $priceModify = 0,
                                      $strikeModify = 0, $strikeSelection = 'atm')
    {
        // set utc so time date will not cause erro
        date_default_timezone_set('UTC');

        // generate a list of date which is closest to underlying
        // call and put
        /*
         * 1. open underlying, get everyday
         * 2. use everyday price to select strike
         * 3.
         */

        $symbolEM = $this->getDoctrine()->getManager('symbol');
        // get a list of underlyings
        $underlyings = $symbolEM
            ->getRepository('JackImportBundle:Underlying')
            ->findBy(array(), array('date' => 'asc'));

        // get a list of cycles
        $cycles = $symbolEM
            ->getRepository('JackImportBundle:Cycle')
            ->findBy(array(), array('expiredate' => 'asc'));

        // get a list of strike
        $strikes = $symbolEM
            ->getRepository('JackImportBundle:Strike')
            ->findBy(array(), array('price' => 'asc'));

        // generate a list of date cloest to dte
        $closestChainArray = array();
        foreach ($underlyings as $underlying) {
            if (!($underlying instanceof Underlying)) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager!'
                );
            }

            $underlyingId = $underlying->getId();

            $currentUnderlyingDate = $underlying->getDate()->format('Y-m-d');
            $modifyExpireUnderlyingDate = $underlying->getDate()->modify("+$dayModify days")->format('Y-m-d');
            //$modifyExpireUnderlyingDate = $underlying->getDate()->modify("+$dte days");

            // now get cycle nearest expire date
            $dateDiffArray = array();
            $cycleDateArray = array();
            // generate a list of date diff
            foreach ($cycles as $cycle) {
                if (!($cycle instanceof Cycle)) {
                    throw $this->createNotFoundException(
                        'Error [ Cycle ] object from entity manager!'
                    );
                }

                $currentCycleId = $cycle->getId();
                $currentCycleDate = $cycle->getExpiredate()->format('Y-m-d');

                $datetime1 = new \DateTime($modifyExpireUnderlyingDate);
                $datetime2 = new \DateTime($currentCycleDate);

                $dateDiff = intval($datetime1->diff($datetime2)->format("%R%a"));

                if ($dateDiff >= 0) {
                    $dateDiffArray[$currentCycleId] = $dateDiff;
                    $cycleDateArray[$currentCycleId] = $currentCycleDate;
                }
            }

            // use the date diff array to check exist in db
            $closestDayBetween = 0;
            $closestCycleDate = 0;
            $closestCycleId = 0;
            foreach ($dateDiffArray as $cycleId => $dateDiff) {
                $chain = $symbolEM
                    ->getRepository('JackImportBundle:Chain')
                    ->findOneBy(
                        array(
                            'cycleid' => $cycleId,
                            'underlyingid' => $underlyingId,
                        )
                    );

                if ($chain) {
                    $closestDayBetween = $dateDiff;
                    $closestCycleId = $cycleId;
                    $closestCycleDate = $cycleDateArray[$cycleId];
                    break;
                }
            }

            // set underlying price for compare
            $currentUnderlyingPrice = $underlying->getLast();
            $modifyUnderlyingPrice = $underlying->getLast() + $priceModify;

            // TODO: finally working date differ in cycle, next is strike
            // generate a list of price diff
            $priceDiffCallArray = array();
            $priceStrikeCallArray = array();
            foreach ($strikes as $strike) {
                if (!($strike instanceof Strike)) {
                    throw $this->createNotFoundException(
                        'Error [ Strike ] object from entity manager!'
                    );
                }

                $currentStrikePrice = $strike->getPrice();

                if ($strike->getCategory() == 'CALL') {

                    $priceDiff = floatval(number_format($currentStrikePrice - $modifyUnderlyingPrice, 2));

                    if ($priceDiff < 0) {
                        $priceDiff = -$priceDiff;
                    }

                    $priceDiffCallArray[$strike->getId()] = $priceDiff;
                    $priceStrikeCallArray[$strike->getId()] = $currentStrikePrice;
                }
            }

            // use the strike and cycle to search db exist
            asort($priceDiffCallArray);

            $nextChain = 0;
            if ($strikeModify) {
                // if strike modify is set use the next strike contract
                $nextChain = $strikeModify;
            }

            foreach ($priceDiffCallArray as $strikeId => $priceDiffCall) {
                $chain = $symbolEM
                    ->getRepository('JackImportBundle:Chain')
                    ->findOneBy(
                        array
                        (
                            'strikeid' => $strikeId,
                            'cycleid' => $closestCycleId,
                            'underlyingid' => $underlyingId,
                        )
                    );


                if ($chain) {
                    $closestStrikeDiff = $priceDiffCall;
                    $closestStrikeId = $strikeId;
                    $closestStrikePrice = $priceStrikeCallArray[$strikeId];

                    if ($nextChain) {
                        $nextChain--;
                    } else {
                        break;
                    }
                }

            }


            $a = 1;
        }


    }


    private function selectChainData($day)
    {
        /*
         * 1. generate a list of date + $day (currentDate + 45)
         * 2. get the list from db where dte nearest to 45
         * 3.
         */


    }

    public function getDataAction()
    {
    }


    public function calDataAction()
    {
    }

    public function formatDataAction()
    {
    }

    public function saveDataAction()
    {
    }

}
