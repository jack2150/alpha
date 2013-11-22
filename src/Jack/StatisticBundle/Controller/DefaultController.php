<?php

namespace Jack\StatisticBundle\Controller;

use Jack\FindBundle\Controller\FindController;

use Doctrine\ORM\Tools\SchemaTool;

use Jack\ImportBundle\Entity\Underlying;
use Jack\ImportBundle\Entity\Cycle;
use Jack\ImportBundle\Entity\Strike;
use Jack\ImportBundle\Entity\Chain;

/**
 * Class DefaultController
 * @package Jack\StatisticBundle\Controller
 */
class DefaultController extends FindController
{
    protected $underlyings;
    protected $cycles;
    protected $strikes;

    /**
     * @param $tableName
     * @return int
     */
    public function checkTableExist($tableName)
    {
        $schemaManager = $this->getDoctrine()->getConnection('symbol')->getSchemaManager();

        if ($schemaManager->tablesExist(array($tableName)) == true) {
            // table exists! ...
            return 1;
        }

        return 0;
    }

    /**
     * @param $tableName
     */
    public function createTable($tableName)
    {
        // create new schema object
        $entityManager = $this->getDoctrine()->getManager('symbol');

        /** @var $entityManager \Doctrine\ORM\EntityManager */
        $tool = new SchemaTool($entityManager);

        $tool->createSchema(array(
            $entityManager->getClassMetadata($tableName),
        ));
    }

    /**
     * @param $underlyingId
     * must be exist in underlying table, if not nothing will be found
     * @param $date
     * the date must be same row as underlying id for searching
     * @param $dte
     * in that date, we forward looking +dte to select cycle
     * @param string $type
     * the search type for cycle dte (underlying date+dte - cycle expire date)
     * closest - will get positive or negative cycle closest to dte
     * forward - will only get positive cycle closest to dte
     * backward - will only get negative cycle closest to dte
     * @param int $recursive
     * use number of next cycle, for closest cycle array(0,1,2,3...)
     * @return Cycle object
     * return one cycle object that is closest to DTE
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error happen when getting object from db
     */
    public function findOneCycleByDTE($underlyingId, $date, $dte, $type = 'closest', $recursive = 0)
    {
        // set default time zone to utc
        date_default_timezone_set('UTC');

        // set search date
        $findDate = new \DateTime($date);
        $findDate = $findDate->modify("+$dte days");

        // get the day diff between find date and cycle date
        $dayDiffData = array();
        $dayDiffs = array();
        foreach ($this->cycles as $cycle) {
            if (!($cycle instanceof Cycle)) {
                throw $this->createNotFoundException(
                    'Error [ Cycle ] object from entity manager!'
                );
            }

            $cycleDate = $cycle->getExpiredate()->format('Y-m-d');
            $cycleDate = new \DateTime($cycleDate);

            // reverse
            //$dayDiff[] = $cycleDate->diff($findDate)->format('%R%a');
            $dayDiffs[$cycle->getId()] = intval($findDate->diff($cycleDate)->format('%R%a'));

            // debug use only
            $dayDiffData[$cycle->getId()] =
                $cycle->getExpiredate()->format('Y-m-d') . " - " .
                $findDate->format('Y-m-d')
                . " = " . $findDate->diff($cycleDate)->format('%R%a');
        }


        // now set the type of closest and sort array
        switch ($type) {

            case 'forward':
                foreach ($dayDiffs as $cycleId => $dayDiff) {
                    if ($dayDiff < 0) {
                        unset($dayDiffs[$cycleId]);
                    }
                }
                asort($dayDiffs);
                break;
            case 'backward':
                foreach ($dayDiffs as $cycleId => $dayDiff) {
                    if ($dayDiff > 0) {
                        unset($dayDiffs[$cycleId]);
                    }
                }
                arsort($dayDiffs);
                break;
            case 'closest':
            default:
                foreach ($dayDiffs as $cycleId => $dayDiff) {
                    if ($dayDiff < 0) {
                        $dayDiffs[$cycleId] = -$dayDiff;
                    }
                }
                asort($dayDiffs);
                break;
        }

        // find it in chain
        $foundCycleId = null;
        foreach ($dayDiffs as $cycleId => $dayDiff) {
            $chain = $this->findChainOneByIds($underlyingId, $cycleId);

            if ($chain) {
                // check chain exist, if yes add into data
                $foundCycleId = $cycleId;

                $dayDiffData[$cycleId] .= ' Use this!';

                if ($recursive) {
                    $recursive--;
                } else {
                    break;
                }
            } else {
                $dayDiffData[$cycleId] .= ' Not Found!';
            }
        }

        // return cycle object
        $foundCycle = null;
        foreach ($this->cycles as $cycle) {
            if ($cycle->getId() == $foundCycleId) {
                $foundCycle = $cycle;
            }
        }

        return $foundCycle;
    }

