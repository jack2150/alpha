<?php


namespace Jack\EarningBundle\Controller;

use Jack\ImportBundle\Entity\Underlying;
use Jack\ImportBundle\Entity\Earning;

class SweetSpotController extends EstimateController
{
    protected static $maxBackward = 20;
    protected static $maxForward = 20;


    protected $matrixEarningPriceMove;

    protected $rangeSection;


    /**
     * @param $symbol
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sweetSpotResultAction($symbol)
    {
        // increase the max time running
        set_time_limit(600);

        // init the function
        $this->initEarning($symbol);

        // use sideway range
        //$this->rangeSection = array(0, 1.25, 2.5, 5, 7.5, 10);
        $this->rangeSection = array(0, 2, 4, 6, 8, 10);


        // generate a list of enter to exit, backward to forward day matrix
        $this->setMatrixEarningPriceMovementSummary($this->rangeSection);

        // get best max min average from matrix data
        //list($minAverage, $maxAverage) = $this->getMaxMinAverageEPM('last', 'last');

        // get the max min bullish from matrix data
        //$this->getMaxMinBullishEPM('last', 'last', $this->rangeSection);

        // get the most bullish
        $sweetSpotType = 'bullish';
        $bullishRangeMaxEdge = $this->getSweetspotResult($sweetSpotType, 'last', 'last');

        return $this->render(
            'JackEarningBundle:SweetSpot:result.html.twig',
            array(
                'symbol' => $symbol,
                'countEarning' => count($this->earnings),
                'searchType' => $sweetSpotType,
                'sweetSpotType' => $sweetSpotType,
                'bullishRangeMaxEdge' => $bullishRangeMaxEdge
            )
        );


    }

    public function redirectAction()
    {
    }


    /**
     * @param string $searchType
     * @param $searchEnter
     * @param $searchExit
     * @return array
     */
    public function getSweetspotResult($searchType = 'bullish', $searchEnter, $searchExit)
    {
        // rename search type
        $directionName = $searchType . 'Edge';

        // put side way range into more readable
        $ranges = array();
        foreach ($this->rangeSection as $key => $rangeSection) {
            $ranges[$key] = strval($rangeSection);
        }

        // new edge array
        $dailyEdgeArray = array();


        foreach ($this->matrixEarningPriceMove as $earningPriceMoveData) {
            // declare not found
            $found = 0;

            // put enter exit into more readable variable
            $currentBackward = $earningPriceMoveData['backward'];
            $currentForward = $earningPriceMoveData['forward'];


            // put enter exit into more readable variable
            $currentEnter = $earningPriceMoveData['enter'];
            $currentExit = $earningPriceMoveData['exit'];

            // check is same enter and exit timing
            if ($currentEnter == $searchEnter && $currentExit == $searchExit) {
                $found = 1;
            }

            // save memory
            unset($currentEnter, $currentExit);

            // found the enter and exit timing data
            if ($found) {
                // create a new key
                $searchKey = $earningPriceMoveData['backward'] . '-' . $earningPriceMoveData['forward'];

                // total day

                // create a new array for every sideway range
                foreach ($ranges as $range) {
                    $bullishEdge = $earningPriceMoveData['summary'][$range]['bullishEdge'];
                    $bearishEdge = $earningPriceMoveData['summary'][$range]['bearishEdge'];
                    $sideWayEdge = $earningPriceMoveData['summary'][$range]['sideWayEdge'];

                    // todo: bullish, bearish, sideway method

                    if ($searchType == 'bullish') {
                        $dailyEdgeArray[$range][$searchKey] = floatval(number_format(
                            $bullishEdge, 4
                        ));
                    } elseif ($searchType == 'bearish') {
                        $dailyEdgeArray[$range][$searchKey] = floatval(number_format(
                            $bearishEdge, 4
                        ));
                    } else {
                        $dailyEdgeArray[$range][$searchKey] = floatval(number_format(
                            $sideWayEdge, 4
                        ));
                    }


                    // real edge
                    /*
                    if ($searchType == 'bullish') {
                        // make all positive, bearish must
                        $bearishEdge = $bearishEdge > 0 ? $bearishEdge : -$bearishEdge;
                        $sideWayEdge = $sideWayEdge > 0 ? $sideWayEdge : -$sideWayEdge;

                        // do calculation
                        $realEdge = $bullishEdge - $bearishEdge - $sideWayEdge;
                    }
                    elseif ($searchType == 'bearish') {
                        // make all positive, bearish must
                        $bullishEdge = $bullishEdge < 0 ? $bullishEdge : -$bullishEdge;
                        $sideWayEdge = $sideWayEdge < 0 ? $sideWayEdge : -$sideWayEdge;

                        // do calculation
                        $realEdge = $bearishEdge + $bullishEdge + $sideWayEdge;
                    }
                    else {
                        // make all positive, bearish must
                        $bullishEdge = $bullishEdge > 0 ? $bullishEdge : -$bullishEdge;
                        $bearishEdge = $bearishEdge > 0 ? $bearishEdge : -$bearishEdge;

                        // do calculation
                        $realEdge = $sideWayEdge - $bullishEdge - $bearishEdge;
                    }

                    $dailyEdgeArray[$range][$searchKey] = floatval(number_format(
                        $realEdge, 4
                    ));
                    */
                }
            }

            // save memory
            unset($found);
        }


        // loop for every range
        $rangeMaxEdge = array();
        foreach ($ranges as $range) {
            // get maximum edge
            if ($searchType == 'bullish') {
                // maximum value
                $maxEdge = max($dailyEdgeArray[$range]);
                $maxEdgeKey = $searchEnter . '-' . $searchExit . '-' . array_search($maxEdge, $dailyEdgeArray[$range]);
            } elseif ($searchType == 'bearish') {
                // minimum value
                $maxEdge = min($dailyEdgeArray[$range]);
                $maxEdgeKey = $searchEnter . '-' . $searchExit . '-' . array_search($maxEdge, $dailyEdgeArray[$range]);
            } else {
                // closest to 0
                // create a list of no negative value
                // sort the new array, and get the first item key
                $newKeyArray = array();
                foreach ($dailyEdgeArray[$range] as $key => $dailyEdge) {
                    if ($dailyEdge < 0) {
                        $newKeyArray[$key] = -$dailyEdge;
                    } else {
                        $newKeyArray[$key] = $dailyEdge;
                    }
                }
                asort($newKeyArray);
                $closestZeroValue = current($newKeyArray);
                $closestZeroKey = array_search($closestZeroValue, $newKeyArray);

                // use the current because it is smaller
                $maxEdge = $dailyEdgeArray[$range][$closestZeroKey];
                $maxEdgeKey = $searchEnter . '-' . $searchExit . '-' . array_search($maxEdge, $dailyEdgeArray[$range]);
            }


            $rangeMaxEdge[$range] = array(
                'edge' => $maxEdge,
                'data' => $this->matrixEarningPriceMove[$maxEdgeKey]
            );
        }

        return $rangeMaxEdge;
    }

