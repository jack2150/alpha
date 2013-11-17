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


        // all done show output
        return $this->render(
            'JackStatisticBundle:HV:result.html.twig',
            array('debugResults' => $debugResults)
        );
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
