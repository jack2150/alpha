<?php

namespace Jack\EarningBundle\Controller;

use Jack\FindBundle\Controller\FindController;
use Symfony\Component\HttpFoundation\Request;

use Jack\ImportBundle\Entity\Underlying;
use Jack\ImportBundle\Entity\Earning;
use Jack\ImportBundle\Entity\Holiday;
use Jack\ImportBundle\Entity\Symbol;

/**
 * Class DefaultController
 * @package Jack\EarningBundle\Controller
 */
class DefaultController extends FindController
{
    protected $earnings;
    protected $underlyings;
    protected $holidays;


    protected $earningUnderlyings;


    /**
     * @param $symbol
     */
    public function initEarning($symbol)
    {
        // init the data ready for use
        $this->init($symbol);

        //$this->earnings = $this->findEarningAll();
        $this->setEarningByDate();
    }


    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $earningFormData = array(
            'symbol' => null,
            'action' => '20'

        );

        $earningForm = $this->createFormBuilder($earningFormData)
            ->add('symbol', 'choice', array(
                'choices' => $this->getSymbolArray(),
                'required' => true,
                'multiple' => false,
            ))
            ->add('action', 'choice', array(
                'choices' => array(
                    'searchUnderlying' => 'Search Earning Underlying',
                    'estimatePriceMove' => 'Estimate Earning Before/After Price Movement',
                ),
                'required' => true,
                'multiple' => false,
            ))

            ->add('generate', 'submit')
            ->getForm();

        $earningForm->handleRequest($request);

        if ($earningForm->isValid()) {
            $earningFormData = $earningForm->getData();

            $symbol = $earningFormData['symbol'];
            $action = $earningFormData['action'];

            $returnUrl = 'jack_earning_default_index';
            $params = array();
            switch ($action) {
                case 'searchUnderlying':
                    $returnUrl = 'jack_earning_default_result';
                    $params = array(
                        'symbol' => strtolower($symbol)
                    );
                    break;
                case 'estimatePriceMove':
                    $returnUrl = 'jack_earning_estimate_price_result';
                    $params = array(
                        'symbol' => strtolower($symbol)
                    );
                    break;
            }

            return $this->redirect($this->generateUrl($returnUrl, $params));
        }