    public function getMaxMinBullishEPM($enter, $exit, $sideWayRange)
    {
        $bullishArray = null;

        $range0 = strval($sideWayRange[0]);
        $range1 = strval($sideWayRange[1]);
        $range2 = strval($sideWayRange[2]);
        $range3 = strval($sideWayRange[3]);
        $range4 = strval($sideWayRange[4]);
        $range5 = strval($sideWayRange[5]);


        foreach ($this->matrixEarningPriceMove as $earningPriceMoveData) {
            $useKey = $earningPriceMoveData['backward'] . '-' . $earningPriceMoveData['forward'];

            if ($earningPriceMoveData['enter'] == $enter &&
                $earningPriceMoveData['exit'] == $exit
            ) {
                // generate an max array
                //$maxArray[$useKey] = $earningPriceMoveData['max'];

                //$minArray[$useKey] = $earningPriceMoveData['min'];

                $bullishArray[$range0][$useKey] = $earningPriceMoveData['summary'][$range0]['bullish'];
                $bullishArray[$range1][$useKey] = $earningPriceMoveData['summary'][$range1]['bullish'];
                $bullishArray[$range2][$useKey] = $earningPriceMoveData['summary'][$range2]['bullish'];
                $bullishArray[$range3][$useKey] = $earningPriceMoveData['summary'][$range3]['bullish'];
                $bullishArray[$range4][$useKey] = $earningPriceMoveData['summary'][$range4]['bullish'];
                $bullishArray[$range5][$useKey] = $earningPriceMoveData['summary'][$range5]['bullish'];
            }
        }

    }

    public function getMaxMinAverageEPM($enter, $exit)
    {
        $averageArray = null;
        //$maxArray = null;
        //$minArray = null;

        foreach ($this->matrixEarningPriceMove as $earningPriceMoveData) {
            $useKey = $earningPriceMoveData['backward'] . '-' . $earningPriceMoveData['forward'];

            if ($earningPriceMoveData['enter'] == $enter &&
                $earningPriceMoveData['exit'] == $exit
            ) {
                // generate an max array
                //$maxArray[$useKey] = $earningPriceMoveData['max'];

                //$minArray[$useKey] = $earningPriceMoveData['min'];

                $averageArray[$useKey] = $earningPriceMoveData['average'];
            }

        }

        // minimum average price move
        $minAverage['value'] = min($averageArray);
        list($minAverage['backward'], $minAverage['forward']) =
            explode('-', array_search($minAverage['value'], $averageArray));
        $minAverageKey = $enter . '-' . $exit . '-' . $minAverage['backward'] . '-' . $minAverage['forward'];

        // maximum average price move
        $maxAverage['value'] = max($averageArray);
        list($maxAverage['backward'], $maxAverage['forward']) =
            explode('-', array_search($maxAverage['value'], $averageArray));
        $maxAverageKey = $enter . '-' . $exit . '-' . $maxAverage['backward'] . '-' . $maxAverage['forward'];

        // save memory
        unset($averageArray, $minAverage, $maxAverage);

        // return both max and min average
        return array(
            0 => $this->matrixEarningPriceMove[$minAverageKey],
            1 => $this->matrixEarningPriceMove[$maxAverageKey]
        );
    }


