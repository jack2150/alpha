<?php

namespace Jack\EarningBundle\Controller;

use Jack\ImportBundle\Entity\Underlying;
use Jack\ImportBundle\Entity\Earning;

class SweetSpotController extends EstimateController
{
    protected $matrixEarningPriceMove;

    protected $rangeSection;

    /**
     * @param $symbol
     * @param string $type
     * @param string $strategy
     * @param string $enter
     * @param string $exit
     * @param int $forward
     * @param int $backward
     * @param string $format
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function sweetSpotResultAction(
        $symbol,
        $type = 'maxEdge', $strategy = 'bullish',
        $enter = 'last', $exit = 'last',
        $forward = 1, $backward = 1,
        $format = 'medium'
    )
    {
        // increase the max time running
        set_time_limit(1200);

        // init the function
        $this->initEarning($symbol);

        // use sideway range
        //$this->rangeSection = array(0, 1.25, 2.5, 5, 7.5, 10);
        switch ($format) {
            case 'smallest':
                $this->rangeSection = array(0, 0.25, 0.75, 1.25, 1.75, 2.25);
                break;
            case 'smaller':
                $this->rangeSection = array(0, 0.5, 1, 1.5, 2, 2.5);
                break;
            case 'small':
                $this->rangeSection = array(0, 1, 2, 3, 4, 5);
                break;
            case 'large':
                $this->rangeSection = array(0, 2.5, 5, 7.5, 10, 12.5);
                break;
            case 'larger':
                $this->rangeSection = array(0, 3, 6, 9, 12, 15);
                break;
            case 'largest':
                $this->rangeSection = array(0, 4, 8, 12, 16, 20);
                break;
            case 'medium':
            default:
                $this->rangeSection = array(0, 2, 4, 6, 8, 10);
        }

        // generate a list of enter to exit, backward to forward day matrix
        $this->setMatrixEarningPriceMovementSummary($this->rangeSection, $backward, $forward);

        switch ($type) {
            case 'average':
                $sweetSpotType = 'bestAverage';
                $movement = $strategy;
                if ($enter == 'open+last' || $exit == 'open+last') {
                    $sweetSpotResult = $this->getBestAverageEnterExitResult($movement, $enter, $exit);
                } else {
                    $sweetSpotResult = $this->getBestAverageResult($movement, $enter, $exit);
                }

                break;
            case 'chance':
                $sweetSpotType = 'highestChance';
                $movement = $strategy;
                if ($enter == 'open+last' || $exit == 'open+last') {
                    $sweetSpotResult = $this->getHighestChanceEnterExitResult($movement, $enter, $exit);
                } else {
                    $sweetSpotResult = $this->getHighestChanceResult($movement, $enter, $exit);
                }

                break;
            case 'edge':
            default:
                // next: get the highest chance of bullish, bearish, sideway
                $sweetSpotType = 'maxEdge';
                $movement = $strategy;

                if ($enter == 'open+last' || $exit == 'open+last') {
                    $sweetSpotResult = $this->getMaxEdgeEnterExitResult($movement, $enter, $exit);
                } else {
                    $sweetSpotResult = $this->getMaxEdgeResult($movement, $enter, $exit);
                }

        }

        // render page
        $render = $this->render(
            'JackEarningBundle:SweetSpot:result.html.twig',
            array(
                'symbol' => $symbol,
                'countEarning' => count($this->earnings),

                'backward' => $backward,
                'forward' => $forward,

                'sweetSpotType' => $sweetSpotType,
                'movement' => $movement,

                'bullishRangeMaxEdge' => $sweetSpotResult,

                'summaryForm' => $this->createSummaryForm(
                    $type, $strategy, $enter, $exit, $forward, $backward, $format
                )
            )
        );

        // debug use only
        //file_put_contents("sweetspot.html", $render);


        // return the render page
        return $render;
    }

    /**
     * @param string $movement
     * @param $enters
     * @param $exits
     * @return array
     */
    public function getBestAverageEnterExitResult($movement = 'bullish', $enters, $exits)
    {
        // put side way range into more readable
        $ranges = array();
        foreach ($this->rangeSection as $key => $rangeSection) {
            $ranges[$key] = strval($rangeSection);
        }

        // create a max edge result array
        $bestAverageDataArray = array();
        if ($enters == 'open+last') {
            if ($exits == 'open+last') {
                $bestAverageDataArray['open-open'] = $this->getBestAverageResult($movement, 'open', 'open');
                $bestAverageDataArray['open-last'] = $this->getBestAverageResult($movement, 'open', 'last');
                $bestAverageDataArray['last-open'] = $this->getBestAverageResult($movement, 'last', 'open');
                $bestAverageDataArray['last-last'] = $this->getBestAverageResult($movement, 'last', 'last');
            } else {
                $bestAverageDataArray['open-' . $exits] =
                    $this->getBestAverageResult($movement, 'open', $exits);
                $bestAverageDataArray['last-' . $exits] =
                    $this->getBestAverageResult($movement, 'last', $exits);
            }
        } else {
            if ($exits == 'open+last') {
                $bestAverageDataArray[$enters . '-open'] =
                    $this->getBestAverageResult($movement, $enters, 'open');
                $bestAverageDataArray[$enters . '-last'] =
                    $this->getBestAverageResult($movement, $enters, 'last');
            }
        }

        // get highest chance from data array
        $rangeBestAverage = array();
        foreach ($ranges as $range) {
            $averageValueArray = array();
            foreach ($bestAverageDataArray as $enterExitKey => $bestAverageData) {
                // assign 'average' data into array
                $averageValueArray[$enterExitKey] =
                    $bestAverageData[$range]['data']['average'];

                // save memory
                unset($bestAverageData, $enterExitKey);
            }

            // get the max value for array
            if ($movement == 'bullish') {
                // get the max value for bullish
                $rangeBestAverageValue = max($averageValueArray);
            } else if ($movement == 'bearish') {
                // get the min value for bearish
                $rangeBestAverageValue = min($averageValueArray);

            } else {
                // convert all negative value to positive
                array_walk($averageValueArray,
                    function (&$item1, $key) {
                        if ($item1 < 0) {
                            $item1 = -$item1;
                        }
                    }
                );

                // get the closest to zero value for sideway
                $rangeBestAverageValue = min($averageValueArray);
            }

            // get the key from array
            $rangeBestAverageKey = array_search($rangeBestAverageValue, $averageValueArray);

            // set the array for return
            $rangeBestAverage[$range] = $bestAverageDataArray[$rangeBestAverageKey][$range];

            // save memory
            unset(
            $range,
            $rangeBestAverageValue,
            $rangeBestAverageKey,
            $averageValueArray
            );
        }

        // save memory
        unset($bestAverageDataArray);

        // return best average data
        return $rangeBestAverage;
    }


