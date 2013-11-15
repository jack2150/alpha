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

    // Force Extending class to define this method
    /**
     * @param Request $request
     * @return mixed
     */
    //abstract public function indexAction(Request $request);


    /**
     * @param string $sort
     * underlying data sort 'date' type 'asc' or 'desc' by date
     * @return array
     * return an array of underlying objects
     */
    public function findUnderlyingAll($sort = 'asc')
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Underlying')
            ->findBy(array(), array('date' => $sort));
    }


    /**
     * @param string $sort
     * cycles data sort 'expiredate' type 'asc' or 'desc' by date
     * @return array
     * return an array of cycle objects
     */
    public function findCycleAll($sort = 'asc')
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Cycle')
            ->findBy(array(), array('expiredate' => $sort));
    }

    /**
     * @param string $sort
     * strikes data sort 'strike' type 'asc' or 'desc' by date
     * @return array
     * return an array of strike objects
     */
    public function findStrikeAll($sort = 'asc')
    {
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        return $symbolEM
            ->getRepository('JackImportBundle:Strike')
            ->findBy(array(), array('strike' => $sort));
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