    /**
     * @param $sideWayRange
     */
    public function setMatrixEarningPriceMovementSummary($sideWayRange)
    {
        // for entry and exit data
        $enters = array('last', 'open', 'high', 'low');
        $exits = array('last', 'open', 'high', 'low');

        // generate matrix summary result
        $matrixEarningPriceMove = array();
        foreach ($enters as $enter) {
            foreach ($exits as $exit) {
                for ($backward = 0; $backward < self::$maxBackward; $backward++) {
                    for ($forward = 0; $forward < self::$maxForward; $forward++) {

                        // calculate the summary result
                        list($max, $min, $average, $summary) =
                            $this->getSummaryResult($sideWayRange, $enter, $exit, $forward, $backward);

                        // save the data into array
                        $summaryKey = "$enter-$exit-$backward-$forward";
                        $matrixEarningPriceMove[$summaryKey] = array(
                            'enter' => $enter,
                            'exit' => $exit,
                            'backward' => $backward,
                            'forward' => $forward,

                            'format' => $sideWayRange,

                            'max' => $max,
                            'min' => $min,
                            'average' => $average,

                            'summary' => $summary,
                        );
                    }
                }
            }
        }

        $this->matrixEarningPriceMove = $matrixEarningPriceMove;
    }


    /**
     * @param $sideWayRange
     * @param $from
     * @param $to
     * @param $forward
     * @param $backward
     * @return array
     */
    public function getSummaryResult($sideWayRange, $from, $to, $forward, $backward)
    {
        $this->earningUnderlyings = $this->findUnderlyingByEarning2($forward, $backward);

        // get price estimates for all
        list($this->priceEstimates, $dateArray) = $this->getPriceEstimates();

        // calculate the min, max and average
        list($max, $min, $average) = $this->getMinMaxAverage($from, $to);

        // summary calculation
        $summaryReport = array(
            strval($sideWayRange[0]) => $this->calculateSummary($sideWayRange[0], $from, $to),
            strval($sideWayRange[1]) => $this->calculateSummary($sideWayRange[1] / 100, $from, $to),
            strval($sideWayRange[2]) => $this->calculateSummary($sideWayRange[2] / 100, $from, $to),
            strval($sideWayRange[3]) => $this->calculateSummary($sideWayRange[3] / 100, $from, $to),
            strval($sideWayRange[4]) => $this->calculateSummary($sideWayRange[4] / 100, $from, $to),
            strval($sideWayRange[5]) => $this->calculateSummary($sideWayRange[5] / 100, $from, $to)
        );

        return array(
            0 => $max,
            1 => $min,
            2 => $average,
            3 => $summaryReport
        );
    }


    /**
     * @param int $forward
     * @param int $backward
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function findUnderlyingByEarning2($forward = 0, $backward = 0)
    {
        $earningUnderlying = array();

        // if underlying is not set
        if (!count($this->underlyings)) {
            $this->underlyings = $this->getUnderlyingDateArray();
        }

        $underlyingDateArray = array_keys($this->underlyings);


        foreach ($this->earnings as $dateKey => $earning) {
            // error checking
            if (!($earning instanceof Earning)) {
                throw $this->createNotFoundException(
                    'Error [ Earning ] object from entity manager'
                );
            }

            $searchDate = $dateKey;

            $marketHour = strtolower($earning->getMarkethour());

            // find position in date array
            $currentPosition = array_search($searchDate, $underlyingDateArray);
            $startPosition = 0;
            $arrayLength = 0;
            switch ($marketHour) {
                case 'before':
                    $startPosition = $currentPosition - $backward - 1;
                    $arrayLength = $backward + $forward + 2;
                    break;
                case 'during':
                    $startPosition = $currentPosition - $backward;
                    $arrayLength = $backward + $forward + 1;

                    break;
                case 'after':
                    $startPosition = $currentPosition - $backward;
                    $arrayLength = $backward + $forward + 2;
                    break;
            }

            // get the array of earning underlying
            $underlyingData = array_slice($this->underlyings, $startPosition, $arrayLength);

            // put into a array of earnings and underlyings
            $earningUnderlying[$dateKey] = array(
                'earning' => $earning,
                'underlyings' => $underlyingData
            );

            // save memory
            unset($dateKey, $earning, $searchDate, $marketHour,
            $currentPosition, $startPosition, $arrayLength);
        }

        return $earningUnderlying;
    }

    /**
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getUnderlyingDateArray()
    {
        $underlyings = $this->findUnderlyingAll();

        $newUnderlyings = array();
        foreach ($underlyings as $underlying) {
            // error checking
            if (!($underlying instanceof Underlying)) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager'
                );
            }

            // using date as key
            $newUnderlyings[$underlying->getDate()->format('Y-m-d')] = $underlying;
        }
        unset($underlyings);

        return $newUnderlyings;
    }

}
