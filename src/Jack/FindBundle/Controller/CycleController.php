<?php

namespace Jack\FindBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use Jack\FindBundle\Controller\FindController;

use Jack\ImportBundle\Entity\Cycle;

/**
 * Class CycleController
 * @package Jack\FindBundle\Controller
 */
class CycleController extends FindController
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $findCycleData = array(
            'symbol' => '',
            'action' => '------',
        );

        $findCycleForm = $this->createFormBuilder($findCycleData)
            ->add('symbol', 'choice', array(
                'choices' => $this->getSymbolArray(),
                'required' => true,
                'multiple' => false,
            ))
            ->add('action', 'choice', array(
                'choices' => array(
                    'findBySpecific' => 'Find By Specific',
                    'findAll' => 'Find All',
                    //'findByWeekNo' => 'Find By Week No.',
                    //'findByWeekly' => 'Find By Weekly',
                    //'findByMini' => 'Find By Mini',
                    //'findByMonth' => 'Find By Month',
                    //'findByLEAP' => 'Find By LEAP',
                ),
                'required' => true,
                'multiple' => false,
            ))
            ->add('find', 'submit')
            ->getForm();

        $findCycleForm->handleRequest($request);

        if ($findCycleForm->isValid()) {
            $findCycleData = $findCycleForm->getData();

            $symbol = $findCycleData['symbol'];
            $action = $findCycleData['action'];

            $returnUrl = '';
            $params = array();
            switch ($action) {
                case 'findBySpecific':
                    $returnUrl = 'jack_find_cycle_result_findbyspecific';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower($action),
                        'weekNo' => 0,
                        'month' => 0,
                        'year' => 0,
                        'leap' => 0,
                        'weekly' => 0,
                        'mini' => 0,
                    );
                    break;
                case 'findAll':
                    $returnUrl = 'jack_find_cycle_result_findall';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower($action),
                    );
                    break;
                default:

            }

            return $this->redirect(
                $this->generateUrl(
                    $returnUrl,
                    $params
                )
            );


        }

        return $this->render(
            'JackFindBundle:Cycle:index.html.twig',
            array(
                'findCycleForm' => $findCycleForm->createView(),
            )
        );
    }


    public function resultAction(
        $symbol, $action, $weekNo = 0, $month = 0, $year = 0, $leap = 0, $weekly = 0, $mini = 0
    )
    {
        $this->symbol = $symbol;
        $this->getSymbolObject($symbol);

        $this->get('jack_service.fastdb')->switchSymbolDb($symbol);

        $searchLinks = array();
        switch ($action) {
            case 'findbyspecific':
                $searchName = "Find By Specific Search";
                $cycles = $this->findCycleMix(
                    $weekNo,
                    $month,
                    $year,
                    $leap,
                    $weekly,
                    $mini,
                    'desc'
                );

                $linkType = 'specific';
                $weekNoLinks = $this->getWeekNoLinks(
                    $weekNo, 'jack_find_cycle_result_findbyspecific', 1
                );

                $miniLinks = $this->getMiniLinks(
                    $mini, 'jack_find_cycle_result_findbyspecific', 1
                );

                $weeklyLinks = $this->getWeeklyLinks(
                    $weekly, 'jack_find_cycle_result_findbyspecific', 1
                );

                $monthLinks = $this->getMonthLinks(
                    $month, 'jack_find_cycle_result_findbyspecific', 1
                );

                $leapLinks = $this->getLeapLinks(
                    $leap, 'jack_find_cycle_result_findbyspecific', 1
                );

                $yearLinks = $this->getYearLinks(
                    $year, 'jack_find_cycle_result_findbyspecific', 1
                );

                $searchLinks = array(
                    'weekNo' => $weekNoLinks,
                    'month' => $monthLinks,
                    'year' => $yearLinks,
                    'leap' => $leapLinks,
                    'weekly' => $weeklyLinks,
                    'mini' => $miniLinks,
                );
                break;
            case 'findall':
            default:
                $searchName = "Find All";
                $cycles = $this->findCycleAll('desc');
                $linkType = 'findall';
        }

        // count underlying
        $resultCount = 0;
        if (!empty($cycles)) {
            $resultCount = count($cycles);
        }

        return $this->render(
            'JackFindBundle:Cycle:result.html.twig',
            array(
                'symbol' => $symbol,
                'searchName' => $searchName,
                'cycles' => $cycles,
                'resultCount' => $resultCount,
                'linkType' => $linkType,
                'searchLinks' => $searchLinks,
                'parameters' => array(
                    'weekNo' => $weekNo,
                    'month' => $month,
                    'year' => $year,
                    'leap' => $leap,
                    'weekly' => $weekly,
                    'mini' => $mini,
                )
            )
        );
    }

    public function findCycleMix(
        $weekNo = 0, $month = 0, $year = 0, $leap = 0, $weekly = 0, $mini = 0, $sort = 'asc'
    )
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        $cycles = $symbolEM
            ->getRepository('JackImportBundle:Cycle')
            ->findBy(array(), array('expiredate' => $sort));

        // reset the parameter
        $weekNo = intval($weekNo);
        $mini = intval($mini);

        $displayCycles = array();
        foreach ($cycles as $cycle) {
            // use the symbol first and last date in form
            if (!($cycle instanceof Cycle)) {
                throw $this->createNotFoundException(
                    'Error getting [ Cycle ] object from entity manager!'
                );
            }

            $found = 1;

            // set current no week in month 1,2,3,4,5
            $currentWeekNo = intval(substr($cycle->getExpiremonth(), 3, 1));
            $currentWeekNo = $currentWeekNo ? $currentWeekNo : 3;
            if ($weekNo != $currentWeekNo && $weekNo) {
                $found = 0;
            }

            // check what month
            $currentMonth = intval($cycle->getExpiredate()->format('m'));
            if ($month != $currentMonth && $month) {
                $found = 0;
            }

            // check what month
            $currentYear = intval($cycle->getExpiredate()->format('Y'));
            if ($year != $currentYear && $year) {
                $found = 0;
            }

            // set mini then search 1 yes, -1 no, 0 any
            $currentMini = intval($cycle->getIsmini());
            $currentMini = $currentMini ? 1 : -1;
            if ($mini != $currentMini && $mini) {
                $found = 0;
            }

            $currentWeekly = intval($cycle->getIsweekly());
            $currentWeekly = $currentWeekly ? 1 : -1;
            if ($weekly != $currentWeekly && $weekly) {
                $found = 0;
            }

            // search leap type, where is always month
            $currentMonth = intval($cycle->getExpiredate()->format('m'));
            $leap = intval($leap);
            if ($leap == 1) {
                if ($currentMonth != 1 || $currentWeekNo != 3 || $currentMini != -1) {
                    $found = 0;
                }
            } elseif ($leap == -1) {
                if ($currentMonth == 1 && $currentWeekNo == 3 && $currentMini == -1) {
                    $found = 0;
                }
            }

            // now if found, add the result
            if ($found) {
                $displayCycles[] = $cycle;
            }
        }


        return $displayCycles;
    }


    private function getYearLinks($currentYear, $returnURL, $useAny = 0)
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        $cycles = $symbolEM
            ->getRepository('JackImportBundle:Cycle')
            ->findAll();

        if (!$cycles) {
            throw $this->createNotFoundException(
                'No such symbol [' . $this->symbol . '] exist in db!'
            );
        }

        //$startYear = $cycles->getFirstdate()->format("Y");
        //$lastYear = $cycles->getLastdate()->format("Y");

        $startYear = 0;
        $lastYear = 0;
        foreach ($cycles as $cycle) {
            if (!($cycle instanceof Cycle)) {
                throw $this->createNotFoundException(
                    'Error [ Symbol ] object from entity manager'
                );
            }

            $year = $cycle->getExpiredate()->format('Y');
            if ($startYear >= $year || !$startYear) {
                $startYear = $year;
            }

            if ($lastYear <= $year || !$lastYear) {
                $lastYear = $year;
            }
        }

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

        if ($useAny) {
            $useUrl = $returnURL;
            if ($currentYear == 0) {
                $useUrl = '#';
            }

            $yearLinkArray[] = array(
                'year' => 0,
                'url' => $useUrl,
            );
        }

        return $yearLinkArray;
    }


    private function getMonthLinks($currentMonth, $returnURL, $useAny = 0)
    {
        $monthLinkArray = Array();

        for ($month = 1; $month <= 12; $month++) {
            $useMonth = $month;
            $useUrl = $returnURL;

            if ($month == $currentMonth) {
                $useMonth = $month;
                $useUrl = '#';
            }

            $monthLinkArray[] = array(
                'month' => $useMonth,
                'url' => $useUrl,
            );
        }

        if ($useAny) {
            $useUrl = $returnURL;
            if ($currentMonth == 0) {
                $useUrl = '#';
            }

            $monthLinkArray[] = array(
                'month' => 0,
                'url' => $useUrl,
            );
        }

        return $monthLinkArray;
    }

    private function getLeapLinks($currentLeap, $returnURL, $useAny = 0)
    {
        $leapLinkArray = Array();

        // is mini
        for ($leap = 1; $leap >= -1; $leap--) {
            $useLeap = $leap;
            $useUrl = $returnURL;

            if ($leap != 0) {
                if ($leap == $currentLeap) {
                    $useUrl = '#';
                }

                $leapLinkArray[] = array(
                    'leap' => $useLeap,
                    'url' => $useUrl,
                );
            }
        }

        if ($useAny) {
            $useUrl = $returnURL;
            if ($currentLeap == 0) {
                $useUrl = '#';
            }

            $leapLinkArray[] = array(
                'leap' => 0,
                'url' => $useUrl,
            );
        }

        return $leapLinkArray;
    }


    private function getWeeklyLinks($currentWeekly, $returnURL, $useAny = 0)
    {
        $weeklyLinkArray = Array();

        // is mini
        for ($weekly = 1; $weekly >= -1; $weekly--) {
            $useWeekly = $weekly;
            $useUrl = $returnURL;

            if ($weekly != 0) {
                if ($weekly == $currentWeekly) {
                    $useUrl = '#';
                }

                $weeklyLinkArray[] = array(
                    'weekly' => $useWeekly,
                    'url' => $useUrl,
                );
            }
        }

        if ($useAny) {
            $useUrl = $returnURL;
            if ($currentWeekly == 0) {
                $useUrl = '#';
            }

            $weeklyLinkArray[] = array(
                'weekly' => 0,
                'url' => $useUrl,
            );
        }

        return $weeklyLinkArray;
    }

    private function getMiniLinks($currentMini, $returnURL, $useAny = 0)
    {
        $miniLinkArray = Array();

        // is mini
        for ($mini = 1; $mini >= -1; $mini--) {
            $useMini = $mini;
            $useUrl = $returnURL;

            if ($mini != 0) {
                if ($mini == $currentMini) {
                    $useUrl = '#';
                }

                $miniLinkArray[] = array(
                    'mini' => $useMini,
                    'url' => $useUrl,
                );
            }
        }

        if ($useAny) {
            $useUrl = $returnURL;
            if ($currentMini == 0) {
                $useUrl = '#';
            }

            $miniLinkArray[] = array(
                'mini' => 0,
                'url' => $useUrl,
            );
        }

        return $miniLinkArray;
    }

    /**
     * @param $currentWeekNo
     * @param $returnURL
     * @param int $useAny
     * @return array
     */
    private function getWeekNoLinks($currentWeekNo, $returnURL, $useAny = 0)
    {
        $weekNoLinkArray = Array();

        for ($weekNo = 1; $weekNo <= 5; $weekNo++) {
            $useWeekNo = $weekNo;
            $useUrl = $returnURL;

            if ($weekNo == $currentWeekNo) {
                $useUrl = '#';
            }

            $weekNoLinkArray[] = array(
                'weekNo' => $useWeekNo,
                'url' => $useUrl,
            );
        }

        if ($useAny) {
            $useUrl = $returnURL;
            if ($currentWeekNo == 0) {
                $useUrl = '#';
            }

            $weekNoLinkArray[] = array(
                'weekNo' => 0,
                'url' => $useUrl,
            );
        }

        return $weekNoLinkArray;
    }

}
