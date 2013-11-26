<?php

namespace Jack\EarningBundle\Controller;

use Jack\EarningBundle\Controller\DefaultController;

use Jack\ImportBundle\Entity\Underlying;

class EstimateController extends DefaultController
{
    protected $priceEstimates;


    /**
     * @param $symbol
     * @param string $from
     * @param string $to
     * @param int $forward
     * @param int $backward
     * @param string $format
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function priceResultAction(
        $symbol, $from = 'last', $to = 'last', $forward = 0, $backward = 0, $format = 'medium'
    )
    {
        // init the function
        $this->initEarning($symbol);

        // validate data
        list($from, $to, $forward, $backward, $format) =
            $this->validateSelectData($from, $to, $forward, $backward, $format);


        switch ($format) {
            case 'smallest':
                $sideWayRange = array(0, 0.1, 0.25, 0.5, 0.75, 1);
                break;
            case 'smaller':
                $sideWayRange = array(0, 0.25, 0.5, 1, 1.5, 2);
                break;
            case 'small':
                $sideWayRange = array(0, 0.5, 1, 2, 3, 5);
                break;
            case 'large':
                $sideWayRange = array(0, 3, 6, 9, 12);
                break;
            case 'larger':
                $sideWayRange = array(0, 2.5, 5, 10, 15, 20);
                break;
            case 'largest':
                $sideWayRange = array(0, 3.75, 7.5, 15, 22.5, 30);
                break;
            case 'medium':
            default:
                $sideWayRange = array(0, 1.25, 2.5, 5, 7.5, 10);
        }

        // get underlying date using earning
        $this->earningUnderlyings = $this->findUnderlyingByEarning($forward, $backward);

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

        // todo: next max, min, best quarter, estimate vs actual summary
        // range between 1%~10% or above


        return $this->render(
            'JackEarningBundle:Estimate:priceResult.html.twig',
            array(
                'summaryForm' => $this->createSummaryForm($from, $to, $forward, $backward, $format),

                'fromTiming' => $from,
                'toTiming' => $to,

                'forward' => $forward,
                'backward' => $backward,

                'endDate' => current($dateArray),
                'startDate' => end($dateArray),

                'summaryMax' => $max,
                'summaryMin' => $min,
                'summaryAverage' => $average,
                'summaryReport' => $summaryReport,

                'priceEstimates' => $this->priceEstimates
            )
        );
    }

    public function validateSelectData($from, $to, $forward, $backward, $format)
    {
        // validate from
        $from = strtolower($from);
        if ($from != 'last' && $from != 'open' &&
            $from != 'high' && $from != 'low'
        ) {
            $from = 'last';
        }

        // validate to
        $to = strtolower($to);
        if ($to != 'last' && $to != 'open' &&
            $to != 'high' && $to != 'low'
        ) {
            $to = 'last';
        }

        // validate forward and backward
        $forward = intval($forward);
        if (!is_int($forward)) {
            $forward = 0;
        }

        $backward = intval($backward);
        if (!is_int($backward)) {
            $backward = 0;
        }

        // validate format
        $format = strtolower($format);
        if ($format != 'smallest' && $format != 'smaller' &&
            $format != 'small' && $format != 'medium' &&
            $format != 'large' && $format != 'larger' &&
            $format != 'largest'
        ) {
            $format = 'medium';
        }

        return array(
            0 => $from,
            1 => $to,
            2 => $forward,
            3 => $backward,
            4 => $format
        );


    }

    public function resultRedirectAction()
    {
        $formData = $this->getRequest()->get('form');

        // either last, open, high, low
        if ($formData['from'] != 'last' || $formData['from'] != 'open' ||
            $formData['from'] != 'high' || $formData['from'] != 'low'
        ) {
            $from = 'last';
        } else {
            $from = $formData['from'];
        }

        $from = $formData['from'];
        $to = $formData['to'];

        // integer only
        $forward = $formData['forward'];
        $backward = $formData['backward'];

        // smallest to largest
        $format = $formData['format'];

        // must exist
        $symbol = $formData['symbol'];

        return $this->redirect(
            $this->generateUrl('jack_earning_estimate_price_result',
                array(
                    'symbol' => $symbol,
                    'from' => $from,
                    'to' => $to,
                    'forward' => $forward,
                    'backward' => $backward,
                    'format' => $format
                )
            ));

    }


    public function createSummaryForm(
        $from = 'last', $to = 'last', $forward = 0, $backward = 0, $format = 'medium'
    )
    {
        $summaryFormData = array(
            'from' => $from,
            'to' => $to,
            'forward' => $forward,
            'backward' => $backward,
            'format' => $format,
            'symbol' => $this->symbol
        );

        $summarySelectForm = $this->createFormBuilder($summaryFormData)
            ->setAction($this->generateUrl(
                'jack_earning_estimate_price_redirect'
            ))
            ->add('from', 'choice', array(
                'choices' => array(
                    'last' => 'Close Price',
                    'open' => 'Open Price',
                    'high' => 'Day High',
                    'low' => 'Day Low',
                ),
                'required' => true,
                'multiple' => false,
            ))
            ->add('to', 'choice', array(
                'choices' => array(
                    'last' => 'Close Price',
                    'open' => 'Open Price',
                    'high' => 'Day High',
                    'low' => 'Day Low',
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
                    'smallest' => 'SIDEWAY RANGE: 0.0%-0.1%-0.25%-0.5%-0.75%-1.0%',
                    'smaller' => 'SIDEWAY RANGE: 0.0%-0.25%-0.5%-1.0%-1.5%-2.0%',
                    'small' => 'SIDEWAY RANGE: 0.0%-0.5%-1.0%-2.0%-3.0%-5.0%',
                    'medium' => 'SIDEWAY RANGE: 0.0%-1.25%-2.5%-5.0%-7.5%-10.0%',
                    'large' => 'SIDEWAY RANGE: 0.0%-1.5%-3.0%-6.0%-9.0%-12.0%',
                    'larger' => 'SIDEWAY RANGE: 0.0%-2.5%-5.0%-10.0%-15.0%-20.0%',
                    'largest' => 'SIDEWAY RANGE: 0.0%-3.75%-7.5%-15.0%-22.5%-30.0%',
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
     * @param $str
     * either 'forward' or 'backward'
     * @param int $maxDay
     * max day for selection
     * @return array
     * return a list of form selection
     */
    private function createSelectDayArray($str, $maxDay = 120)
    {
        $dayArray = array();

        $dayArray[] = "Earning Day";
        for ($day = 1; $day <= $maxDay; $day++) {
            $dayArray[] = "$day Day $str";
        }

        return $dayArray;
    }