    /**
     * @param string $movement
     * @param $enters
     * @param $exits
     * @return array
     */
    public function getHighestChanceEnterExitResult($movement = 'bullish', $enters, $exits)
    {
        // put side way range into more readable
        $ranges = array();
        foreach ($this->rangeSection as $key => $rangeSection) {
            $ranges[$key] = strval($rangeSection);
        }

        // create a max edge result array
        $highestChanceDataArray = array();
        if ($enters == 'open+last') {
            if ($exits == 'open+last') {
                $highestChanceDataArray['open-open'] = $this->getHighestChanceResult($movement, 'open', 'open');
                $highestChanceDataArray['open-last'] = $this->getHighestChanceResult($movement, 'open', 'last');
                $highestChanceDataArray['last-open'] = $this->getHighestChanceResult($movement, 'last', 'open');
                $highestChanceDataArray['last-last'] = $this->getHighestChanceResult($movement, 'last', 'last');
            } else {
                $highestChanceDataArray['open-' . $exits] =
                    $this->getHighestChanceResult($movement, 'open', $exits);
                $highestChanceDataArray['last-' . $exits] =
                    $this->getHighestChanceResult($movement, 'last', $exits);
            }
        } else {
            if ($exits == 'open+last') {
                $highestChanceDataArray[$enters . '-open'] =
                    $this->getHighestChanceResult($movement, $enters, 'open');
                $highestChanceDataArray[$enters . '-last'] =
                    $this->getHighestChanceResult($movement, $enters, 'last');
            }
        }

        // set the percent string
        if ($movement == 'bullish') {
            $movementPercentStr = 'bullishPercent';
        } else if ($movement == 'bearish') {
            $movementPercentStr = 'bearishPercent';
        } else {
            $movementPercentStr = 'sideWayPercent';
        }

        // get highest chance from data array
        $rangeHighestChance = array();
        foreach ($ranges as $range) {
            $chanceValueArray = array();
            $edgeValueArray = array();
            foreach ($highestChanceDataArray as $enterExitKey => $highestChanceData) {

                $chanceValueArray[$enterExitKey] =
                    $highestChanceData[$range]['data']['summary'][$range][$movementPercentStr];

                $edgeValueArray[$enterExitKey] =
                    $highestChanceData[$range]['edge'];
            }

            // get the max value for array, only highest chance
            $highestChanceValue = max($chanceValueArray);

            // if more than 1 same percentage, use highest edge
            $highestChanceArray = array_filter($chanceValueArray,
                function ($value) use ($highestChanceValue) {
                    return $value == $highestChanceValue ? 1 : 0;
                }
            );

            // get an array of same chances for edge value
            $highestChanceMaxEdgeArray = array_intersect_key($edgeValueArray, $highestChanceArray);

            // get the max value for array
            if ($movement == 'bullish') {
                // get the max value for bullish
                $highestChanceMaxEdgeValue = max($highestChanceMaxEdgeArray);
            } else if ($movement == 'bearish') {
                // get the min value for bearish
                $highestChanceMaxEdgeValue = min($highestChanceMaxEdgeArray);

            } else {
                // convert all negative value to positive
                array_walk($highestChanceMaxEdgeArray,
                    function (&$item1, $key) {
                        if ($item1 < 0) {
                            $item1 = -$item1;
                        }
                    }
                );

                // get the closest to zero value for sideway
                $highestChanceMaxEdgeValue = min($highestChanceMaxEdgeArray);
            }

            // get the key from array
            $highestChanceMaxEdgeKey = array_search($highestChanceMaxEdgeValue, $highestChanceMaxEdgeArray);

            // set the array for return
            $rangeHighestChance[$range] = $highestChanceDataArray[$highestChanceMaxEdgeKey][$range];

            // save memory
            unset
            (
            $highestChanceMaxEdgeKey,
            $highestChanceMaxEdgeValue,
            $highestChanceMaxEdgeArray,
            $highestChanceValue,
            $chanceValueArray,
            $highestChanceArray,
            $highestChanceData,
            $edgeValueArray,
            $rangeSection,
            $key
            );
        }

        return $rangeHighestChance;
    }


