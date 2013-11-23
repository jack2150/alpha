<?php

namespace Jack\StatisticBundle\Controller;

use Jack\StatisticBundle\Controller\DefaultController;

use Symfony\Component\HttpFoundation\Request;

use Jack\ImportBundle\Entity\Underlying;
use Jack\ImportBundle\Entity\Vwap;

class VwapController extends DefaultController
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

    protected $underlyingDateArray;

    protected $vwaps;


    /**
     * @param $symbol
     * @param $sample
     */
    public function initVwap($symbol, $sample)
    {
        // init the data ready for use
        $this->init($symbol);

        // create new hv table if table not exists

        if (!$this->checkTableExist('vwap')) {
            $this->createTable('Jack\ImportBundle\Entity\Vwap');
        }

        // get the underlying result
        //$this->underlyings = $this->findUnderlyingAll();
        $this->setDateUnderlyings();

        // set vwaps
        $this->setVwaps($sample);
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $generateVwapData = array(
            'symbol' => null,
            'action' => 'value20d'

        );

        $generateVwapForm = $this->createFormBuilder($generateVwapData)
            ->add('symbol', 'choice', array(
                'choices' => $this->getSymbolArray(),
                'required' => true,
                'multiple' => false,
            ))
            ->add('sample', 'choice', array(
                'choices' => array(
                    '20' => 'Generate 20 Trading Days VWAP',
                    '30' => 'Generate 30 Trading Days VWAP',
                ),
                'required' => true,
                'multiple' => false,
            ))

            ->add('generate', 'submit')
            ->getForm();

        $generateVwapForm->handleRequest($request);

        if ($generateVwapForm->isValid()) {
            $generateVwapData = $generateVwapForm->getData();

            $symbol = $generateVwapData['symbol'];
            $sample = $generateVwapData['sample'];


            return $this->redirect(
                $this->generateUrl(
                    'jack_stat_vwap_result', array(
                        'sample' => $sample,
                        'symbol' => strtolower($symbol)
                    )
                )
            );
        }

        // render page
        return $this->render(
            'JackStatisticBundle:Vwap:index.html.twig',
            array(
                'generateVwapForm' => $generateVwapForm->createView(),
            )
        );
    }


    /**
     * @param $symbol
     * @param $sample
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function resultAction($symbol, $sample)
    {
        // debug use only
        $debug = array();

        // set sample size
        $sampleSize = $sample;
        //$sampleSize = 5;

        // start init
        $this->initVwap($symbol, $sampleSize);

        // entity manager
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        /*
         * use 1 date in underlying
         * get the past 20 days from the underlying
         * calculate sum of all volume
         * and sum of all volume * price
         * get the vwap
         */
        $countDebug = 0;
        foreach ($this->underlyings as $underlying) {
            $currentDate = $underlying->getDate()->format('Y-m-d');

            // todo: check exist with underlying id and sample size

            if (!$this->checkVwapExist($underlying->getId(), $sampleSize)) {
                $currentPosition = array_search($currentDate, $this->underlyingDateArray);
                $startPosition = $currentPosition - $sampleSize;

                if ($startPosition > -1) {
                    // reduce loop time, no result

                    $sumVolume = 0;
                    $sumVolumePrice = 0;
                    for ($i = 0; $i < $sampleSize; $i++) {
                        $position = $startPosition + $i;

                        $date = $this->underlyingDateArray[$position];

                        $volume = $this->underlyings[$date]->getVolume();
                        $lastPrice = $this->underlyings[$date]->getLast();

                        $sumVolume += $volume;
                        $sumVolumePrice += $volume * $lastPrice;
                    }
                    unset($volume, $lastPrice);

                    /*
                    $sampleArray = array_slice($this->underlyings, $startPosition, $sampleSize);

                    $sumVolume = 0;
                    $sumVolumePrice = 0;
                    foreach ($sampleArray as $underlyingObject)
                    {
                        // error checking
                        if (!($underlyingObject instanceof Underlying)) {
                            throw $this->createNotFoundException(
                                'Error [ Underlying ] object from entity manager!'
                            );
                        }

                        // sum the volume and volume * price
                        $sumVolume += $underlyingObject->getVolume();
                        $sumVolumePrice += $underlyingObject->getVolume() * $underlyingObject->getLast();

                        unset($underlyingObject);
                    }

                    unset($sampleArray, $underlyingObject);
                    */


                    // calculate the vwap
                    $vwapValue = $sumVolumePrice / $sumVolume;

                    // format the vwap, it is price
                    $vwapValue = floatval(number_format($vwapValue, 2));

                    $vwapObject = new Vwap();

                    $vwapObject->setSample($sampleSize);
                    $vwapObject->setValue($vwapValue);
                    $vwapObject->setUnderlyingid($underlying);

                    // add into query
                    $symbolEM->persist($vwapObject);

                    // debug use only
                    $countDebug++;
                    $debug [] = array(
                        'count' => $countDebug,
                        'data' => "Date: $currentDate" .
                        ", Sample: $sampleSize" .
                        ", Position: $currentPosition" .
                        ", Sum Volume: $sumVolume" .
                        ", Sum Vol*Price: $sumVolumePrice" .
                        ", VWAP Value: $vwapValue",
                        'result' => 1
                    );

                    unset($vwapObject, $currentDate, $currentPosition, $startPosition,
                    $sumVolume, $sumVolumePrice, $vwapValue);

                } else {
                    // not enough sample
                    // debug use only
                    $countDebug++;
                    $debug [] = array(
                        'count' => $countDebug,
                        'data' => "Date: $currentDate, Start Position: $startPosition ...",
                        'result' => 0
                    );
                }


            }
        }

        // save into db
        $symbolEM->flush();


        return $this->render(
            'JackStatisticBundle:Vwap:result.html.twig',
            array(
                'symbol' => $symbol,
                'debugResults' => $debug
            )
        );
    }

    public function setDateUnderlyings()
    {
        $underlyings = $this->findUnderlyingAll();

        $dateUnderlyings = array();
        foreach ($underlyings as $underlying) {
            if (!($underlying instanceof Underlying)) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager!'
                );
            }

            $dateUnderlyings[$underlying->getDate()->format('Y-m-d')] = $underlying;
        }

        $this->underlyings = $dateUnderlyings;
        $this->underlyingDateArray = array_keys($dateUnderlyings);
    }

    /**
     * @param $underlyingId
     * @param $sample
     * @return int
     */
    public function checkVwapExist($underlyingId, $sample)
    {
        $found = 0;

        $searchKey = "$underlyingId-$sample";
        if (isset($this->vwaps[$searchKey])) {
            $found = 1;
        }

        return $found;
    }

    /**
     * @param $sample
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function setVwaps($sample)
    {
        $vwaps = $this->findVwapBySample($sample);

        $newVwaps = array();
        foreach ($vwaps as $vwap) {
            if (!($vwap instanceof Vwap)) {
                throw $this->createNotFoundException(
                    'Error [ VWAP ] object from entity manager!'
                );
            }

            $newVwaps[$vwap->getUnderlyingid()->getId() . '-' . $sample] = $vwap;
        }

        $this->vwaps = $newVwaps;
    }

    public function findVwapBySample($sample)
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Vwap')
            ->findBy(array('sample' => $sample));
    }


}
