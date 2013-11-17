<?php

namespace Jack\StatisticBundle\Controller;


use Jack\ImportBundle\Entity\Hv;
use Jack\ImportBundle\Entity\Underlying;
use Jack\StatisticBundle\Controller\DefaultController;

/**
 * Class HVController
 * @package Jack\StatisticBundle\Controller
 */
class HVController extends DefaultController
{
    protected static $sampleSize = array(
        'oneWeek' => 5,
        'twoWeek' => 10,
        'oneMonth' => 20,
        'twoMonth' => 40,
        'threeMonth' => 60,
        'halfYear' => 126,
        'oneYear' => 252,
    );

    //protected $cycles;
    //protected $strikes;
    protected $underlyings;
    protected $hvs;


    /**
     * @param $symbol
     */
    public function initHV($symbol)
    {
        // init the data ready for use
        $this->init($symbol);

        // create new hv table if table not exists
        if (!$this->checkTableExist('hv')) {
            $this->createTable('Jack\ImportBundle\Entity\Hv');
        }

        // get the underlying result
        $this->underlyings = $this->findUnderlyingAll();

        // set hv data
        $this->setHV();
    }

    // TODO: next put all into a function, create 52 week (year) high / low / rank function

    /**
     * @param $symbol
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function resultAction($symbol)
    {
        // init all the data
        $this->initHV($symbol);

        /*
         * 1. get a list of underlying price
         * 2. using tick sample size 20 + 1 to get everyday change %
         */
        $sampleSize = self::$sampleSize['oneMonth'];

        // for generate hv value only
        //$debug = $this->generateValueHV($sampleSize);

        // for generate year high, low, and daily rank
        $debug = $this->setYearHighLowRankHV();


