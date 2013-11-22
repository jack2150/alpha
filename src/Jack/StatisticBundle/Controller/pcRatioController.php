<?php

namespace Jack\StatisticBundle\Controller;


use Jack\StatisticBundle\Controller\DefaultController;

use Symfony\Component\HttpFoundation\Request;

use Jack\ImportBundle\Entity\Pcratio;
use Jack\ImportBundle\Entity\Chain;
use Jack\ImportBundle\Entity\Underlying;

/**
 * Class pcRatioController
 * @package Jack\StatisticBundle\Controller
 */
class pcRatioController extends DefaultController
{
    protected static $maxPcRatioInsert = 50;

    protected $pcRatios;

    /**
     * @param $symbol
     */
    public function initPcRatio($symbol)
    {
        // init the data ready for use
        $this->init($symbol);

        // create new hv table if table not exists

        if (!$this->checkTableExist('pcratio')) {
            $this->createTable('Jack\ImportBundle\Entity\Pcratio');
        }

        // get the underlying result
        $this->underlyings = $this->findUnderlyingAll();

        // set pc ratio data
        $this->setPcRatios();
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
                    'jack_stat_pcratio_result', array('symbol' => strtolower($symbol))
                )
            );


        }

        // render page
        return $this->render(
            'JackStatisticBundle:pcRatio:index.html.twig',
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
        // display debug
        $debug = array();

        // start init
        $this->initPcRatio($symbol);

        // entity manager
        $symbolEM = $this->getDoctrine()->getManager('symbol');


        /*
         * use 1 underlying data only
         * get a list of cycle with underlying id
         * loop calculate volume for put and call
         * finish with calculate pc ratio
         * save db
         */
        $countPcRatio = self::$maxPcRatioInsert - 1;
        $repeatPage = 0;

        $countDebug = 0;
        foreach ($this->underlyings as $underlying) {
            // error checking
            if (!($underlying instanceof Underlying)) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager!'
                );
            }

            // check underlying id is already added
            if (!($this->checkPcRatioExist($underlying->getId()))) {
                $chains = $this->findChainByIds($underlying->getId());

                // loop calculate volume for put and call
                $putVolume = 0;
                $callVolume = 0;
                foreach ($chains as $key => $chain) {
                    // error checking
                    if (!($chain instanceof Chain)) {
                        throw $this->createNotFoundException(
                            'Error [ Underlying ] object from entity manager!'
                        );
                    }

                    if ($chain->getStrikeid()->getCategory() == 'CALL') {
                        $callVolume += intval($chain->getVolume());
                    }

                    if ($chain->getStrikeid()->getCategory() == 'PUT') {
                        $putVolume += intval($chain->getVolume());
                    }

                    unset($chain);
                }

                unset($chains);

                // calculate pc ratio
                // divide by zero error
                if (!$putVolume || !$callVolume) {
                    $pcRatioValue = 0;
                } else {
                    $pcRatioValue = number_format($putVolume / $callVolume, 6);
                }

                // create new object and save data into object
                $pcRatioObject = new Pcratio();

                $pcRatioObject->setPutvolume($putVolume);
                $pcRatioObject->setCallvolume($callVolume);
                $pcRatioObject->setValue($pcRatioValue);
                $pcRatioObject->setUnderlyingid($underlying);

                // add into table
                $symbolEM->persist($pcRatioObject);

                // debug use only
                $countDebug++;
                $debug[] = array(
                    'count' => $countDebug,
                    'data' => "Put Volume: $putVolume, Call Volume: $callVolume, Put/Call Ratio: " .
                    number_format($pcRatioValue * 100, 2, '.', ',') . "% ",
                    'result' => 1
                );

                // unset data
                unset($putVolume, $callVolume, $pcRatioValue, $pcRatioObject);

                // out loop if max insert exist
                if ($countPcRatio) {
                    $countPcRatio--;
                } else {
                    // exit loop
                    $repeatPage = 1;
                    break;
                }

            }
        }

        // save into database
        $symbolEM->flush();

        // if remain data
        $importUrl = 0;
        if ($repeatPage) {
            $importUrl = $this->generateUrl(
                'jack_stat_pcratio_result', array('symbol' => $symbol)
            );
        }

        // render page
        return $this->render(
            'JackStatisticBundle:pcRatio:result.html.twig',
            array(
                'symbol' => $symbol,
                'import_url' => $importUrl,
                'debugResults' => $debug
            )
        );

    }

    /**
     * @param $underlyingId
     * @return int
     */
    public function checkPcRatioExist($underlyingId)
    {
        $found = 0;

        if (isset($this->pcRatios[$underlyingId])) {
            $found = 1;
        }

        return $found;
    }

    public function setPcRatios()
    {
        $pcRatios = $this->findPcRatioAll();

        // replace array with underlying id
        $newPcRatioArray = array();
        foreach ($pcRatios as $pcRatio) {
            // error checking
            if (!($pcRatio instanceof Pcratio)) {
                throw $this->createNotFoundException(
                    'Error [ PcRatio ] object from entity manager!'
                );
            }

            $newPcRatioArray[$pcRatio->getUnderlyingid()->getId()] = $pcRatio;
        }

        // sort the array
        ksort($newPcRatioArray);

        // save into variable
        $this->pcRatios = $newPcRatioArray;
    }


}
