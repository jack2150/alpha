<?php

namespace Jack\EarningBundle\Controller;

use Jack\EarningBundle\Controller\DefaultController;

use Jack\ImportBundle\Entity\Underlying;

class EstimateController extends DefaultController
{
    public function priceResultAction($symbol, $forward = 0, $backward = 0)
    {
        // init the function
        $this->initEarning($symbol);


        $this->earningUnderlyings = $this->findUnderlyingByEarning($forward, $backward);

        // get the data from object array
        $priceEstimates = array();
        foreach ($this->earningUnderlyings as $dateKey => $earningUnderlying) {
            $earning = $earningUnderlying['earning'];

            $underlyings = $earningUnderlying['underlyings'];

            // get the value from object
            $startUnderlying = current($underlyings);
            $endUnderlying = end($underlyings);

            // start underlying object
            $start['last'] = $startUnderlying->getLast();
            $start['open'] = $startUnderlying->getOpen();
            $start['high'] = $startUnderlying->getHigh();
            $start['low'] = $startUnderlying->getLow();
            $start['volume'] = $startUnderlying->getVolume();

            // last underlying object
            $end['last'] = $endUnderlying->getLast();
            $end['open'] = $endUnderlying->getOpen();
            $end['high'] = $endUnderlying->getHigh();
            $end['low'] = $endUnderlying->getLow();
            $end['volume'] = $endUnderlying->getVolume();

            // calculation start here
            // last section
            $last = $this->calculatePriceMovement(
                $start['last'], $end['last'], $end['open'], $end['high'], $end['low']
            );

            // open section
            $open = $this->calculatePriceMovement(
                $start['open'], $end['last'], $end['open'], $end['high'], $end['low']
            );

            // high section
            $high = $this->calculatePriceMovement(
                $start['high'], $end['last'], $end['open'], $end['high'], $end['low']
            );

            // low section
            $low = $this->calculatePriceMovement(
                $start['low'], $end['last'], $end['open'], $end['high'], $end['low']
            );

            // volume section
            $volume['volume']['value'] = $end['volume'] - $start['volume'];
            $volume['volume']['percentage'] =
                $volume['volume']['value'] / $start['volume'];

            // total average calculation
            $average = $this->calculateAverageMovement($start['last'], $last, $open, $high, $low);

            // put it into array
            $priceEstimates[$dateKey] = array(
                'earning' => $earning,
                'last' => $last,
                'open' => $open,
                'high' => $high,
                'low' => $low,
                'average' => $average,
                'volume' => $volume
            );

            // save memory
            unset($last, $open, $high, $low, $volume);
        }

        // summary calculation
        $summaryReport = array(
            'upDown' => $this->calculateSummary($priceEstimates, 0),
            '1percent' => $this->calculateSummary($priceEstimates, 0.01),
            '2percent' => $this->calculateSummary($priceEstimates, 0.02),
            '3percent' => $this->calculateSummary($priceEstimates, 0.03),
            '5percent' => $this->calculateSummary($priceEstimates, 0.05),
            '10percent' => $this->calculateSummary($priceEstimates, 0.1)
        );

        // todo: next max, min, best quarter, estimate vs actual summary


        return $this->render(
            'JackEarningBundle:Estimate:priceResult.html.twig',
            array(
                'summaryReport' => $summaryReport,
                'priceEstimates' => $priceEstimates
            )
        );
    }

    public function calculateSummary($priceEstimates, $sideWayPercent, $base = 'last', $to = 'last')
    {
        $summaryReport = array();

        // declare variable
        $bullishCount = 0;
        $bearishCount = 0;
        $sideWayCount = 0;


        foreach ($priceEstimates as $priceEstimate) {
            $last['last']['percentage'] = floatval(
                number_format($priceEstimate[$base][$to]['percentage'], 2)
            );

            if ($last['last']['percentage'] >= $sideWayPercent) {
                $bullishCount++;
            } elseif ($last['last']['percentage'] < -$sideWayPercent) {
                $bearishCount++;
            } else {
                $sideWayCount++;
            }
        }

        return array(
            'bullish' => $bullishCount,
            'sideWay' => $sideWayCount,
            'bearish' => $bearishCount,
        );
    }