    /**
     * @param $underlyingId
     * @param $cycleId
     * @param $findPrice
     * @param string $category
     * @param string $type
     * @param int $recursive
     * @return null
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function findOneStrikeByPrice
    ($underlyingId, $cycleId, $findPrice, $category = 'call', $type = 'atm', $recursive = 0)
    {
        $chain = null;

        // format input price
        $findPrice = floatval($findPrice);
        $category = strtoupper($category);

        // compare underlying price and strike price
        $priceDiffs = array();
        $priceDiffData = array();
        foreach ($this->strikes as $strike) {
            if (!($strike instanceof Strike)) {
                throw $this->createNotFoundException(
                    'Error [ Strike ] object from entity manager!'
                );
            }

            // format strike price in object and get diff in price

            $strikePrice = floatval($strike->getPrice());

            if ($strike->getCategory() == $category) {
                if ($category == 'call') {
                    // because is 'call' so it use reverse
                    $priceDiffs[$strike->getId()] = $strikePrice - $findPrice;

                    // debug only
                    $diff = $strikePrice - $findPrice;
                    $priceDiffData[$strike->getId()] =
                        "(strike) $strikePrice - (find) $findPrice = (diff) $diff";
                } else {
                    // is put
                    $priceDiffs[$strike->getId()] = $findPrice - $strikePrice;

                    // debug only
                    $diff = $findPrice - $strikePrice;
                    $priceDiffData[$strike->getId()] =
                        "(find) $findPrice - (strike) $strikePrice = (diff) $diff";
                }
            }

        }

        // format price diff array using type method
        switch ($type) {
            // only use out of money
            case 'otm':
                foreach ($priceDiffs as $strikeId => $priceDiff) {
                    if ($priceDiff < 0) {
                        unset($priceDiffs[$strikeId]);
                    }
                }
                asort($priceDiffs);
                break;
            case 'itm':
                foreach ($priceDiffs as $strikeId => $priceDiff) {
                    if ($priceDiff > 0) {
                        unset($priceDiffs[$strikeId]);
                    }
                }
                arsort($priceDiffs);
                break;
            case 'atm':
            default:
                foreach ($priceDiffs as $strikeId => $priceDiff) {
                    if ($priceDiff < 0) {
                        $priceDiffs[$strikeId] = -$priceDiff;
                    }
                }
                asort($priceDiffs);
                break;
        }

        // loop price diff to check exist chain
        $foundStrikeId = 0;
        foreach ($priceDiffs as $strikeId => $priceDiff) {
            $chain = $this->findChainOneByIds($underlyingId, $cycleId, $strikeId);

            if ($chain) {
                // check chain exist, if yes add into data
                $foundStrikeId = $strikeId;

                $priceDiffData[$strikeId] .= ' Use this!';

                if ($recursive) {
                    $recursive--;
                } else {
                    break;
                }
            } else {
                $priceDiffData[$strikeId] .= ' Not Found!';
            }
        }

        // return strike object
        $foundStrike = null;
        foreach ($this->strikes as $strike) {
            if ($strike->getId() == $foundStrikeId) {
                $foundStrike = $strike;
            }
        }

        return $foundStrike;
    }

    /**
     * @return array
     */
    public function findPcRatioAll()
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Pcratio')
            ->findAll();
    }


}
