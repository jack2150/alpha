<?php

namespace Jack\StatisticBundle\Controller;

use Jack\FindBundle\Controller\FindController;

use Jack\ImportBundle\Entity\Underlying;

/**
 * Class HVController
 * @package Jack\StatisticBundle\Controller
 */
class HVController extends FindController
{
    protected $underlyings;
    //protected $cycles;
    //protected $strikes;

    protected static $sampleSize = array(
        'oneWeek' => 5,
        'twoWeek' => 10,
        'oneMonth' => 20,
        'twoMonth' => 40,
        'threeMonth' => 60,
        'halfYear' => 126,
        'oneYear' => 252,
    );


    public function resultAction($symbol)
    {
        // init the data ready for use
        $this->init($symbol);

        /*
         * 1. get a list of underlying price
         * 2. using tick sample size 20 + 1 to get everyday change %
         */
        $sampleSize = self::$sampleSize['oneMonth'];

        /*
        // get 20 + 1 days of price changes standard deviation

        $hv = $this->sampleSizeHV($sampleSize, '2012-5-31');

        $debugResults = "$sampleSize Days HV: ".number_format($hv, 2, '.', '.')."%";
        */

        $debugResults = array();
        foreach ($this->underlyings as $underlying) {
            if (!($underlying instanceof Underlying)) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager!'
                );
            }

            $currentDate = $underlying->getDate()->format('Y-m-d');
            $hv = $this->sampleSizeHV($sampleSize, $currentDate);

            $debugResults[] =
                "ID: " . $underlying->getId() . ": " .
                "$currentDate - $sampleSize Days HV: "
                . number_format($hv, 2, '.', '.') . "% \n\n";
        }

        // TODO: next check db exist, create db, insert db


        return $this->render(
            'JackStatisticBundle:HV:result.html.twig',
            array('debugResults' => $debugResults)
        );
    }

    public function sampleSizeHV($sampleSize, $startDate)
    {
        // define price array
        $priceArray = array();
        $debugPriceArray = array(); // debug use only

        // set start date, will be start date - sample size
        $startDate = new \DateTime($startDate);
        $sampleDate = $startDate->modify("-$sampleSize days");


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
            }
        }

        // after get a list of last price, get the latest 20 from the array
        $slide = ($sampleSize + 1) * -1;
        $priceArray = array_slice($priceArray, $slide);
        $debugPriceArray = array_slice($debugPriceArray, $slide);

        // check if sample have more than sample size + 1 days (20+1=21)
        $haveSample = 0;
        if (count($priceArray) > $sampleSize) {
            // calculate change and remain
            $haveSample = 1;
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