    /**
     * @param string $movement
     * @param $enters
     * @param $exits
     * @return array
     */
    public function getMaxEdgeEnterExitResult($movement = 'bullish', $enters, $exits)
    {
        // put side way range into more readable
        $ranges = array();
        foreach ($this->rangeSection as $key => $rangeSection) {
            $ranges[$key] = strval($rangeSection);
        }

        // create a max edge result array
        $maxEdgeDataArray = array();
        if ($enters == 'open+last') {
            if ($exits == 'open+last') {
                $maxEdgeDataArray['open-open'] = $this->getMaxEdgeResult($movement, 'open', 'open');
                $maxEdgeDataArray['open-last'] = $this->getMaxEdgeResult($movement, 'open', 'last');
                $maxEdgeDataArray['last-open'] = $this->getMaxEdgeResult($movement, 'last', 'open');
                $maxEdgeDataArray['last-last'] = $this->getMaxEdgeResult($movement, 'last', 'last');
            } else {
                $maxEdgeDataArray['open-' . $exits] = $this->getMaxEdgeResult($movement, 'open', $exits);
                $maxEdgeDataArray['last-' . $exits] = $this->getMaxEdgeResult($movement, 'last', $exits);
            }
        } else {
            if ($exits == 'open+last') {
                $maxEdgeDataArray[$enters . '-open'] = $this->getMaxEdgeResult($movement, $enters, 'open');
                $maxEdgeDataArray[$enters . '-last'] = $this->getMaxEdgeResult($movement, $enters, 'last');
            }
        }

        // loop for every range
        $rangeMaxEdge = array();
        foreach ($ranges as $range) {
            // compare all range edge result to get the max for each
            $edgeValueArray = array();
            foreach ($maxEdgeDataArray as $maxEdgeDataKey => $maxEdgeData) {
                $edgeValueArray[$range][$maxEdgeDataKey] = $maxEdgeData[$range]['edge'];
            }

            // get the max value for array
            if ($movement == 'bullish') {
                // get the max value for bullish
                $maxEdgeValue = max($edgeValueArray[$range]);
            } else if ($movement == 'bearish') {
                // get the min value for bearish
                $maxEdgeValue = min($edgeValueArray[$range]);
            } else {
                // convert all negative value to positive
                array_walk($edgeValueArray[$range], function (&$item1, $key) {
                    if ($item1 < 0) {
                        $item1 = -$item1;
                    }
                });

                // sort the array remain key
                asort($edgeValueArray[$range]);

                // get the closest to zero value for sideway
                $maxEdgeValue = current($edgeValueArray[$range]);
            }

            // get the key from array
            $maxEdgeEnterExitKey = array_search($maxEdgeValue, $edgeValueArray[$range]);
            //$maxEdgeKey = array_search($maxEdgeValue, $maxEdgeDataArray[$maxEdgeEnterExitKey][$range]['edge']);

            // set the data
            $rangeMaxEdge[$range] = $maxEdgeDataArray[$maxEdgeEnterExitKey][$range];

        }

        return $rangeMaxEdge;
    }


