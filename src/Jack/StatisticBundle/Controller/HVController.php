<?php

namespace Jack\StatisticBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

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

    // underlying and hv data from database
    protected $underlyings;
    protected $hvs;

    // a list of everyday price with null
    protected $dailyPriceArray;

    // a list of hv with date as key from hvs
    protected $hvDateArray;


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


    }

    // todo: next index page for selection, and result page
    public function indexAction(Request $request)
    {
        $generateHVData = array(
            'symbol' => null,
            'action' => 'value20d',
        );

        $generateHVForm = $this->createFormBuilder($generateHVData)
            ->add('symbol', 'choice', array(
                'choices' => $this->getSymbolArray(),
                'required' => true,
                'multiple' => false,
            ))
            ->add('action', 'choice', array(
                'choices' => array(
                    'value20d' => 'Generate 20 Days HV Value Only',
                    'other20d' => 'Generate 20 Days Year High, Low, Rank',
                    'value40d' => 'Generate 40 Days HV Value Only',
                    'other40d' => 'Generate 40 Days Year High, Low, Rank',
                ),
                'required' => true,
                'multiple' => false,
            ))
            ->add('generate', 'submit')
            ->getForm();

        $generateHVForm->handleRequest($request);

        if ($generateHVForm->isValid()) {
            $generateHVData = $generateHVForm->getData();

            $symbol = $generateHVData['symbol'];
            $action = $generateHVData['action'];

            $returnUrl = '';
            $params = array();
            switch ($action) {
                case 'other40d':
                    $returnUrl = 'jack_stat_hv_result';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower('others'),
                        'sample' => 40
                    );
                    break;
                case 'value40d':
                    $returnUrl = 'jack_stat_hv_result';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower('value'),
                        'sample' => 40
                    );
                    break;
                case 'other20d':
                    $returnUrl = 'jack_stat_hv_result';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower('others'),
                        'sample' => 20
                    );
                    break;
                case 'value20d':
                default:
                    $returnUrl = 'jack_stat_hv_result';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower('value'),
                        'sample' => 20
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

        // render page
        return $this->render(
            'JackStatisticBundle:HV:index.html.twig',
            array(
                'generateHVForm' => $generateHVForm->createView(),
            )
        );
    }


    /**
     * @param $symbol
     * @param $action
     * @param $sample
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resultAction($symbol, $action, $sample)
    {
        $debug = array();
        /*
         * 1. get a list of underlying price
         * 2. using tick sample size 20 + 1 to get everyday change %
         */
        //$sampleSize = self::$sampleSize['oneMonth'];

        // init all the data
        $this->initHV($symbol, $sample);

        $a = 1;

        switch ($action) {
            case 'others':
                $debug = $this->setYearHighLowRankHV($sample);
                break;

            case 'value':
            default:
                $debug = $this->generateValueHV($sample);
                $a = 1;
                break;
        }

        // all done show output
        return $this->render(
            'JackStatisticBundle:HV:result.html.twig',
            array('debugResults' => $debug)
        );
    }

    // Core Functions
    /**
     * @param $sampleSize
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function generateValueHV($sampleSize)
    {
        $debugResults = array();
        // start the entity manager
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        // set daily price array
        $this->dailyPriceArray = $this->getDailyPriceArray();

        // set hv data, can select sample size
        $this->setHV($sampleSize);

        // set hv date array
        $this->hvDateArray = $this->getHVDateArray();

        // start the underlying loop
        $debugCount = -1;
        foreach ($this->underlyings as $underlying) {
            // error checking
            if (!($underlying instanceof Underlying)) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager!'
                );
            }

            $currentDate = $underlying->getDate()->format('Y-m-d');

            // if not exist in database, insert it
            if (!($this->isSetSampleHV($currentDate))) {
                //if (!($this->checkSampleHVExist($sampleSize, $underlying->getId()))) {
                // set current date


                $hvValue = $this->calculateHV($sampleSize, $currentDate);
                // calculate the hv value for current date
                //$hvValue = $this->sampleSizeHV($sampleSize, $currentDate);

                // debug use only
                $debugCount++;
                $debugResults[$underlying->getId()] = array(
                    'count' => $debugCount,
                    'data' => "ID: " . $underlying->getId() . ": " .
                    "$currentDate - $sampleSize Days HV: "
                    . number_format($hvValue * 100, 2, '.', '.') . "%",
                    'result' => ''
                );


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

                    $debugResults[$underlying->getId()]['result'] = 1;
                } else {
                    $debugResults[$underlying->getId()]['result'] = 0;
                }
            }
        }

        // insert into hv table
        $symbolEM->flush();


        return $debugResults;
    }

    /**
     * @param $sampleSize
     * @param $currentDate
     * @return float|int
     */
    public function calculateHV($sampleSize, $currentDate)
    {
        // get an array of sample size +1 before start date

        $DateKeyArray = array_keys($this->dailyPriceArray);

        $currentDatePosition = array_search($currentDate, $DateKeyArray);

        // if sample position is less than sample size then do not run
        $priceArray = array();
        if ($currentDatePosition > $sampleSize) {

            // get a list of array with sample size
            $startPosition = $currentDatePosition - $sampleSize - 1;
            $priceArray = array_slice($this->dailyPriceArray, $startPosition, $sampleSize + 1);
        }

        // have valid sample
        if (count($priceArray) >= $sampleSize) {
            // good sample

            // calculate price change for every 2 days
            $lastPrice = 0;
            $priceChangeArray = array();
            foreach ($priceArray as $priceDate => $price) {
                // set current price
                $currentPrice = $price;

                // calculate price change
                // if both last price and current price not empty
                if ($currentPrice && $lastPrice) {
                    // formula
                    $priceChangeArray[$priceDate] = floatval(
                        number_format(($currentPrice - $lastPrice) / $lastPrice, 6)
                    );

                    // debug use only
                    $debugPriceChangeArray[] =
                        "($currentPrice - $lastPrice) / $lastPrice = "
                        . $priceChangeArray[$priceDate];

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

    /**
     * @param $sampleSize
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function setYearHighLowRankHV($sampleSize)
    {
        date_default_timezone_get('UTC');

        // set hv data, can select sample size
        $this->setHV($sampleSize);

        // debug use only
        $hvData = array();
        $debugAllHVData = array();

        // get every day (include holiday, sunday, saturday) array of hv
        // key is date, value is hv
        $dailyArrayHV = $this->getDailyArrayHV();

        // get first date in hv array with no null
        // after first sample is generate
        $firstDate = key(array_filter($dailyArrayHV, 'strlen'));

        // date key array and use it as index
        $DateKeyArray = array_keys($dailyArrayHV);

        // set start date, use first hv date as start date
        // it was 1 year after 1st sample generated
        $startDate = new \DateTime($firstDate);
        $startDate->modify("+1 year");
        // loop every underlying
        $debugCount = -1;
        foreach ($this->underlyings as $underlying) {
            // error checking
            if (!($underlying instanceof Underlying)) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager!'
                );
            }

            // set underlying id
            $underlyingId = $underlying->getId();

            // set current date for search
            $currentDate = new \DateTime($underlying->getDate()->format('Y-m-d'));
            $currentDateStr = $currentDate->format('Y-m-d');
            $dayDiff = intval($startDate->diff($currentDate)->format('%R%a'));

            // after 365
            if ($dayDiff > 0) {
                // start getting year high low rank
                // note because modify is persist data, so end first
                //$sampleEndDate = $currentDate->modify('-1 day')->format('Y-m-d');
                $sampleEndDate = $currentDate->format('Y-m-d');
                $leapYear = $currentDate->format('L');
                $sampleStartDate = $currentDate->modify("-1 year")->format('Y-m-d');

                // find the position of date
                $sampleEndPosition = array_search($sampleEndDate, $DateKeyArray);
                $sampleStartPosition = array_search($sampleStartDate, $DateKeyArray);

                // total sample, if leap year 366, if normal year 365
                $totalSample = $sampleEndPosition - $sampleStartPosition;

                // get the array of start until end position, then remove null
                // 365 is from 2011-1-1 until 2011-12-31 is 365 days
                // because if 2011-1-1 until 2012-1-1 is 366 days (+1day start)
                $sampleArray = array_slice($dailyArrayHV, $sampleStartPosition, $totalSample);

                $sampleArray = array_filter($sampleArray, 'strlen');

                // get the max and min value
                $maxHV = max($sampleArray);
                $minHV = min($sampleArray);

                // calculate rank
                $currentHV = $dailyArrayHV[$currentDateStr];
                $rankHV = 1 - (($maxHV - $currentHV) / ($maxHV - $minHV));

                // set all data into array
                $hvData[$underlyingId] = array(
                    'high' => floatval(number_format($maxHV, 6)),
                    'low' => floatval(number_format($minHV, 6)),
                    'rank' => floatval(number_format($rankHV, 6))
                );

                // debug use only
                $debugCount++;
                $debugAllHVData[$underlyingId] = array(
                    'count' => $debugCount,
                    'data' => "Date: $currentDateStr " .
                    " Sample: $sampleStartDate ($sampleStartPosition)" .
                    " ~ $sampleEndDate ($sampleEndPosition) = $totalSample" .
                    ", HV: " . number_format($currentHV * 100, 2) .
                    "% High: " . number_format($maxHV * 100, 2) .
                    "% Low: " . number_format($minHV * 100, 2) .
                    "% Rank: " . number_format($rankHV * 100, 2) . "%",
                    'result' => 0
                );
            } else {
                // debug use only
                $debugCount++;
                $debugAllHVData[$underlyingId] = array(
                    'count' => $debugCount,
                    'data' => "Date: $currentDateStr" .
                    ", HV: " . number_format($dailyArrayHV[$currentDateStr] * 100, 2),
                    'result' => 0
                );
            }
        }


        $symbolEM = $this->getDoctrine()->getManager('symbol');


        // loop to set data back into hvs
        foreach ($this->hvs as $currentKey => $hv) {
            // error checking
            if (!($hv instanceof Hv)) {
                throw $this->createNotFoundException(
                    'Error [ HV ] object from entity manager!'
                );
            }

            // underlying id, sample size, and hv is same
            $currentUnderlyingId = $hv->getUnderlyingid()->getId();
            $currentSampleSize = $hv->getSample();
            $currentDateHV = $hv->getValue();

            if (array_key_exists($currentUnderlyingId, $hvData)) {
                $debugAllHVData[$currentUnderlyingId]['result'] = 1;

                // set year high low rank into hv object
                $this->hvs[$currentKey]->setYearhigh($hvData[$currentUnderlyingId]['high']);
                $this->hvs[$currentKey]->setYearlow($hvData[$currentUnderlyingId]['low']);
                $this->hvs[$currentKey]->setRank($hvData[$currentUnderlyingId]['rank']);
            }
        }

        // insert the data now
        $symbolEM->flush();

        return $debugAllHVData;
    }

    // Sub Functions
    /**
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getDailyArrayHV()
    {
        // replace with function
        $dateHVArray = $this->getHVDateArray();

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

        return $dailyArrayHV;
    }

    /**
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getHVDateArray()
    {
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

        return $dateHVArray;
    }

    /**
     * @param $searchDate
     * @return int
     */
    public function isSetSampleHV($searchDate)
    {
        $exists = 0;

        // loop the hv date array
        foreach ($this->hvDateArray as $currentDate => $hvValue) {
            if ($currentDate == $searchDate) {
                $exists = 1;
            }
        }

        return $exists;
    }

    /**
     * @param $sampleSize
     * @param $priceDate
     * @return int
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function checkSampleHVExist($sampleSize, $priceDate)
    {
        $exist = 0;
        foreach ($this->hvs as $hv) {
            // error checking

            /*
            if (!($hv instanceof Hv)) {
                throw $this->createNotFoundException(
                    'Error [ HV ] object from entity manager!'
                );
            }
            */

            if ($hv->getSample() == $sampleSize
                // && $hv->getUnderlyingid()->getId() == $priceDate
            ) {
                $exist = 1;
            }

        }

        return $exist;
    }


    /**
     * @param $sampleSize
     */
    public function setHV($sampleSize)
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        $this->hvs = $symbolEM
            ->getRepository('JackImportBundle:Hv')
            ->findBy(array('sample' => $sampleSize));
    }

    /**
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getDailyPriceArray()
    {
        $dailyPriceArray = array();
        foreach ($this->underlyings as $underlying) {
            // error checking
            if (!($underlying instanceof Underlying)) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager!'
                );
            }

            $currentDate = $underlying->getDate()->format('Y-m-d');
            // create an array
            $dailyPriceArray[$currentDate] = $underlying->getLast();
        }


        return $dailyPriceArray;
    }

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

    /**
     * @param $sampleSize
     * total of sample collect to calculate hv
     * @param $startDate
     * start date use to calculate the sample
     * @return float|int
     * return a value of historical volatility
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    /*
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
            foreach ($priceArray as $priceDate => $price) {
                // set current price
                $currentPrice = $price;

                // calculate price change
                // if both last price and current price not empty
                if ($currentPrice && $lastPrice) {
                    // formula
                    $priceChangeArray[$priceDate] = floatval(
                        number_format(($currentPrice - $lastPrice) / $lastPrice, 6)
                    );

                    // debug use only
                    $debugPriceChangeArray[] =
                        "($currentPrice - $lastPrice) / $lastPrice = "
                        . $priceChangeArray[$priceDate];

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
    */


}
