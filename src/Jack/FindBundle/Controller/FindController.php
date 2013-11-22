<?php

namespace Jack\FindBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;
use Symfony\Component\HttpFoundation\Request;

use Jack\ImportBundle\Entity\Symbol;

/**
 * Class FindController
 * @package Jack\FindBundle\Controller
 */
abstract class FindController extends Controller
{
    protected $symbol;
    protected $symbolObject;

    /**
     * @param $symbol
     */
    public function init($symbol)
    {
        // default timezone
        date_default_timezone_set('UTC');

        // set symbol and symbol object
        $this->symbol = $symbol;
        $this->getSymbolObject($symbol);

        // start function with get underlyings data
        $this->get('jack_service.fastdb')->switchSymbolDb($symbol);
    }

    /**
     * @param string $sort
     * underlying data sort 'date' type 'asc' or 'desc' by date
     * @return array
     * return an array of underlying objects
     */
    public function findUnderlyingAll($sort = 'asc')
    {
        //date_default_timezone_set('UTC');

        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Underlying')
            ->findBy(array(), array('date' => $sort));
    }

    /**
     * @param $firstDate
     * @param $lastDate
     * @param string $sort
     * @return mixed
     */
    public function findUnderlyingByDateRange($firstDate, $lastDate, $sort = 'asc')
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        $repository = $symbolEM->getRepository('JackImportBundle:Underlying');

        $query = $repository->createQueryBuilder('u')
            ->where('u.date >= :firstDate and u.date <= :lastDate')
            ->setParameter('firstDate', $firstDate)
            ->setParameter('lastDate', $lastDate)
            ->orderBy('u.date', $sort)
            ->getQuery();

        return $query->getResult();
    }


    /**
     * @param string $sort
     * cycles data sort 'expiredate' type 'asc' or 'desc' by date
     * @return array
     * return an array of cycle objects
     */
    public function findCycleAll($sort = 'asc')
    {
        //date_default_timezone_set('UTC');

        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Cycle')
            ->findBy(array(), array('expiredate' => $sort));
    }

    /**
     * @param string $sort
     * strikes data sort 'price' type 'asc' or 'desc' by date
     * @return array
     * return an array of strike objects
     */
    public function findStrikeAll($sort = 'asc')
    {
        //date_default_timezone_set('UTC');

        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Strike')
            ->findBy(array(), array('price' => $sort));
    }

    /**
     * @param $category
     * value either 'call' or 'put'
     * @param string $sort
     * sort type for the result
     * @return array
     * return a list of strike objects
     */
    public function findStrikeByCategory($category, $sort = 'asc')
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Strike')
            ->findBy(
                array('category' => strtoupper($category)),
                array('price' => $sort)
            );
    }

    /**
     * @param int $underlyingId
     * underlying id from underlying table
     * @param int $cycleId
     * cycle id from cycle id table
     * @param int $strikeId
     * strike id from strike id table
     * @return object
     * use to search is the data 'exist' for
     * both or all ids in chain table
     */
    public function findChainOneByIds($underlyingId = 0, $cycleId = 0, $strikeId = 0)
    {
        // generate search array
        $searchTerm = array();
        if ($underlyingId) {
            $searchTerm += array('underlyingid' => $underlyingId);
        }

        if ($cycleId) {
            $searchTerm += array('cycleid' => $cycleId);
        }

        if ($strikeId) {
            $searchTerm += array('strikeid' => $strikeId);
        }


        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Chain')
            ->findOneBy(
                $searchTerm
            );
    }

    /**
     * @param int $underlyingId
     * underlying id from underlying table
     * @param int $cycleId
     * cycle id from cycle id table
     * @param int $strikeId
     * strike id from strike id table
     * @return object
     * a list of chain objects
     */
    public function findChainByIds($underlyingId = 0, $cycleId = 0, $strikeId = 0)
    {
        // generate search array
        $searchTerm = array();
        if ($underlyingId) {
            $searchTerm += array('underlyingid' => $underlyingId);
        }

        if ($cycleId) {
            $searchTerm += array('cycleid' => $cycleId);
        }

        if ($strikeId) {
            $searchTerm += array('strikeid' => $strikeId);
        }


        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Chain')
            ->findBy(
                $searchTerm
            );
    }

    /**
     * @return array
     * generate a list of underlying symbol from system table
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * not found or object error
     */
    public function getSymbolArray()
    {
        $symbols = $this->getDoctrine('system')
            ->getRepository('JackImportBundle:Symbol')
            ->findBy(
                array(),
                array('importdate' => 'DESC')
            );

        if (!$symbols) {
            throw $this->createNotFoundException(
                'No underlying import to check yet!' .
                'Please import underlying to databases!'
            );
        }

        // loop all symbols list from table
        $latest = 1;
        $nameArray = Array();
        foreach ($symbols as $symbol) {

            if ($symbol instanceof Symbol) {
                if ($latest) {
                    $markNew = ' **';
                    $latest--;
                } else {
                    $markNew = '';
                }

                $formatName = $symbol->getName() . $markNew;

                $nameArray = array_merge(
                    $nameArray, array(
                        $symbol->getName() => $formatName
                    )
                );
            } else {
                throw $this->createNotFoundException(
                    'Error import symbols from database!'
                );
            }
        }

        return $nameArray;
    }

    /**
     * @param $symbol
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function getSymbolObject($symbol)
    {
        $symbol = $this->getDoctrine('system')
            ->getRepository('JackImportBundle:Symbol')
            ->findOneBy(
                array('name' => $symbol)
            );

        if (!$symbol) {
            throw $this->createNotFoundException(
                'No such symbol [' . $symbol . '] exist in db!'
            );
        }

        if (!($symbol instanceof Symbol)) {
            throw $this->createNotFoundException(
                'Error [ Symbol ] object from entity manager'
            );
        }

        $this->symbolObject = $symbol;
    }
}