    /**
     * @param string $searchType
     * @param $searchEnter
     * @param $searchExit
     * @return array
     */
    public function getMaxEdgeResult($searchType = 'bullish', $searchEnter, $searchExit)
    {
        // put side way range into more readable
        $ranges = array();
        foreach ($this->rangeSection as $key => $rangeSection) {
            $ranges[$key] = strval($rangeSection);
        }

        // new edge array
        $edgeArray = array();


        foreach ($this->matrixEarningPriceMove as $earningPriceMoveData) {
            // declare not found
            $found = 0;

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
                    // set edge data
                    $bullishEdge = $earningPriceMoveData['summary'][$range]['bullishEdge'];
                    $bearishEdge = $earningPriceMoveData['summary'][$range]['bearishEdge'];
                    $sidewayEdge = $earningPriceMoveData['summary'][$range]['sideWayEdge'];

                    if ($searchType == 'bullish') {
                        $edgeArray[$range][$searchKey] = floatval(number_format(
                            $bullishEdge, 4
                        ));

                    } elseif ($searchType == 'bearish') {
                        $edgeArray[$range][$searchKey] = floatval(number_format(
                            $bearishEdge, 4
                        ));

                    } else {
                        $edgeArray[$range][$searchKey] = floatval(number_format(
                            $sidewayEdge, 4
                        ));

                    }
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
                $maxEdge = max($edgeArray[$range]);
                $maxEdgeKey = $searchEnter . '-' . $searchExit . '-' . array_search($maxEdge, $edgeArray[$range]);
            } elseif ($searchType == 'bearish') {
                // minimum value
                $maxEdge = min($edgeArray[$range]);
                $maxEdgeKey = $searchEnter . '-' . $searchExit . '-' . array_search($maxEdge, $edgeArray[$range]);
            } else {
                // closest to 0
                // create a list of no negative value
                // sort the new array, and get the first item key
                $newKeyArray = array();
                foreach ($edgeArray[$range] as $key => $dailyEdge) {
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
                $maxEdge = $edgeArray[$range][$closestZeroKey];
                $maxEdgeKey = $searchEnter . '-' . $searchExit . '-' . array_search($maxEdge, $edgeArray[$range]);
            }

            $rangeMaxEdge[$range] = array(
                'edge' => $maxEdge,
                'data' => $this->matrixEarningPriceMove[$maxEdgeKey]
            );
        }

        return $rangeMaxEdge;
    }