    /**
     * @param $price
     * @param $last
     * @param $open
     * @param $high
     * @param $low
     * @return mixed
     */
    public function calculateAverageMovement($price, $last, $open, $high, $low)
    {
        $average['last']['value'] = ($last['last']['value'] + $open['last']['value']
                + $high['last']['value'] + $low['last']['value']) / 4;
        $average['open']['value'] = ($last['open']['value'] + $open['open']['value']
                + $high['open']['value'] + $low['open']['value']) / 4;
        $average['high']['value'] = ($last['high']['value'] + $open['high']['value']
                + $high['high']['value'] + $low['high']['value']) / 4;
        $average['low']['value'] = ($last['low']['value'] + $open['low']['value']
                + $high['low']['value'] + $low['low']['value']) / 4;

        $average['last']['percentage'] = ($last['last']['percentage'] + $open['last']['percentage']
                + $high['last']['percentage'] + $low['last']['percentage']) / 4;
        $average['open']['percentage'] = ($last['open']['percentage'] + $open['open']['percentage']
                + $high['open']['percentage'] + $low['open']['percentage']) / 4;
        $average['high']['percentage'] = ($last['high']['percentage'] + $open['high']['percentage']
                + $high['high']['percentage'] + $low['high']['percentage']) / 4;
        $average['low']['percentage'] = ($last['low']['percentage'] + $open['low']['percentage']
                + $high['low']['percentage'] + $low['low']['percentage']) / 4;

        // calculate average of all average!!
        $average['average']['value'] = ($average['last']['value'] + $average['open']['value'] +
                $average['high']['value'] + $average['low']['value']) / 4;
        $average['average']['percentage'] = ($average['last']['percentage'] + $average['open']['percentage'] +
                $average['high']['percentage'] + $average['low']['percentage']) / 4;

        $average['reverse']['value'] = '-';
        $average['reverse']['percentage'] = '-';


        return $average;
    }

    /**
     * @param $base
     * @param $last
     * @param $open
     * @param $high
     * @param $low
     * @return mixed
     */
    public function calculatePriceMovement($base, $last, $open, $high, $low)
    {
        $temp = array();

        // price section
        $temp['last']['value'] = $last - $base;
        $temp['open']['value'] = $open - $base;
        $temp['high']['value'] = $high - $base;
        $temp['low']['value'] = $low - $base;

        // percentage section
        $temp['last']['percentage'] = $temp['last']['value'] / $base;
        $temp['open']['percentage'] = $temp['open']['value'] / $base;
        $temp['high']['percentage'] = $temp['high']['value'] / $base;
        $temp['low']['percentage'] = $temp['low']['value'] / $base;

        // average section
        $temp['average']['value'] = ($temp['last']['value'] + $temp['open']['value'] +
                $temp['high']['value'] + $temp['low']['value']) / 4;

        $temp['average']['percentage'] = $temp['average']['value'] / $base;

        // reverse section
        $temp['reverse']['value'] = 0;

        if ($temp['open']['value'] >= 0) {
            if ($temp['last']['value'] < 0) {
                $temp['reverse']['value']++;
            }
            if ($temp['high']['value'] < 0) {
                $temp['reverse']['value']++;
            }
            if ($temp['low']['value'] < 0) {
                $temp['reverse']['value']++;
            }
        } elseif ($temp['open']['value'] < 0) {
            if ($temp['last']['value'] > 0) {
                $temp['reverse']['value']++;
            }
            if ($temp['high']['value'] > 0) {
                $temp['reverse']['value']++;
            }
            if ($temp['low']['value'] > 0) {
                $temp['reverse']['value']++;
            }
        }

        $temp['reverse']['percentage'] = $temp['reverse']['value'] / 3;

        return $temp;
    }


}
