<?php

namespace Jack\FindBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

use Jack\ImportBundle\Entity\Symbol;

class FindController extends Controller
{
    protected $symbol;
    protected $symbolObject;

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
