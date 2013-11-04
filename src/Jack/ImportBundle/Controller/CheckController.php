<?php

namespace Jack\ImportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\QueryBuilder;

use Jack\ImportBundle\Entity\Symbol;
use Jack\ImportBundle\Entity\Underlying;

/**
 * Class CheckController
 * @package Jack\ImportBundle\Controller
 * use to check quote imported into database is correct
 * range within first and last date is correct
 * also display imported total date, cycle, and strikes
 * verify is the database is ready for use
 */
class CheckController extends Controller
{
    // primary check first and last date within database exist
    // addition check symbol table is correct
    public function formAction(Request $request)
    {
        $search = new Symbol();
        $search->setName(1);

        // get data from symbol tables
        // name, import date, range
        /** @noinspection PhpUndefinedMethodInspection */
        $symbols = $this->getDoctrine()
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

                $formatName = $symbol->getName()
                    . " - Last Imported: " .
                    date("Y/m/d", $symbol->getImportdate()->getTimestamp())
                    . $markNew;

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

        // create form for search
        $form = $this->createFormBuilder($search)
            ->add('name', 'choice', array(
                'choices' => $nameArray,
                'required' => true,
                'multiple' => false,
            ))
            ->add('check', 'submit')
            ->getForm();

        $form->handleRequest($request);

        if ($form->isValid()) {
            // the validation passed, completed

            return $this->redirect(
                $this->generateUrl(
                    'jack_import_check_report',
                    array(
                        'name' => strtolower($form->getData()->getName())
                    )
                )
            );
        }

        return $this->render('JackImportBundle:Check:form.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }

    /**
     * @param $name
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function reportAction($name)
    {
        // TODO check date range

        // switch the symbol database into ebay
        //$this->get('jack_service.fastdb')->switchSymbolDb('ebay');
        //$service = $this->get('jack_service.fastdb')->switchSymbolDb($symbol);
        //$entityManager = $this->getDoctrine()->getManager('symbol');
        /** @noinspection PhpUndefinedMethodInspection */
        //$underlying = $entityManager
        //    ->getRepository('JackImportBundle:Underlying')
        //    ->findOneBy(array('name'=>'EBAY'));


        // 1. check symbol exist in system symbol table
        $systemEM = $this->getDoctrine()->getManager('system');
        $symbol = $systemEM->getRepository('JackImportBundle:Symbol')
            ->findOneBy(array('name' => $name));

        if ($symbol) {
            // found on symbol table in system db
            if ($symbol instanceof Symbol) {
                // start check date range from symbol db

                // switch db first
                $this->get('jack_service.fastdb')->switchSymbolDb($name);

                // then get data from tables
                $symbolEM = $this->getDoctrine()->getManager('symbol');
                $underlyings = $symbolEM
                    ->getRepository('JackImportBundle:Underlying')
                    ->findAll(array('name' => $name));

                // check underlying data exist
                if (!$underlyings) {
                    throw $this->createNotFoundException(
                        'Underlying [ ' . strtoupper($name) .
                        ' ] does not have any date yet!'
                    );
                }
            } else {
                throw $this->createNotFoundException(
                    'Error [ Symbol ] object from entity manager!'
                );
            }
        } else {
            throw $this->createNotFoundException(
                'Symbol [ ' . strtoupper($name) . ' ] was not found on database!'
            );
        }

        // now you have underlying data
        // use it compare the date with symbol

        $firstDate = 0;
        $lastDate = 0;
        foreach ($underlyings as $underlying) {
            if (!$underlying instanceof Underlying) {
                throw $this->createNotFoundException(
                    'Error [ Underlying ] object from entity manager!'
                );
            }

            $underlyingDate = $underlying->getDate()->getTimestamp();
            // if first date is larger than current date
            if ($firstDate >= $underlyingDate || !$firstDate) {
                // set first date
                $firstDate = $underlyingDate;
            }
            // if last date is smaller than current date
            if ($lastDate <= $underlyingDate || !$lastDate) {
                $lastDate = $underlyingDate;
            }
        }

        // now you have first date and last date
        // check empty date in that range
        // first you generate require date from loop

        // 24 hours, 60 minutes, 60 seconds
        $everyDay = 24 * 60 * 60;
        $dayBetween = ($lastDate - $firstDate) / $everyDay;
        $businessDays = 0;

        $totalMissing = 0;
        $missingDates = Array();
        $missingDate = "";
        for ($dateNow = $firstDate; $dateNow <= $lastDate; $dateNow += $everyDay) {
            // loop underlying object for exist

            $weekday = date('l', $dateNow);
            if ($weekday != 'Saturday' && $weekday != 'Sunday') {
                // working days only
                $businessDays++;

                // if not saturday or sunday
                // check the result

                $foundMatch = 0;
                foreach ($underlyings as $underlying) {
                    if ($underlying instanceof Underlying) {
                        // compare the date
                        if ($dateNow == $underlying->getDate()->getTimestamp()) {
                            $foundMatch = 1;
                        }
                    }
                }

                if (!$foundMatch) {
                    // if not found in table
                    // add it into missing array
                    $totalMissing++;
                    $missingDate[] = date("Y-m-d (l)", $dateNow);
                    if ($weekday == 'Friday') {
                        $missingDates[] = implode(" , ", $missingDate);
                        $missingDate = null;
                    }
                }
            }
        }


        // TODO: what if holiday exist? use the event object
        // highlight holiday date from array


        // count cycle, striek, and chain
        $qb = $symbolEM->createQueryBuilder();
        if ($qb instanceof QueryBuilder) {
            $qb->select('count(cycle.id)');
            $qb->from('JackImportBundle:Cycle', 'cycle');
            $cycleCount = $qb->getQuery()->getSingleScalarResult();

            $qb->select('count(strike.id)');
            $qb->from('JackImportBundle:Strike', 'strike');
            $strikeCount = $qb->getQuery()->getSingleScalarResult();

            $qb->select('count(chain.id)');
            $qb->from('JackImportBundle:Chain', 'chain');
            $chainCount = $qb->getQuery()->getSingleScalarResult();
        } else {
            throw $this->createNotFoundException(
                'Error [ QueryBuilder ] object from entity manager!'
            );
        }

        return $this->render(
            'JackImportBundle:Check:report.html.twig',
            array(
                'symbol' => strtoupper($name),
                'firstDate' => date("Y-m-d", $firstDate),
                'lastDate' => date("Y-m-d", $lastDate),
                'dayBetween' => $dayBetween,
                'workingDays' => $businessDays,
                'totalMissingDays' => $totalMissing,
                'missingDates' => $missingDates,
                'cycleCount' => $cycleCount,
                'strikeCount' => $strikeCount,
                'chainCount' => $chainCount,

            )
        );
    }

}
