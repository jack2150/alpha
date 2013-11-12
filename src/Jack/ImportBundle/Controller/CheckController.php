<?php

namespace Jack\ImportBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\QueryBuilder;

use Jack\ImportBundle\Entity\Symbol;
use Jack\ImportBundle\Entity\Underlying;
use Jack\ImportBundle\Entity\Holiday;

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
        date_default_timezone_set('UTC');

        // 1. check symbol exist in system symbol table
        $systemEM = $this->getDoctrine()->getManager('system');
        $symbol = $systemEM->getRepository('JackImportBundle:Symbol')
            ->findOneBy(array('name' => $name));

        // highlight holiday date from array
        $holidays = $systemEM->getRepository('JackImportBundle:Holiday')
            ->findAll();

        if ($holidays) {
            foreach ($holidays as $holiday) {
                if (!$holiday instanceof Holiday) {
                    throw $this->createNotFoundException(
                        'Error [ Holiday ] object from entity manager!'
                    );
                }

                $holidayDate[] = $holiday->getDate()->format('Y-m-d');
            }
            unset($holidays);
        }

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
                $firstDateObject = $underlying->getDate();
            }
            // if last date is smaller than current date
            if ($lastDate <= $underlyingDate || !$lastDate) {
                $lastDate = $underlyingDate;
                $lastDateObject = $underlying->getDate();
            }
        }

        // now you have first date and last date
        // check empty date in that range
        // first you generate require date from loop


        // 24 hours, 60 minutes, 60 seconds
        $everyDay = 24 * 60 * 60;
        $dayBetween = ($lastDate - $firstDate) / $everyDay;
        /** @var $lastDateObject \DateTime */
        /** @var $firstDateObject \DateTime */
        $diff = $lastDateObject->diff($firstDateObject);
        $dayBetween = $diff->format('%a');
        $businessDays = 0;


        $date = '2009-12-06';
        // End date
        $end_date = '2020-12-31';

        $totalMissing = 0;
        $missingDates = Array();
        $missingDate = null;


        // End date
        $current_date = $firstDateObject->format('Y-m-d');
        $end_date = $lastDateObject->format('Y-m-d');;
        $holidayCount = 0;

        while (strtotime($current_date) <= strtotime($end_date)) {

            $weekday = date('l', strtotime($current_date));
            if ($weekday != 'Saturday' && $weekday != 'Sunday') {
                // working days only
                $businessDays++;

                $foundMatch = 0;
                foreach ($underlyings as $underlying) {
                    if ($underlying instanceof Underlying) {
                        // compare the date
                        $compareDate = $underlying->getDate()->format('Y-m-d');
                        if ($current_date == $compareDate) {
                            $foundMatch = 1;
                        }
                    }
                }

                if (!$foundMatch) {
                    // if not found in table
                    // check is holiday

                    $notHoliday = 1;
                    if (isset($holidayDate)) {
                        foreach ($holidayDate as $holiday) {
                            if ($current_date == $holiday) {
                                $notHoliday = 0;
                            }
                        }
                    }

                    if ($notHoliday) {
                        // add it into missing array
                        $totalMissing++;
                        $missingDate[] = date("Y-m-d (l)", strtotime($current_date));
                    } else {
                        // is holiday
                        $holidayCount++;
                    }

                    if ($weekday == 'Friday' && isset($missingDate)) {
                        $missingDates[] = implode(" , ", $missingDate);
                        $missingDate = null;
                    }
                }
            }

            $current_date = date("Y-m-d", strtotime("+1 day", strtotime($current_date)));
        }
        if (isset($missingDate)) {
            $missingDates[] = implode(" , ", $missingDate);
        }


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
                'workingDays' => $businessDays - $holidayCount,
                'totalMissingDays' => $totalMissing,
                'missingDates' => $missingDates,
                'cycleCount' => $cycleCount,
                'strikeCount' => $strikeCount,
                'chainCount' => $chainCount,

            )
        );
    }

}