    /**
     * @param string $searchType
     * @param $searchEnter
     * @param $searchExit
     * @return array
     */
    public function getHighestChanceResult($searchType = 'bullish', $searchEnter, $searchExit)
    {

        // put side way range into more readable
        $ranges = array();
        foreach ($this->rangeSection as $key => $rangeSection) {
            $ranges[$key] = strval($rangeSection);
        }

        // declare percent array
        $bullishPercent = array();
        $bearishPercent = array();
        $sidewayPercent = array();

        // declare edge array
        $bullishEdge = array();
        $bearishEdge = array();
        $sidewayEdge = array();


        foreach ($this->matrixEarningPriceMove as $earningPriceMoveData) {
            // declare not found
            $found = 0;

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

                foreach ($ranges as $range) {
                    // set all percent data into array
                    $bullishPercent[$range][$searchKey] = $earningPriceMoveData['summary'][$range]['bullishPercent'];
                    $bearishPercent[$range][$searchKey] = $earningPriceMoveData['summary'][$range]['bearishPercent'];
                    $sidewayPercent[$range][$searchKey] = $earningPriceMoveData['summary'][$range]['sideWayPercent'];

                    // set all edge data into array
                    $bullishEdge[$range][$searchKey] = $earningPriceMoveData['summary'][$range]['bullishEdge'];
                    $bearishEdge[$range][$searchKey] = $earningPriceMoveData['summary'][$range]['bearishEdge'];
                    $sidewayEdge[$range][$searchKey] = $earningPriceMoveData['summary'][$range]['sideWayEdge'];

                }
            }
        }

        // get the max for bullish
        $maxChangeArray = array();
        foreach ($ranges as $range) {
            if ($searchType == 'bullish') {
                // use bullish array
                $movementChanceArray = $bullishPercent[$range];
                $movementEdgeArray = $bullishEdge[$range];
            } elseif ($searchType == 'bearish') {
                // get the highest chance and get they day key
                $movementChanceArray = $bearishPercent[$range];
                $movementEdgeArray = $bearishEdge[$range];
            } else {
                // get the highest chance and get they day key
                $movementChanceArray = $sidewayPercent[$range];
                $movementEdgeArray = $sidewayEdge[$range];
            }

            // -----------------------------------------------------------

            // get a list of highest chance and key
            $highestChanceEdgeArray = array();
            if (count($movementChanceArray)) {
                $highestChanceValue = max($movementChanceArray);

                // create new array with only max highest key

                foreach ($movementChanceArray as $searchKey => $percentValue) {
                    if ($percentValue == $highestChanceValue) {
                        $highestChanceEdgeArray[$searchKey] = $movementEdgeArray[$searchKey];
                    }
                }
            }

            // check for max edge for every key above
            if ($searchType == 'bullish') {
                $highestChanceWithMaxEdgeValue = max($highestChanceEdgeArray);
            } elseif ($searchType == 'bearish') {
                // get the most lowest negative value
                $highestChanceWithMaxEdgeValue = min($highestChanceEdgeArray);
            } else {
                // convert all negative value to positive
                $sortHighestChanceEdgeArray = array();
                foreach ($highestChanceEdgeArray as $key => $highestChanceEdgeValue) {
                    if ($highestChanceEdgeValue < 0) {
                        $sortHighestChanceEdgeArray[$key] = -$highestChanceEdgeValue;
                    } else {
                        $sortHighestChanceEdgeArray[$key] = $highestChanceEdgeValue;
                    }
                }
                unset($key, $highestChanceEdgeValue);

                // sort the array low to high
                asort($sortHighestChanceEdgeArray);

                // get the array where the edge value is lowest
                $highestChanceWithMaxEdgeValue = current($sortHighestChanceEdgeArray);
            }

            $highestChanceWithMaxEdgeKey = array_search(
                $highestChanceWithMaxEdgeValue, $highestChanceEdgeArray
            );

            if (!$highestChanceWithMaxEdgeKey) {
                $highestChanceWithMaxEdgeKey = '0-0';
            }


            // -----------------------------------------------------------


            // set the key for matrix data
            $maxChangeKey = $searchEnter . '-' . $searchExit . '-' . $highestChanceWithMaxEdgeKey;

            // set the data to array
            $maxChangeArray[$range] = array(
                'edge' => $highestChanceWithMaxEdgeValue,
                'data' => $this->matrixEarningPriceMove[$maxChangeKey]
            );

        }

