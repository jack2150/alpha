<?php

namespace Jack\FindBundle\Controller;

use Jack\FindBundle\Controller\FindController;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Jack\ImportBundle\Entity\Symbol;
use Jack\ImportBundle\Entity\Underlying;

class UnderlyingController extends FindController
{
    /**
     * @param Request $request
     * return the request data from form, symbol and action
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * request post data
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error when form submit, validate data, and error object
     */
    public function indexAction(Request $request)
    {
        // create a form
        $findForm = array(
            'symbol' => '',
            'action' => '------'
        );
        $form = $this->createFormBuilder($findForm)
            ->add('symbol', 'choice', array(
                'choices' => $this->getSymbolArray(),
                'required' => true,
                'multiple' => false,
            ))
            ->add('action', 'choice', array(
                'choices' => array(
                    'findByDateRange' => 'Find By Date Range',
                    'findByCalendar' => 'Find By Calendar',
                    'findByWeek' => 'Find By Week',
                    'findByWeekday' => 'Find By Weekday',
                    'findByYear' => 'Find By Year',
                    'findByMonth' => 'Find By Month',
                    'findAll' => 'Find All',
                    'findByDay' => 'Find By Day',

                    // next for underlying


                    // TODO: remain find underlying type
                    //'findByDTE' => 'Find By DTE',
                    '1' => 'Find By Earnings',
                    '5' => 'Find By Date Range',
                    '6' => 'Find By IV',
                    '7' => 'Find By HV',
                    '8' => 'Find By IV Rank',
                ),
                'required' => true,
                'multiple' => false,
            ))
            ->add('find', 'submit')
            ->getForm();

        // validate form
        $form->handleRequest($request);

        if ($form->isValid()) {
            // redirect to underlying result form
            $formData = $form->getData();
            $symbol = $formData['symbol'];
            $action = $formData['action'];

            // switch find action
            switch ($action) {
                case 'findByDay':
                    $returnUrl = 'jack_find_underlying_result_findbyday';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower($action),
                        'day' => 1,
                    );
                    break;
                case 'findByMonth':
                    $returnUrl = 'jack_find_underlying_result_findbymonth';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower($action),
                        'month' => 1,
                    );
                    break;
                case 'findByYear':
                    $returnUrl = 'jack_find_underlying_result_findbyyear';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower($action),
                        'year' => date('Y'), // this year
                    );
                    break;
                case 'findByWeekday':
                    $returnUrl = 'jack_find_underlying_result_findbyweekday';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower($action),
                        'weekday' => strtolower(date("l")),
                    );
                    break;
                case 'findByWeek':
                    $returnUrl = 'jack_find_underlying_result_findbyweek';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower($action),
                        'week' => strtolower(date("W")),
                    );
                    break;
                case 'findByCalendar':
                    $returnUrl = 'jack_find_underlying_result_findbycalendar';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower($action),
                        'day' => 0,
                        'month' => date('m'),
                        'year' => 0,
                        'weekday' => 0,
                        'week' => 0,
                    );
                    break;
                case 'findByDateRange':
                    $returnUrl = 'jack_find_underlying_result_findbydaterange';

                    $this->getSymbolObject($symbol);

                    if (!($this->symbolObject instanceof Symbol)) {
                        throw $this->createNotFoundException(
                            'Error [ Symbol ] object from entity manager'
                        );
                    }

                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower($action),
                        'firstDate' => $this->symbolObject->getFirstdate()->format('Y-m-d'),
                        'lastDate' => $this->symbolObject->getLastdate()->format('Y-m-d'),
                    );
                    break;
                case 'findAll':
                default:
                    $returnUrl = 'jack_find_underlying_result_findall';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower($action),
                    );
                    break;
            }

            return $this->redirect(
                $this->generateUrl(
                    $returnUrl,
                    $params
                )
            );

        }

        return $this->render(
            'JackFindBundle:Underlying:index.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }


    public function resultAction
    ($symbol, $action, $day = 0, $month = 0, $year = 0, $weekday = 0, $week = 0,
     $firstDate = 0, $lastDate = 0)
    {
        // set core symbol
        $this->symbol = $symbol;
        $this->getSymbolObject($this->symbol);

        // switch db first
        $this->get('jack_service.fastdb')->switchSymbolDb($symbol);

        // select action to call function
        $linkType = 1;
        $searchName = "";
        $searchLinks = 0;
        $underlyings = Array();
        switch ($action) {
            case 'findall':
                $searchName = "Find All";
                $underlyings = $this->findUnderlyingAll('desc');
                break;
            case 'findbyday':
                $searchName = "Find By Day $day";
                $underlyings = $this->findUnderlyingByCalendar('day', $day, 'desc');
                $linkType = 'days';
                $searchLinks = $this->getListOfDay($day, 'findbyday');
                break;
            case 'findbymonth':
                $searchName = "Find By Month $month";
                $underlyings = $this->findUnderlyingByCalendar('month', $month, 'desc');
                $linkType = 'months';
                $searchLinks = $this->getListOfMonth($month, 'findbymonth');
                break;
            case 'findbyyear':
                $searchName = "Find By Year $year";
                $underlyings = $this->findUnderlyingByCalendar('year', $year, 'desc');
                $linkType = 'years';
                $searchLinks = $this->getListOfYear($year, 'findbyyear');
                break;
            case 'findbyweekday':
                $searchName = "Find By Weekday $weekday";
                $underlyings = $this->findUnderlyingByCalendar('weekday', $weekday, 'desc');
                $linkType = 'weekday';
                $searchLinks = $this->getListOfWeekday($weekday, 'findbyweekday');
                break;
            case 'findbyweek':
                $searchName = "Find By Week No. $week";
                $underlyings = $this->findUnderlyingByCalendar('week', $week, 'desc');
                $linkType = 'week';
                $searchLinks = $this->getListOfWeek($week, 'findbyweek');
                break;
            case 'findbycalendar':
                $searchName = "Find By Calendar";

                $underlyings = $this->findUnderlyingByMixCalendar(array(
                    'day' => $day,
                    'month' => $month,
                    'year' => $year,
                    'weekday' => $weekday,
                    'week' => $week,
                ), 'desc');
                $linkType = 'calendar';

                $dayLinks = $this->getListOfDay($day, 'findbyday');
                $monthLinks = $this->getListOfMonth($month, 'findbymonth');
                $yearLinks = $this->getListOfYear($year, 'findbyyear');
                $weekdayLinks = $this->getListOfWeekday($weekday, 'findbyweekday');
                $weekLinks = $this->getListOfWeek($week, 'findbyweek');
                $searchLinks = array(
                    'day' => $dayLinks,
                    'month' => $monthLinks,
                    'year' => $yearLinks,
                    'weekday' => $weekdayLinks,
                    'week' => $weekLinks,
                );
                break;
            case 'findbydaterange':
                $searchName = "Find By Date Range ";
                $underlyings = $this->findUnderlyingByDateRange($firstDate, $lastDate, 'desc');
                $linkType = 'daterange';
                $searchLinks = $this->getDateRangeForm($firstDate, $lastDate, 'desc');
                break;
        }

        // count underlying
        $resultCount = 0;
        if (!empty($underlyings)) {
            $resultCount = count($underlyings);
        }

        return $this->render(
            'JackFindBundle:Underlying:result.html.twig',
            array(
                'symbol' => $symbol,
                'searchName' => $searchName,
                'underlyings' => $underlyings,
                'resultCount' => $resultCount,
                'linkType' => $linkType,
                'searchLinks' => $searchLinks,
                'date' => array(
                    'day' => $day,
                    'month' => $month,
                    'year' => $year,
                    'weekday' => $weekday,
                    'week' => $week,
                )
            )
        );
    }


    /**
     * @param string $type
     * the search match type (day, month, year, weekday, week)
     * @param int $find
     * search exact day on every month
     * @param string $sort
     * result is sort by date
     * @return array
     * a list of array of underlying objects
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * database return error object
     */
    public function findUnderlyingByCalendar($type = 'day', $find = 1, $sort = 'asc')
    {
        date_default_timezone_set('UTC');


        $firstDate = $this->symbolObject->getFirstdate()->format('Y-m-d');
        $lastDate = $this->symbolObject->getLastdate()->format('Y-m-d');


        $firstDate = new \DateTime($firstDate);
        $lastDate = new \DateTime($lastDate);

        $dateDiff = intval($firstDate->diff($lastDate)->format("%R%a"));

        // generate a list of day 1
        $dayArray = array();
        $currentDate = $firstDate->format('Y-m-d');
        for ($day = 0; $day <= $dateDiff; $day++) {
            // switch for calendar (day, month, year, weekday, week number)
            switch ($type) {
                case 'month':
                    $currentMonth = Date("m", strtotime($currentDate . " +" . $day . " Day"));

                    if ($currentMonth == $find) {
                        $dayArray[] = Date("Y-m-d", strtotime($currentDate . " +" . $day . " Day"));
                    }
                    break;
                case 'year':
                    $currentYear = Date("Y", strtotime($currentDate . " +" . $day . " Day"));

                    if ($currentYear == $find) {
                        $dayArray[] = Date("Y-m-d", strtotime($currentDate . " +" . $day . " Day"));
                    }
                    break;
                    break;
                case 'weekday':
                    $currentWeekday = strtolower(
                        Date("l", strtotime($currentDate . " +" . $day . " Day"))
                    );

                    if ($currentWeekday != 'sunday' && $currentWeekday != 'saturday') {
                        if ($currentWeekday == $find) {
                            $dayArray[] = Date("Y-m-d", strtotime($currentDate . " +" . $day . " Day"));
                        }
                    }
                    break;
                case 'week':
                    $currentWeek = strtolower(
                        Date("W", strtotime($currentDate . " +" . $day . " Day"))
                    );

                    if ($currentWeek == $find) {
                        $dayArray[] = Date("Y-m-d", strtotime($currentDate . " +" . $day . " Day"));
                    }

                    break;
                case 'day':
                default:
                    $currentDay = Date("d", strtotime($currentDate . " +" . $day . " Day"));

                    if ($currentDay == $find) {
                        $dayArray[] = Date("Y-m-d", strtotime($currentDate . " +" . $day . " Day"));
                    }
                    break;
            }


        }

        $symbolEM = $this->getDoctrine()->getManager('symbol');

        if (count($dayArray)) {
            $underlyings = $symbolEM->getRepository('JackImportBundle:Underlying')
                ->findBy(array('date' => $dayArray), array('date' => $sort));
        } else {
            $underlyings = 0;
        }

        return $underlyings;
    }


    /**
     * @param array $find
     * an array include (day, month, year, weekday, week)
     * to correct search underlying
     * @param string $sort
     * sort is either 'asc' or 'desc'
     * @return array|int
     * return a list of underlyings data
     * or 0 if nothing is found
     */
    public function findUnderlyingByMixCalendar(
        $find = array('day' => 0, 'month' => 0, 'year' => 0, 'weekday' => 0, 'week' => 0),
        $sort = 'asc')
    {
        date_default_timezone_set('UTC');

        // past the find data into use
        $findDay = $find['day'];
        $findMonth = $find['month'];
        $findYear = $find['year'];
        $findWeekday = strtolower($find['weekday']);
        $findWeek = $find['week'];


        // set the initial date for loop
        $firstDate = $this->symbolObject->getFirstdate()->format('Y-m-d');
        $lastDate = $this->symbolObject->getLastdate()->format('Y-m-d');

        $firstDate = new \DateTime($firstDate);
        $lastDate = new \DateTime($lastDate);

        $dateDiff = intval($firstDate->diff($lastDate)->format("%R%a"));

        // generate a list of day 1
        $dayArray = array();
        $startDate = $firstDate->format('Y-m-d');
        for ($day = 0; $day <= $dateDiff; $day++) {
            $currentDate = strtotime($startDate . " +" . $day . " Day");

            $currentDay = Date("d", $currentDate);
            $currentMonth = Date("m", $currentDate);
            $currentYear = Date("Y", $currentDate);
            $currentWeekday = strtolower(Date("l", $currentDate));
            $currentWeek = Date("W", $currentDate);

            // check day is same or any
            $found = 1;

            // compare day
            if ($currentDay != $findDay && $findDay > 0) {
                $found = 0;
            }

            // compare month
            if ($currentMonth != $findMonth && $findMonth > 0) {
                $found = 0;
            }

            // compare year
            if ($currentYear != $findYear && $findYear > 0) {
                $found = 0;
            }

            // compare weekday

            if ($currentWeekday != $findWeekday && !is_numeric($findWeekday)) {
                $found = 0;
            }

            // compare week
            if ($currentWeek != $findWeek && $findWeek > 0) {
                $found = 0;
            }

            // if all match then get date
            if ($found) {
                $dayArray[] = Date("Y-m-d", strtotime($startDate . " +" . $day . " Day"));
            }
        }

        $symbolEM = $this->getDoctrine()->getManager('symbol');

        if (count($dayArray)) {
            $underlyings = $symbolEM->getRepository('JackImportBundle:Underlying')
                ->findBy(array('date' => $dayArray), array('date' => $sort));
        } else {
            $underlyings = 0;
        }

        return $underlyings;

    }

    /**
     * @param $firstDate
     * first date use in form for last search
     * @param $lastDate
     * last date use in form for last search
     * @param $returnURL
     * return url for redirect in page
     * @return \Symfony\Component\Form\FormView
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function getDateRangeForm($firstDate, $lastDate, $returnURL)
    {
        // put used first and last date into form
        $findForm = array(
            'startDate' => new \DateTime($firstDate),
            'endDate' => new \DateTime($lastDate),
        );

        // use the symbol first and last date in form
        if (!($this->symbolObject instanceof Symbol)) {
            throw $this->createNotFoundException(
                'Symbol Object in [ Underlying Class ] do not set!'
            );
        }

        // set the first and last year in underlying
        $firstYear = $this->symbolObject->getFirstdate()->format('Y');
        $lastYear = $this->symbolObject->getLastdate()->format('Y');

        // create form
        $form = $this->createFormBuilder($findForm)
            ->setAction($this->generateUrl(
                'jack_find_underlying_result_daterange_redirect', array(
                'firstDate' => $firstDate,
                'lastDate' => $lastDate,
                'symbol' => $this->symbol,
                'action' => 'findbydaterange'
            )))
            ->add('startDate', 'date', array(
                'input' => 'datetime',
                'widget' => 'choice',
                'years' => range($firstYear, $lastYear),
                'required' => true,
            ))
            ->add('endDate', 'date', array(
                'input' => 'datetime',
                'widget' => 'choice',
                'years' => range($firstYear, $lastYear),
                'required' => true,
            ))
            ->add('find', 'submit')
            ->getForm();

        // return as a form view
        return $form->createView();
    }

    /**
     * @param $symbol
     * symbol use to generate data
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     * redirect back into the page
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error object from db
     */
    public function dateRangeRedirectAction($symbol)
    {
        // work now, redirect to same page with usable date
        $errorFound = 0;

        $formData = $this->getRequest()->get('form');

        $firstYear = $formData['startDate']['year'];
        $firstMonth = $formData['startDate']['month'];
        $firstDay = $formData['startDate']['day'];

        $lastYear = $formData['endDate']['year'];
        $lastMonth = $formData['endDate']['month'];
        $lastDay = $formData['endDate']['day'];


        // validate is all numeric
        if (!is_numeric($firstYear) || !is_numeric($firstMonth) ||
            !is_numeric($firstDay) || !is_numeric($lastYear) ||
            !is_numeric($lastMonth) || !is_numeric($lastDay)
        ) {
            $errorFound = 1;
        }

        // check is reverse
        // if year is bigger
        $reverse = 0;
        if ($firstYear > $lastYear) {
            $reverse = 1;
        }

        // if month is bigger
        if (($firstYear == $lastYear) && ($firstMonth > $lastMonth)) {
            $reverse = 1;
        }

        // if date is bigger
        if (($firstYear == $lastYear) && ($firstMonth == $lastMonth) && ($firstDay > $lastDay)) {
            $reverse = 1;
        }

        // error checking
        if ($errorFound) {
            $this->getSymbolObject($symbol);

            if (!($this->symbolObject instanceof Symbol)) {
                throw $this->createNotFoundException(
                    'Error [ Symbol ] object from entity manager'
                );
            }

            $firstDate = $this->symbolObject->getFirstdate()->format('Y-m-d');
            $lastDate = $this->symbolObject->getLastdate()->format('Y-m-d');
        } else {
            if ($reverse) {
                $firstYear = $lastYear;
                $firstMonth = $lastMonth;
                $firstDay = $lastDay;

                $lastYear = $formData['startDate']['year'];
                $lastMonth = $formData['startDate']['month'];
                $lastDay = $formData['startDate']['day'];
            }

            $firstDate = "$firstYear-$firstMonth-$firstDay";
            $lastDate = "$lastYear-$lastMonth-$lastDay";
        }


        return $this->redirect($this->generateUrl('jack_find_underlying_result_findbydaterange',
            array(
                'firstDate' => $firstDate,
                'lastDate' => $lastDate,
                'symbol' => $symbol,
                'action' => 'findbydaterange'
            )
        ));

    }

    /**
     * @param $currentWeek
     * current searched day, do not allow to reselect
     * @param $returnURL
     * return url for the search
     * @return array
     * return a list of day link for select
     */
    private function getListOfWeek($currentWeek, $returnURL)
    {
        $weekLinkArray = Array();

        for ($week = 1; $week <= 53; $week++) {
            $useWeek = $week;
            $useUrl = $returnURL;

            if ($week == $currentWeek) {
                $useWeek = $week;
                $useUrl = '#';
            }

            $weekLinkArray[] = array(
                'week' => $useWeek,
                'url' => $useUrl,
            );
        }

        return $weekLinkArray;
    }

    /**
     * @param $currentWeekday
     * @param $returnURL
     * @return array
     */
    private function getListOfWeekday($currentWeekday, $returnURL)
    {
        $weekdayArray = array(
            '1' => 'Monday',
            '2' => 'Tuesday',
            '3' => 'Wednesday',
            '4' => 'Thursday',
            '5' => 'Friday',
        );

        $weekdayLinkArray = Array();

        for ($weekday = 1; $weekday <= 5; $weekday++) {
            $useDisplay = $weekdayArray[$weekday];
            $useWeekday = strtolower($weekdayArray[$weekday]);
            $useUrl = $returnURL;

            if (strtolower($weekdayArray[$weekday]) == $currentWeekday) {
                $useDisplay = $weekdayArray[$weekday];
                $useWeekday = strtolower($weekdayArray[$weekday]);
                $useUrl = '#';
            }

            $weekdayLinkArray[] = array(
                'display' => $useDisplay,
                'weekday' => $useWeekday,
                'url' => $useUrl,
            );
        }

        return $weekdayLinkArray;
    }

    /**
     * @param $currentYear
     * @param $returnURL
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function getListOfYear($currentYear, $returnURL)
    {

        $symbol = $this->getDoctrine('system')
            ->getRepository('JackImportBundle:Symbol')
            ->findOneBy(
                array('name' => $this->symbol)
            );

        if (!$symbol) {
            throw $this->createNotFoundException(
                'No such symbol [' . $this->symbol . '] exist in db!'
            );
        }

        if (!($symbol instanceof Symbol)) {
            throw $this->createNotFoundException(
                'Error [ Symbol ] object from entity manager'
            );
        }

        $startYear = $symbol->getFirstdate()->format("Y");
        $lastYear = $symbol->getLastdate()->format("Y");

        $yearLinkArray = Array();

        for ($year = $startYear; $year <= $lastYear; $year++) {
            $useYear = $year;
            $useUrl = $returnURL;

            if ($year == $currentYear) {
                $useYear = $year;
                $useUrl = '#';
            }

            $yearLinkArray[] = array(
                'year' => $useYear,
                'url' => $useUrl,
            );
        }

        return $yearLinkArray;
    }


    /**
     * @param $currentMonth
     * @param $returnURL
     * @return array
     */
    private function getListOfMonth($currentMonth, $returnURL)
    {
        $monthArray = Array(
            '1' => 'JAN',
            '2' => 'FEB',
            '3' => 'MAR',
            '4' => 'APR',
            '5' => 'MAY',
            '6' => 'JUN',
            '7' => 'JUL',
            '8' => 'AUG',
            '9' => 'SEP',
            '10' => 'OCT',
            '11' => 'NOV',
            '12' => 'DEC',
        );
        $monthLinkArray = Array();

        for ($month = 1; $month <= 12; $month++) {
            $useDisplay = $monthArray[$month];
            $useMonth = $month;
            $useUrl = $returnURL;

            if ($month == $currentMonth) {
                $useMonth = $month;
                $useUrl = '#';
            }

            $monthLinkArray[] = array(
                'display' => $useDisplay,
                'month' => $useMonth,
                'url' => $useUrl,
            );
        }

        return $monthLinkArray;
    }

    /**
     * @param $currentDay
     * current searched day, do not allow to reselect
     * @param $returnURL
     * return url for the search
     * @return array
     * return a list of day link for select
     */
    private function getListOfDay($currentDay, $returnURL)
    {
        $dayLinkArray = Array();

        for ($day = 1; $day <= 31; $day++) {
            $useDay = $day;
            $useUrl = $returnURL;

            if ($day == $currentDay) {
                $useDay = $day;
                $useUrl = '#';
            }

            $dayLinkArray[] = array(
                'day' => $useDay,
                'url' => $useUrl,
            );
        }

        return $dayLinkArray;
    }

}
