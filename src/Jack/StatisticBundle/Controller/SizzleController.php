<?php

namespace Jack\StatisticBundle\Controller;

use Jack\StatisticBundle\Controller\DefaultController;
use Symfony\Component\HttpFoundation\Request;

use Jack\ImportBundle\Entity\Pcratio;
use Jack\ImportBundle\Entity\Underlying;
use Jack\ImportBundle\Entity\Sizzle;


/**
 * Class SizzleController
 * @package Jack\StatisticBundle\Controller
 */
class SizzleController extends DefaultController
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

    protected $pcRatios;
    protected $sizzles;

    private $pcRatioDateArray;

    /**
     * @param $symbol
     */
    public function initSizzle($symbol)
    {
        // init the data ready for use
        $this->init($symbol);

        // create new hv table if table not exists

        if (!$this->checkTableExist('sizzle')) {
            $this->createTable('Jack\ImportBundle\Entity\Sizzle');
        }

        // get the underlying result
        $this->underlyings = $this->findUnderlyingAll();

        // set underlying date key
        $this->setDatePcRatios();

        // set past sizzle data
        $this->setSizzles();
    }

    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $generatePcRatioData = array(
            'symbol' => null
        );

        $generatePcRatioForm = $this->createFormBuilder($generatePcRatioData)
            ->add('symbol', 'choice', array(
                'choices' => $this->getSymbolArray(),
                'required' => true,
                'multiple' => false,
            ))
            ->add('generate', 'submit')
            ->getForm();

        $generatePcRatioForm->handleRequest($request);

        if ($generatePcRatioForm->isValid()) {
            $generatePcRatioData = $generatePcRatioForm->getData();

            $symbol = $generatePcRatioData['symbol'];

            return $this->redirect(
                $this->generateUrl(
                    'jack_stat_sizzle_result', array('symbol' => strtolower($symbol))
                )
            );
        }

        // render page
        return $this->render(
            'JackStatisticBundle:Sizzle:index.html.twig',
            array(
                'generatePcRatioForm' => $generatePcRatioForm->createView(),
            )
        );
    }


    /**
     * @param $symbol
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function resultAction($symbol)
    {
        // debug use only
        $debug = array();

        // init the data
        $this->initSizzle($symbol);

        // entity manager
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        // set sample size
        $sampleSize = self::$sampleSize['oneMonth'];

        /*
         * get all data from pc ratio
         * get all data from underlying using date
         * using 1 underlying date with 5 pc ratio
         * calculate the 5 day average volume
         * calculate the today volume
         * get the sizzle index (call, put and index)
         */
        $countDebug = 0;
        foreach ($this->underlyings as $underlying) {
            // error control
            if (!($underlying instanceof Underlying)) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager!'
                );
            }

            // check sizzle exist
            if (!($this->checkSizzleExist($underlying->getId()))) {
                // get current date
                $currentDate = $underlying->getDate()->format('Y-m-d');

                // find the current date key position in array
                $currentPosition = array_search($currentDate, $this->pcRatioDateArray);
                $startPosition = $currentPosition - $sampleSize;

                if ($startPosition > -1) {
                    // get today volume
                    $currentPutVolume = $this->pcRatios[$currentDate]->getPutvolume();
                    $currentCallVolume = $this->pcRatios[$currentDate]->getCallvolume();
                    $currentOptionVolume = $currentPutVolume + $currentCallVolume;

                    // make a new sample size array
                    $pastSampleArray = array_slice($this->pcRatios, $startPosition, $sampleSize);

                    // start the loop calculation
                    $sumPutVolume = 0;
                    $sumCallVolume = 0;
                    foreach ($pastSampleArray as $PcRatio) {
                        // error checking
                        if (!($PcRatio instanceof Pcratio)) {
                            throw $this->createNotFoundException(
                                'Error [ PcRatio ] object from entity manager!'
                            );
                        }

                        // calculate the total
                        $sumPutVolume += $PcRatio->getPutvolume();
                        $sumCallVolume += $PcRatio->getCallvolume();
                    }

                    // calculate total volume
                    $sumOptionVolume = intval($sumPutVolume + $sumCallVolume);

                    // zero checking
                    if ($sumOptionVolume && $currentOptionVolume) {
                        // calculate average for put, call, both
                        $averagePutVolume = $sumPutVolume / $sampleSize;
                        $averageCallVolume = $sumCallVolume / $sampleSize;
                        $averageOptionVolume = $sumOptionVolume / $sampleSize;

                        // calculate sizzle for put, call, both
                        $putSizzleIndex = $averagePutVolume / $currentPutVolume;
                        $callSizzleIndex = $averageCallVolume / $currentCallVolume;
                        $optionSizzleIndex = $averageOptionVolume / $currentOptionVolume;

                        // format result
                        $putSizzleIndex = floatval(number_format($putSizzleIndex, 4));
                        $callSizzleIndex = floatval(number_format($callSizzleIndex, 4));
                        $optionSizzleIndex = floatval(number_format($optionSizzleIndex, 4));
                    } else {
                        $putSizzleIndex = 0;
                        $callSizzleIndex = 0;
                        $optionSizzleIndex = 0;
                    }

                    // save into db
                    $sizzle = new Sizzle();

                    $sizzle->setSample($sampleSize);
                    $sizzle->setPutindex($putSizzleIndex);
                    $sizzle->setCallindex($callSizzleIndex);
                    $sizzle->setValue($optionSizzleIndex);
                    $sizzle->setUnderlyingid($underlying);

                    $symbolEM->persist($sizzle);

                    // debug use only
                    $countDebug++;
                    $debug[] = array(
                        'count' => $countDebug,
                        'data' => "Date: $currentDate, PutVol: $currentPutVolume, " .
                        " CallVol: $currentCallVolume, TotalVol: $currentOptionVolume " .
                        " PutSizzle: $putSizzleIndex, CallSizzle: $callSizzleIndex " .
                        " SizzleIndex: $optionSizzleIndex",
                        'result' => 1
                    );

                    unset($sizzle, $putSizzleIndex, $callSizzleIndex, $optionSizzleIndex);
                } else {
                    // not enough sample
                    // debug use only
                    $countDebug++;
                    $debug[] = array(
                        'count' => $countDebug,
                        'data' => "Date: $currentDate ... ",
                        'result' => 0
                    );
                }
            }
        }

        // save into db
        $symbolEM->flush();

        return $this->render(
            'JackStatisticBundle:Sizzle:result.html.twig',
            array(
                'symbol' => $symbol,
                'debugResults' => $debug
            )
        );
    }

    /**
     * @param $underlyingId
     * @return int
     * true if exist, false if not exist
     */
    public function checkSizzleExist($underlyingId)
    {
        $found = 0;

        if (isset($this->sizzles[$underlyingId])) {
            $found = 1;
        }

        return $found;
    }

    public function setSizzles()
    {
        $sizzles = $this->findSizzleAll();

        $newSizzles = array();
        foreach ($sizzles as $sizzle) {
            // error checking
            if (!($sizzle instanceof Sizzle)) {
                throw $this->createNotFoundException(
                    'Error [ Sizzle ] object from entity manager!'
                );
            }

            $newSizzles[$sizzle->getUnderlyingid()->getId()] = $sizzle;
        }

        $this->sizzles = $newSizzles;
    }


    /**
     * @return mixed
     * a list of sizzle objects
     */
    public function findSizzleAll()
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Sizzle')
            ->findAll();

    }


    public function setDatePcRatios()
    {
        $pcRatios = $this->findPcRatioAll();

        // replace array with underlying date as key
        $newPcRatioArray = array();
        foreach ($pcRatios as $pcRatio) {
            // error checking
            if (!($pcRatio instanceof Pcratio)) {
                throw $this->createNotFoundException(
                    'Error [ PcRatio ] object from entity manager!'
                );
            }

            $newPcRatioArray[$pcRatio->getUnderlyingid()->getDate()->format('Y-m-d')] = $pcRatio;
        }

        $DateKeyArray = array_keys($newPcRatioArray);

        // sort the array using key
        sort($DateKeyArray);

        // sort the new pc ratio array with date
        $sortPcRatioArray = array();
        foreach ($DateKeyArray as $dateKey) {
            $sortPcRatioArray[$dateKey] = $newPcRatioArray[$dateKey];
        }

        //unset($newPcRatioArray, $DateKeyArray);

        // save into variable
        $this->pcRatioDateArray = $DateKeyArray;
        $this->pcRatios = $sortPcRatioArray;
    }


}