        return $maxChangeArray;
    }


    /**
     * @param string $searchType
     * @param $searchEnter
     * @param $searchExit
     * @return array
     */
    public function getBestAverageResult($searchType = 'bullish', $searchEnter, $searchExit)
    {
        // put side way range into more readable
        $ranges = array();
        foreach ($this->rangeSection as $key => $rangeSection) {
            $ranges[$key] = strval($rangeSection);
        }

        // declare percent array
        $bullishPercent = array();
        $bearishPercent = array();
        $sidewayPercent = array();

        // declare edge array
        $averages = array();

        foreach ($this->matrixEarningPriceMove as $earningPriceMoveData) {
            // declare not found
            $found = 0;

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

                $averages[$searchKey] = $earningPriceMoveData['average'];
            }
        }


        if ($searchType == 'bullish') {
            // get the max value for bullish
            $bestAverageValue = max($averages);

            // set the edge key
            $edgeKey = 'bullishEdge';
        } else if ($searchType == 'bearish') {
            // get the min value for bearish
            $bestAverageValue = min($averages);

            // set the edge key
            $edgeKey = 'bearishEdge';
        } else {
            // convert all negative value to positive
            array_walk($averages, function (&$item1, $key) {
                if ($item1 < 0) {
                    $item1 = -$item1;
                }
            });

            // sort the array remain key
            asort($averages);

            // get the closest to zero value for sideway
            $bestAverageValue = current($averages);

            // set the edge key
            $edgeKey = 'sideWayEdge';
        }

        $bestAverageKey = array_search(
            $bestAverageValue, $averages
        );

        unset($bestAverageValue, $averages);

        //
        // get the max for bullish
        $maxChangeArray = array();
        // set the key for matrix data
        $maxChangeKey = $searchEnter . '-' . $searchExit . '-' . $bestAverageKey;

        // loop for range data
        foreach ($ranges as $range) {
            // set the data to array
            $maxChangeArray[$range] = array(
                'edge' => $this->matrixEarningPriceMove[$maxChangeKey]['summary'][$range][$edgeKey],
                'data' => $this->matrixEarningPriceMove[$maxChangeKey]
            );
        }

        return $maxChangeArray;
    }


    /**
     * @param $sideWayRange
     * @param $backward
     * @param $forward
     */
    public function setMatrixEarningPriceMovementSummary($sideWayRange, $backward, $forward)
    {
        // for entry and exit data
        $enters = array('last', 'open', 'high', 'low');
        $exits = array('last', 'open', 'high', 'low');

        $maxBackward = $backward + 1;
        $maxForward = $forward + 1;

        // generate matrix summary result
        $matrixEarningPriceMove = array();
        foreach ($enters as $enter) {
            foreach ($exits as $exit) {
                for ($backward = 0; $backward < $maxBackward; $backward++) {
                    for ($forward = 0; $forward < $maxForward; $forward++) {

                        // calculate the summary result
                        list($max, $min, $average, $daily, $summary) =
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
                            'daily' => $daily,

                            'summary' => $summary,
                        );
                    }
                }
            }
        }

        $this->matrixEarningPriceMove = $matrixEarningPriceMove;
    }

    /**
     * @param string $type
     * @param string $strategy
     * @param string $enter
     * @param string $exit
     * @param int $forward
     * @param int $backward
     * @param string $format
     * @return \Symfony\Component\Form\FormView
     */
    public function createSummaryForm(
        $type = 'maxEdge', $strategy = 'bullish', $enter = 'last', $exit = 'last',
        $forward = 0, $backward = 0, $format = 'medium'
    )
    {
        $summaryFormData = array(
            'type' => $type,
            'strategy' => $strategy,
            'enter' => $enter,
            'exit' => $exit,
            'forward' => $forward,
            'backward' => $backward,
            'format' => $format,
            'symbol' => $this->symbol
        );

        $summarySelectForm = $this->createFormBuilder($summaryFormData)
            ->setAction($this->generateUrl(
                'jack_earning_sweetspot_redirect'
            ))
            ->add('type', 'choice', array(
                'choices' => array(
                    'edge' => 'Max Edge',
                    'chance' => 'Max Chance',
                    'average' => 'Max Average'
                ),
                'required' => true,
                'multiple' => false,
            ))
            ->add('strategy', 'choice', array(
                'choices' => array(
                    'bullish' => 'Bullish',
                    'sideway' => 'Sideway',
                    'bearish' => 'Bearish'
                ),
                'required' => true,
                'multiple' => false,
            ))


            ->add('enter', 'choice', array(
                'choices' => array(
                    'last' => 'Close Price',
                    'open' => 'Open Price',
                    'high' => 'Day High',
                    'low' => 'Day Low',
                    'open+last' => 'Open/Close',
                ),
                'required' => true,
                'multiple' => false,
            ))
            ->add('exit', 'choice', array(
                'choices' => array(
                    'last' => 'Close Price',
                    'open' => 'Open Price',
                    'high' => 'Day High',
                    'low' => 'Day Low',
                    'open+last' => 'Open/Close',
                ),
                'required' => true,
                'multiple' => false,
            ))

            ->add('forward', 'choice', array(
                'choices' => $this->createSelectDayArray('Forward', 60),
                'required' => true,
                'multiple' => false,
            ))
            ->add('backward', 'choice', array(
                'choices' => $this->createSelectDayArray('Backward', 60),
                'required' => true,
                'multiple' => false,
            ))

            ->add('format', 'choice', array(
                'choices' => array(
                    'smallest' => 'Smallest',
                    'smaller' => 'Smaller',
                    'small' => 'Small',
                    'medium' => 'Medium',
                    'large' => 'Large',
                    'larger' => 'Larger',
                    'largest' => 'Largest',
                ),
                'required' => true,
                'multiple' => false,
            ))

            ->add('symbol', 'hidden')

            ->add('generate', 'submit')
            ->getForm();


        return $summarySelectForm->createView();
    }


    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectAction()
    {
        $formData = $this->getRequest()->get('form');

        $type = $formData['type'];
        $strategy = $formData['strategy'];

        $enter = $formData['enter'];
        $exit = $formData['exit'];

        // integer only
        $forward = $formData['forward'];
        $backward = $formData['backward'];

        // smallest to largest
        $format = $formData['format'];

        // must exist
        $symbol = $formData['symbol'];

        return $this->redirect(
            $this->generateUrl('jack_earning_sweetspot_result',
                array(
                    'symbol' => $symbol,
                    'type' => $type,
                    'strategy' => $strategy,
                    'enter' => $enter,
                    'exit' => $exit,
                    'forward' => $forward,
                    'backward' => $backward,
                    'format' => $format
                )
            ));

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

        // set total days
        $totalDays = $this->countEarningDays();

        // calculate the min, max and average
        list($max, $min, $average, $daily) = $this->getMinMaxAverage($from, $to, $totalDays);

        // summary calculation
        $summaryReport = array(
            strval($sideWayRange[0]) => $this->calculateSummary($sideWayRange[0], $from, $to, $totalDays),
            strval($sideWayRange[1]) => $this->calculateSummary($sideWayRange[1] / 100, $from, $to, $totalDays),
            strval($sideWayRange[2]) => $this->calculateSummary($sideWayRange[2] / 100, $from, $to, $totalDays),
            strval($sideWayRange[3]) => $this->calculateSummary($sideWayRange[3] / 100, $from, $to, $totalDays),
            strval($sideWayRange[4]) => $this->calculateSummary($sideWayRange[4] / 100, $from, $to, $totalDays),
            strval($sideWayRange[5]) => $this->calculateSummary($sideWayRange[5] / 100, $from, $to, $totalDays)
        );

        return array(
            0 => $max,
            1 => $min,
            2 => $average,
            3 => $daily,

            4 => $summaryReport
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
