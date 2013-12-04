<?php

namespace Jack\EarningBundle\Controller;

use Jack\ImportBundle\Entity\Earning;

class QuarterController extends SweetSpotController
{
    protected $rangeSection;


    /**
     * @param $symbol
     * @param string $enter
     * @param string $exit
     * @param int $forward
     * @param int $backward
     * @param string $format
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resultAction(
        $symbol, $enter = 'last', $exit = 'last', $forward = 0, $backward = 0, $format = 'medium'
    )
    {
        // init the function
        $this->initEarning($symbol);

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

        // get underlying date using earning
        $this->earningUnderlyings = $this->findUnderlyingByEarning2($forward, $backward);

        // get price estimates for all
        list($this->priceEstimates, $dateArray) = $this->getPriceEstimates();


        // summary calculation
        $quarterReports = array(
            strval($this->rangeSection[0]) => $this->getQuarterResult($this->rangeSection[0], $enter, $exit),
            strval($this->rangeSection[1]) => $this->getQuarterResult($this->rangeSection[1] / 100, $enter, $exit),
            strval($this->rangeSection[2]) => $this->getQuarterResult($this->rangeSection[2] / 100, $enter, $exit),
            strval($this->rangeSection[3]) => $this->getQuarterResult($this->rangeSection[3] / 100, $enter, $exit),
            strval($this->rangeSection[4]) => $this->getQuarterResult($this->rangeSection[4] / 100, $enter, $exit),
            strval($this->rangeSection[5]) => $this->getQuarterResult($this->rangeSection[5] / 100, $enter, $exit)
        );

        $a = 1;

        // todo: next display data, create form
        return $this->render(
            'JackEarningBundle:Quarter:result.html.twig',
            array
            (
                'symbol' => $symbol,

                'quarterReports' => $quarterReports,

                'quarterForm' => $this->createQuarterForm(
                    $enter, $exit, $forward, $backward, $format
                ),


            )
        );

    }

    public function redirectAction()
    {
        $formData = $this->getRequest()->get('form');

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
            $this->generateUrl('jack_earning_quarter_result',
                array(
                    'symbol' => $symbol,
                    'enter' => $enter,
                    'exit' => $exit,
                    'forward' => $forward,
                    'backward' => $backward,
                    'format' => $format
                )
            )
        );
    }

    /**
     * @param float $sidewayFormat
     * @param string $enter
     * @param string $exit
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getQuarterResult($sidewayFormat = 0.00, $enter = 'last', $exit = 'last')
    {
        // declare period ending value
        $result = array(
            'count' => 0,
            'average' => 0,
            'percent' => 0,
            'edge' => 0,
            'daily' => 0,

            'pool' => array(),
        );

        $movement = array(
            'bullish' => $result,
            'sideway' => $result,
            'bearish' => $result,
        );

        $quarters = array(
            'q1' => $movement,
            'q2' => $movement,
            'q3' => $movement,
            'q4' => $movement,
        );

        $totalDays = $this->countEarningDays();

        unset($movement, $result);

        foreach ($this->priceEstimates as $priceEstimate) {
            $earning = $priceEstimate['earning'];

            // error checking
            if (!($earning instanceof Earning)) {
                throw $this->createNotFoundException(
                    'Error [ Earning ] object from entity manager'
                );
            }

            // get period and percent for earning
            $periodEnding = $earning->getPeriodending();
            $movePercent = $priceEstimate[$enter][$exit]['percentage'];

            if ($movePercent >= $sidewayFormat) {
                // bullish
                $quarters[$periodEnding]['bullish']['count']++;
                $quarters[$periodEnding]['bullish']['pool'][] = $priceEstimate;
                $quarters[$periodEnding]['bullish']['average'] += $movePercent;
            } elseif ($movePercent <= -$sidewayFormat) {
                // bearish
                $quarters[$periodEnding]['bearish']['count']++;
                $quarters[$periodEnding]['bearish']['pool'][] = $priceEstimate;
                $quarters[$periodEnding]['bearish']['average'] += $movePercent;
            } else {
                // sideway
                $quarters[$periodEnding]['sideway']['count']++;
                $quarters[$periodEnding]['sideway']['pool'][] = $priceEstimate;
                $quarters[$periodEnding]['sideway']['average'] += $movePercent;
            }

            // save memory
            unset($priceEstimate, $periodEnding, $movePercent);
        }

        // calculate average, percent, edge, and daily for each quarter
        foreach ($quarters as $season => $quarter) {
            $earningCount = $quarter['bullish']['count'] +
                $quarter['bearish']['count'] +
                $quarter['sideway']['count'];

            foreach ($quarter as $movement => $result) {
                // set movement count percentage
                if ($earningCount) {
                    $quarters[$season][$movement]['percent'] =
                        floatval(number_format($result['count'] / $earningCount, 4));
                }


                // set movement average price percent
                if ($result['count']) {
                    $quarters[$season][$movement]['average'] =
                        floatval(number_format($result['average'] / $result['count'], 4));
                }

                // set movement edge
                $quarters[$season][$movement]['edge'] =
                    $quarters[$season][$movement]['percent'] *
                    $quarters[$season][$movement]['average'];

                // set movement daily
                if ($totalDays) {
                    $quarters[$season][$movement]['daily'] =
                        floatval(number_format($quarters[$season][$movement]['average'] / $totalDays, 6));
                } else {
                    $quarters[$season][$movement]['daily'] = 0;
                }

            }

            // save memory
            unset($quarter, $result, $movement, $season);
        }

        return $quarters;
    }

    /**
     * @param string $enter
     * @param string $exit
     * @param int $forward
     * @param int $backward
     * @param string $format
     * @return \Symfony\Component\Form\FormView
     */
    public function createQuarterForm(
        $enter = 'last', $exit = 'last', $forward = 0, $backward = 0, $format = 'medium'
    )
    {
        $summaryFormData = array(
            'enter' => $enter,
            'exit' => $exit,
            'forward' => $forward,
            'backward' => $backward,
            'format' => $format,
            'symbol' => $this->symbol
        );

        $summarySelectForm = $this->createFormBuilder($summaryFormData)
            ->setAction($this->generateUrl(
                'jack_earning_quarter_redirect'
            ))
            ->add('enter', 'choice', array(
                'choices' => array(
                    'last' => 'Close Price',
                    'open' => 'Open Price',
                    'high' => 'Day High',
                    'low' => 'Day Low',
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
}