        return $this->render(
            'JackEarningBundle:Default:index.html.twig',
            array('earningForm' => $earningForm->createView())
        );
    }

    /**
     * @param $symbol
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resultAction($symbol)
    {
        // init the function
        $this->initEarning($symbol);

        // find underlying by earning
        $this->earningUnderlyings = $this->findUnderlyingByEarning();

        return $this->render(
            'JackEarningBundle:Default:result.html.twig',
            array(
                'earningUnderlyings' => $this->earningUnderlyings
            )
        );
    }

    /**
     * @param int $forward
     * @param int $backward
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function findUnderlyingByEarning($forward = 0, $backward = 0)
    {
        // save memory and process power model
        /*
         * 1. get all data from holiday and system-symbol
         * 2. generate a list of date without holiday and weekend
         * 3. search the date in position
         * 4. use the date and put into an search date array
         * 5. search in the db
         * 6. put it into correct object
         */

        $dateArray = $this->getValidDateArray();

        $earningExist = 0;
        if (count($this->earnings) > 0) {
            $earningExist = 1;
        }

        $newEarningUnderlying = array();
        if ($earningExist) {
            $earningDateArray = array();
            $findDateArray = array();
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
                $currentPosition = array_search($searchDate, $dateArray);
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

                $earningDateArray[$dateKey] = array_slice($dateArray, $startPosition, $arrayLength);

                $findDateArray = array_merge($findDateArray,
                    array_slice($dateArray, $startPosition, $arrayLength));

                // save memory
                unset($dateKey, $earning, $searchDate, $marketHour,
                $currentPosition, $startPosition, $arrayLength);
            }

            unset($dateArray);

            // find in underlying
            $underlyings = $this->getUnderlyingByDate($findDateArray);

            // put underlyings data into correct array


            foreach ($earningDateArray as $dateKey => $earningDates) {
                $underlyingData = null;
                foreach ($earningDates as $earningDate) {
                    $underlyingData[$earningDate] = $underlyings[$earningDate];
                }

                $newEarningUnderlying[$dateKey] = array(
                    'earning' => $this->earnings[$dateKey],
                    'underlyings' => $underlyingData
                );

                unset($underlyingData);
            }

            unset($underlyings);

            // reverse sort using key
            krsort($newEarningUnderlying);
        }


        return $newEarningUnderlying;
    }

    /**
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getValidDateArray()
    {
        // set holiday
        $this->setHoliday();

        // error checking
        if (!($this->symbolObject instanceof Symbol)) {
            throw $this->createNotFoundException(
                'Error [ Underlying ] object from entity manager'
            );
        }

        $firstDate = $this->symbolObject->getFirstdate()->format('Y-m-d');
        $lastDate = $this->symbolObject->getLastdate()->format('Y-m-d');

        $firstDate = new \DateTime($firstDate);
        $lastDate = new \DateTime($lastDate);

        $dayDiff = intval($firstDate->diff($lastDate)->format("%R%a"));

        $dateArray = array();
        for ($day = 0; $day < $dayDiff; $day++) {
            $useDay = 1;

            // create date object for test
            $currentDate = new \DateTime($firstDate->format('Y-m-d'));
            $currentDate = $currentDate->modify("+$day day");

            $currentDateStr = $currentDate->format('Y-m-d');

            // check not weekend
            $weekday = $currentDate->format('l');
            if ($weekday == 'Saturday' || $weekday == 'Sunday') {
                $useDay = 0;
            }

            // check not holiday
            if ($this->checkDateIsHoliday($currentDateStr)) {
                $useDay = 0;
            }

            // if ok, add into array
            if ($useDay) {
                $dateArray[] = $currentDateStr;
            }

            unset($currentDate, $useDay, $weekday, $currentDateStr);
        }

        // remove holiday because no longer use
        unset($this->holidays);

        return $dateArray;
    }


    /*
    public function findUnderlyingByEarning($forward = 0, $backward = 0)
    {
        $earningUnderlying = array();

        $underlyingDateArray = array_keys($this->underlyings);


        foreach ($this->earnings as $dateKey => $earning)
        {
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
            switch ($marketHour)
            {
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
            $earningUnderlying[$searchDate] = array_slice($this->underlyings, $startPosition, $arrayLength);
        }


        return $earningUnderlying;
    }
    */


    /**
     * @param $dateArray
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getUnderlyingByDate($dateArray)
    {
        $underlyings = $this->findUnderlyingByDate($dateArray);
        //$underlyings = $this->findUnderlyingAll();

        $newUnderlyings = array();
        foreach ($underlyings as $underlying) {
            // error checking
            if (!($underlying instanceof Underlying)) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager'
                );
            }

            // using date as key
            $dateKey = $underlying->getDate()->format('Y-m-d');
            $newUnderlyings[$dateKey] = $underlying;
        }
        unset($underlying);

        // sort again
        ksort($newUnderlyings);


        return $newUnderlyings;
    }

    public function setEarningByDate()
    {
        $earnings = $this->findEarningAll();

        // create a new earnings using underlying date
        $newEarnings = array();
        foreach ($earnings as $earning) {
            // error checking
            if (!($earning instanceof Earning)) {
                throw $this->createNotFoundException(
                    'Error [ Earning ] object from entity manager'
                );
            }

            // using date as key
            $dateKey = $earning->getEventid()->getUnderlyingid()->getDate()->format('Y-m-d');
            $newEarnings[$dateKey] = $earning;
        }

        // sort the array
        ksort($newEarnings);

        // set into class
        $this->earnings = $newEarnings;
    }

    public function checkDateIsHoliday($date)
    {
        $found = 0;

        if (isset($this->holidays[$date])) {
            $found = 1;
        }

        return $found;
    }


    public function setHoliday()
    {
        $holidays = $this->findHolidayAll();

        $newHolidays = array();
        foreach ($holidays as $holiday) {
            if (!($holiday instanceof Holiday)) {
                throw $this->createNotFoundException(
                    'Error [ Holiday ] object from entity manager'
                );
            }

            $newHolidays[$holiday->getDate()->format('Y-m-d')] = $holiday;
        }

        $this->holidays = $newHolidays;
    }

    public function findUnderlyingByDate($dateArray)
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Underlying')
            ->findBy(array('date' => $dateArray));
    }

    /**
     * @return array
     */
    public function findHolidayAll()
    {
        $symbolEM = $this->getDoctrine()->getManager('system');

        return $symbolEM
            ->getRepository('JackImportBundle:Holiday')
            ->findAll();
    }

    /**
     * @return array
     */
    public function findEarningAll()
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Earning')
            ->findAll();
    }

}