    public function getMinMaxAverage($base = 'last', $to = 'last')
    {
        $percentageList = array();
        foreach ($this->priceEstimates as $priceEstimate) {
            $percentageValue = floatval(
                number_format($priceEstimate[$base][$to]['percentage'], 3)
            );

            $percentageList[] = $percentageValue;
        }

        $maxPercentage = 0;
        $minPercentage = 0;
        $averagePercentage = 0;

        $countList = count($percentageList);
        if ($countList) {
            $maxPercentage = max($percentageList);
            $minPercentage = min($percentageList);
            $averagePercentage = array_sum($percentageList) / $countList;
        }

        return array(
            0 => $maxPercentage,
            1 => $minPercentage,
            2 => $averagePercentage,
        );

    }

    public function getPriceEstimates()
    {
        // get the data from object array
        $dateArray = array();
        $priceEstimates = array();
        foreach ($this->earningUnderlyings as $dateKey => $earningUnderlying) {
            $earning = $earningUnderlying['earning'];

            $underlyings = $earningUnderlying['underlyings'];

            // get the value from object
            $startUnderlying = current($underlyings);
            $endUnderlying = end($underlyings);

            // create date array
            $date = array(
                'startDate' => $startUnderlying->getDate()->format('Y-m-d'),
                'endDate' => $endUnderlying->getDate()->format('Y-m-d'),
            );

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
                'date' => $date,
                'earning' => $earning,
                'last' => $last,
                'open' => $open,
                'high' => $high,
                'low' => $low,
                'average' => $average,
                'volume' => $volume
            );

            // create a dates array
            $dateArray[] = $dateKey;

            // save memory
            unset($last, $open, $high, $low, $volume);
        }

        return array(
            0 => $priceEstimates,
            1 => $dateArray
        );

    }

    public function calculateSummary($sideWayPercent, $base = 'last', $to = 'last')
    {
        // declare variable
        $bullishCount = 0;
        $bearishCount = 0;
        $sideWayCount = 0;

        $percentagePool = array(
            'bullish' => array(),
            'sideWay' => array(),
            'bearish' => array()
        );

        $percentageList = array();
        foreach ($this->priceEstimates as $priceEstimate) {
            $percentageValue = floatval(
                number_format($priceEstimate[$base][$to]['percentage'], 3)
            );

            // direction analysis
            if ($percentageValue >= $sideWayPercent) {
                $percentagePool['bullish'][] = $percentageValue;
                $bullishCount++;
            } elseif ($percentageValue < -$sideWayPercent) {
                $percentagePool['bearish'][] = $percentageValue;
                $bearishCount++;
            } else {
                $percentagePool['sideWay'][] = $percentageValue;
                $sideWayCount++;
            }

            // min, max, list
            $percentageList[] = $percentageValue;
        }

        if (!empty($percentagePool['sideWay'])) {
            sort($percentagePool['sideWay']);
        }
        if (!empty($percentagePool['bullish'])) {
            rsort($percentagePool['bullish']);
        }
        if (!empty($percentagePool['bearish'])) {
            rsort($percentagePool['bearish']);
        }

        return array(
            'bullish' => $bullishCount,
            'bullishPool' => $percentagePool['bullish'],
            'sideWay' => $sideWayCount,
            'sideWayPool' => $percentagePool['sideWay'],
            'bearish' => $bearishCount,
            'bearishPool' => $percentagePool['bearish']
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
