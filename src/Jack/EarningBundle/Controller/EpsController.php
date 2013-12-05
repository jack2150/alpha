<?php

namespace Jack\EarningBundle\Controller;

use Jack\ImportBundle\Entity\Earning;

/**
 * Class EpsController
 * @package Jack\EarningBundle\Controller
 */
class EpsController extends EstimateController
{
    protected $rangeSection;


    public function resultAction(
        $symbol, $enter = 'last', $exit = 'last', $forward = 0, $backward = 0, $format = 'medium'
    )
    {
        // init the function
        $this->initEarning($symbol);

        // set range
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


        // calculate eps report
        $epsReports = array(
            strval($this->rangeSection[0]) => $this->getEpsResult($this->rangeSection[0], $enter, $exit),
            strval($this->rangeSection[1]) => $this->getEpsResult($this->rangeSection[1] / 100, $enter, $exit),
            strval($this->rangeSection[2]) => $this->getEpsResult($this->rangeSection[2] / 100, $enter, $exit),
            strval($this->rangeSection[3]) => $this->getEpsResult($this->rangeSection[3] / 100, $enter, $exit),
            strval($this->rangeSection[4]) => $this->getEpsResult($this->rangeSection[4] / 100, $enter, $exit),
            strval($this->rangeSection[5]) => $this->getEpsResult($this->rangeSection[5] / 100, $enter, $exit)
        );

        return $this->render(
            'JackEarningBundle:Eps:result.html.twig',
            array
            (
                'symbol' => $symbol,

                'enter' => $enter,
                'exit' => $exit,
                'backward' => $backward,
                'forward' => $forward,

                'epsReports' => $epsReports,

                'form' => $this->createSelectForm(
                    $enter, $exit, $forward, $backward, $format
                )
            )
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
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
            $this->generateUrl('jack_earning_eps_result',
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

    public function getEpsResult($sidewayFormat = 0.05, $enter = 'last', $exit = 'last')
    {
        // declare period ending value
        $result = array(
            'count' => 0,
            'percent' => 0,

            'average' => 0,
            'daily' => 0,

            'edge' => 0,

            'pool' => array(),
        );

        $movement = array(
            'bullish' => $result,
            'sideway' => $result,
            'bearish' => $result,
        );

        // actual eps above estimate or
        // actual eps below estimate
        $eps = array(
            'above' => $movement,
            'below' => $movement
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

            // get actual and estimate eps
            $estimateEps = $earning->getEstimate();
            $actualEps = $earning->getActual();

            // get price movement
            $movePercent = $priceEstimate[$enter][$exit]['percentage'];

            // compare eps
            if ($actualEps >= $estimateEps) {
                $epsKey = 'above';
            } else {
                $epsKey = 'below';
            }

            if ($movePercent >= $sidewayFormat) {
                // bullish
                $eps[$epsKey]['bullish']['count']++;
                $eps[$epsKey]['bullish']['pool'][] = $priceEstimate;
                $eps[$epsKey]['bullish']['average'] += $movePercent;
            } elseif ($movePercent <= -$sidewayFormat) {
                // bearish
                $eps[$epsKey]['bearish']['count']++;
                $eps[$epsKey]['bearish']['pool'][] = $priceEstimate;
                $eps[$epsKey]['bearish']['average'] += $movePercent;
            } else {
                // sideway
                $eps[$epsKey]['sideway']['count']++;
                $eps[$epsKey]['sideway']['pool'][] = $priceEstimate;
                $eps[$epsKey]['sideway']['average'] += $movePercent;
            }

            // save memory
            unset($earning, $priceEstimate, $epsKey, $estimateEps, $actualEps, $movePercent);

        }

        // calculate average, percent, edge, and daily for each quarter
        foreach ($eps as $epsKey => $epsData) {
            $earningCount = $epsData['bullish']['count'] +
                $epsData['bearish']['count'] +
                $epsData['sideway']['count'];

            foreach ($epsData as $movement => $result) {
                // set movement count percentage
                if ($earningCount) {
                    $eps[$epsKey][$movement]['percent'] =
                        floatval(number_format($result['count'] / $earningCount, 4));
                }

                // set movement average price percent
                if ($result['count']) {
                    $eps[$epsKey][$movement]['average'] =
                        floatval(number_format($result['average'] / $result['count'], 4));
                } else {
                    $eps[$epsKey][$movement]['average'] = 0;
                }

                // set movement edge
                $eps[$epsKey][$movement]['edge'] =
                    $eps[$epsKey][$movement]['percent'] *
                    $eps[$epsKey][$movement]['average'];

                // set movement daily
                if ($totalDays) {
                    $eps[$epsKey][$movement]['daily'] =
                        floatval(number_format($eps[$epsKey][$movement]['average'] / $totalDays, 6));
                } else {
                    $eps[$epsKey][$movement]['daily'] = 0;
                }

            }

            // save memory
            unset($epsData, $epsKey, $result, $movement, $epsKey);

        }

        return $eps;
    }

    /**
     * @param string $enter
     * @param string $exit
     * @param int $forward
     * @param int $backward
     * @param string $format
     * @return \Symfony\Component\Form\FormView
     */
    public function createSelectForm(
        $enter = 'last', $exit = 'last', $forward = 0, $backward = 0, $format = 'medium'
    )
    {
        $formData = array(
            'enter' => $enter,
            'exit' => $exit,
            'forward' => $forward,
            'backward' => $backward,
            'format' => $format,
            'symbol' => $this->symbol
        );

        $form = $this->createFormBuilder($formData)
            ->setAction($this->generateUrl(
                'jack_earning_eps_redirect'
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


        return $form->createView();
    }


}
