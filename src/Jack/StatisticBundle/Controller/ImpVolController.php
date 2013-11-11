<?php

namespace Jack\StatisticBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class ImpVolController extends Controller
{
    /*
     * 1. get data from underlying (all data with day)
     * 2. generate iv for every day using function with day input
     * 3.
     */
    protected static $sampleSize = array(
        'oneDay' => 1,
        'twoDay' => 2,
        'oneWeek' => 5,
        'twoWeek' => 10,
        'oneMonth' => 20,
        '45Day' => 30,
        'twoMonth' => 40,
        'threeMonth' => 60,
        'halfYear' => 120,
        'oneYear' => 252,
    );

    public function indexAction($symbol)
    {
        /*
         * 1. switch db to symbol db
         * 2. loop sample size
         * 3. get closest to expire date cycle
         * 4. calculate the iv
         * 5. format save it into db
         */
        // self::$sampleSize;


    }


    private function selectChainData($day)
    {
        /*
         * 1. generate a list of date + $day (currentDate + 45)
         * 2. get the list from db where dte nearest to 45
         * 3.
         */


    }

    public function getDataAction()
    {
    }


    public function calDataAction()
    {
    }

    public function formatDataAction()
    {
    }

    public function saveDataAction()
    {
    }

}