        // all done show output
        return $this->render(
            'JackStatisticBundle:HV:result.html.twig',
            array('debugResults' => $debug)
        );
    }

    public function setYearHighLowRankHV()
    {
        /*
         * 0. create an array of hv using underlying date as key
         * 1. get the symbol last date + 1 year (exact date)
         * 2. run a loop to get past exact 1 year hv
         * 3. get the max and min of the list
         */

        // create an array date => hv

        $dateHVArray = array();
        foreach ($this->hvs as $hv) {
            // error checking
            if (!($hv instanceof Hv)) {
                throw $this->createNotFoundException(
                    'Error [ HV ] object from entity manager!'
                );
            }

            $dateHVArray[$hv->getUnderlyingid()->getDate()->format('Y-m-d')]
                = $hv->getValue();
        }


        // create a new sort array
        /*
        $sortHVArray = array();
        foreach ($this->underlyings as $underlying)
        {
            // error checking
            if (!($underlying instanceof Underlying)) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager!'
                );
            }

            $currentDate = $underlying->getDate()->format('Y-m-d');

            if (isset($dateHVArray[$currentDate])) {
                $sortValueArrayHV[] = $dateHVArray[$currentDate];
                $sortDateArrayHV[] = $currentDate;
            }
        }
        */


        // do loop later

        // start date, 1 year after first date
        //$startDate = '2009-12-30';


        /*
         * create a every date array with
         * date as key, value as hv
         * if no hv value, it set to null
         */
        $firstDate = new \DateTime($this->symbolObject->getFirstdate()->format('Y-m-d'));
        $lastDate = new \DateTime($this->symbolObject->getLastdate()->format('Y-m-d'));

        $dayDiff = intval($firstDate->diff($lastDate)->format("%a"));


        // an array of include all date (holiday, saturday, sunday)
        $dailyArrayHV = array();
        for ($day = 0; $day <= $dayDiff; $day++) {
            $currentDay = new \DateTime($firstDate->format('Y-m-d'));
            $currentDay = $currentDay->modify("+$day day")->format('Y-m-d');

            if (isset($dateHVArray[$currentDay])) {
                $dailyArrayHV[$currentDay] = $dateHVArray[$currentDay];
            } else {
                $dailyArrayHV[$currentDay] = null;
            }
        }

        // loop section
        $startDate = new \DateTime($this->symbolObject->getFirstdate()->format('Y-m-d'));

        // get teh start and end date
        $sampleStartDate = $startDate->format('Y-m-d');
        $sampleEndDate = $startDate->modify("+1 year")->format('Y-m-d');

        // date key array and use it as index
        $DateKeyArray = array_keys($dailyArrayHV);
        //$DateIndexArray = array_search('SX1T_1',$DateKeyArray);

        // get the start and end position in array
        $sampleStartPosition = array_search($sampleStartDate, $DateKeyArray);
        $sampleEndPosition = array_search($sampleEndDate, $DateKeyArray);

        // get the array of start until end position, then remove null
        $sampleArray = array_slice($dailyArrayHV, $sampleStartPosition, $sampleEndPosition);
        $sampleArray = array_filter($sampleArray, 'strlen');

        // get the max and min value
        $maxHV = max($sampleArray);
        $minHV = min($sampleArray);

        // insert into table


        $a = 1;


        return array();
    }

    /**
     * @param $sampleSize
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function generateValueHV($sampleSize)
    {
        // start the entity manager
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        // start the underlying loop
        $debugResults = array();
        foreach ($this->underlyings as $underlying) {
            // error checking
            if (!($underlying instanceof Underlying)) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager!'
                );
            }

            // if not exist in database, insert it
            if (!($this->checkSampleHVExist($sampleSize, $underlying->getId()))) {
                // set current date
                $currentDate = $underlying->getDate()->format('Y-m-d');

                // calculate the hv value for current date
                $hvValue = $this->sampleSizeHV($sampleSize, $currentDate);

                // debug use only
                $debugResults[$underlying->getId()] = "ID: " . $underlying->getId() . ": " .
                    "$currentDate - $sampleSize Days HV: "
                    . number_format($hvValue * 100, 2, '.', '.') . "%";


                // if hv is not empty or zero, where it have no enough sample
                if ($hvValue) {
                    // format hv into 6 decimals
                    $hvValue = number_format($hvValue, 6);

                    // create a new hv object
                    $hvObject = new Hv();

                    // add data into object
                    $hvObject->setSample($sampleSize);
                    $hvObject->setValue($hvValue);
                    $hvObject->setUnderlyingid($underlying);

                    // add into database
                    $symbolEM->persist($hvObject);

                    $debugResults[$underlying->getId()] .= ", Added into db!\n";
                } else {
                    $debugResults[$underlying->getId()] .= ", Not enough sample!\n";
                }
            }
        }

        // insert into hv table
        $symbolEM->flush();

        return $debugResults;
    }

    /**
     * @param $sampleSize
     * @param $underlyingId
     * @return int
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function checkSampleHVExist($sampleSize, $underlyingId)
    {
        $exist = 0;
        foreach ($this->hvs as $hv) {
            // error checking
            if (!($hv instanceof Hv)) {
                throw $this->createNotFoundException(
                    'Error [ HV ] object from entity manager!'
                );
            }

            if ($hv->getSample() == $sampleSize
                && $hv->getUnderlyingid()->getId() == $underlyingId
            ) {
                $exist = 1;
            }
        }

        return $exist;
    }


    public function setHV()
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        $this->hvs = $symbolEM
            ->getRepository('JackImportBundle:Hv')
            ->findAll();
    }

    /**
     * @param $sampleSize
     * total of sample collect to calculate hv
     * @param $startDate
     * start date use to calculate the sample
     * @return float|int
     * return a value of historical volatility
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function sampleSizeHV($sampleSize, $startDate)
    {
        date_default_timezone_get('UTC');

        // define price array
        $priceArray = array();
        $debugPriceArray = array(); // debug use only

        // set start date, will be start date - sample size
        $startDate = new \DateTime($startDate);
        //$sampleDate = $startDate->modify("-$sampleSize days");


        // get 21 data and put it into array
        $countSample = -1;
        foreach ($this->underlyings as $underlying) {
            if (!($underlying instanceof Underlying)) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager!'
                );
            }

            // set current date and day diff
            $currentDate = new \DateTime($underlying->getDate()->format('Y-m-d'));
            $dayDiff = intval($startDate->diff($currentDate)->format("%R%a"));

            // check is after start date, must bigger than sample size
            // for example, before 20 days of 2012-5-31
            if ($dayDiff < 1) {
                // add it into array
                $priceArray[$underlying->getId()] = $underlying->getLast();

                $debugPriceArray[$underlying->getId()] =
                    "startDate: " . $startDate->format("Y-m-d") .
                    " CurrentDate: " . $underlying->getDate()->format('Y-m-d') .
                    " DayDiff: " . $dayDiff .
                    " Sample No: " . $countSample .
                    " Last: " . $underlying->getLast();
            } else {
                break;
            }
        }


        // check if sample have more than sample size + 1 days (20+1=21)
        $haveSample = 0;
        if (count($priceArray) > $sampleSize) {
            // calculate change and remain
            $haveSample = 1;

            // after get a list of last price, get the latest 20 from the array
            $slide = ($sampleSize + 1) * -1;
            $priceArray = array_slice($priceArray, $slide);
            //$debugPriceArray = array_slice($debugPriceArray, $slide);
        }


        if ($haveSample) {
            // good sample

            // calculate price change for every 2 days
            $lastPrice = 0;
            $priceChangeArray = array();
            foreach ($priceArray as $underlyingId => $price) {
                // set current price
                $currentPrice = $price;

                // calculate price change
                // if both last price and current price not empty
                if ($currentPrice && $lastPrice) {
                    // formula
                    $priceChangeArray[$underlyingId] = floatval(
                        number_format(($currentPrice - $lastPrice) / $lastPrice, 6)
                    );

                    // debug use only
                    $debugPriceChangeArray[] =
                        "($currentPrice - $lastPrice) / $lastPrice = "
                        . $priceChangeArray[$underlyingId];

                }

                // set last price, if last price is empty
                $lastPrice = $price;
            }

            // now put the array into calculate standards deviation
            $sd = $this->sd($priceChangeArray);
            $timeValue = sqrt(self::$sampleSize['oneYear']);

            $hv = $sd * $timeValue;
        } else {
            // not enough sample
            $hv = 0;
        }

        return $hv;
    }


    // Function to calculate square of value - mean


    // Function to calculate standard deviation (uses sd_square)

    /**
     * @param $array
     * @return float
     */
    public function sd($array)
    {
        $callback = function ($x, $mean) {
            return pow($x - $mean, 2);
        };

        // square root of sum of squares devided by N-1
        return sqrt(array_sum(
                array_map($callback, $array, array_fill(0, count($array),
                    (array_sum($array) / count($array))))) / (count($array) - 1)
        );
    }


}
