<?php

namespace Jack\ImportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Jack\ImportBundle\Entity\Symbol;
use Jack\ImportBundle\Entity\Underlying;
use Jack\ImportBundle\Entity\Event;
use Jack\ImportBundle\Entity\Earning;
use Jack\ImportBundle\Entity\Analyst;

// TODO: finish event model
/**
 * Class EventController
 * @package Jack\ImportBundle\Controller
 */
class EventController extends Controller
{
    /**
     * @param Request $request
     * data symbol name and event action
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     * template page for selecting event action and symbol
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error occur for invalid object, empty symbol or incorrect action
     */
    public function selectSymbolAction(Request $request)
    {
        // open system db
        // get list of symbol from db
        // create form and select it
        // go into add event form

        $symbols = $this->getDoctrine()
            ->getRepository('JackImportBundle:Symbol')
            ->findBy(
                array(),
                array('importdate' => 'DESC')
            );

        // error if databae is empty
        if (!$symbols) {
            throw $this->createNotFoundException(
                'Symbol table does not contain any row, ' .
                'please import first then add event...'
            );
        }

        // make a list of symbol names
        $latest = 1;
        $symbolNames = Array();
        foreach ($symbols as $symbol) {
            // error if wrong object
            if (!($symbol instanceof Symbol)) {
                throw $this->createNotFoundException(
                    'Error [ symbol ] object from database'
                );
            }

            // if it is latest
            $symbolKey = $symbol->getName();
            $symbolValue = $symbol->getName();
            if ($latest) {
                $symbolValue = $symbol->getName() . " **";
                $latest--;
            }

            // add symbol name into array
            $symbolNames = array_merge(
                $symbolNames, array(
                    $symbolKey => $symbolValue
                )
            );
        }

        // create new symbol object
        // for form usage and select
        $select = array('symbol' => null, 'action' => 'addEarning');
        $form = $this->createFormBuilder($select)
            ->add('symbol', 'choice', array(
                'choices' => $symbolNames,
                'required' => true,
                'multiple' => false,
            ))
            ->add('action', 'choice', array(
                'choices' => array(
                    'addEarning' => 'Add Earning',
                    'addEvent' => 'Add Event',
                    'addAnalyst' => 'Add Analyst',
                    'updateEvent' => 'Update Event',
                    'removeEvent' => 'Remove Event',
                ),
                'required' => true,
                'multiple' => false,
            ))
            ->add('continue', 'submit')
            ->getForm();


        // validation and redirect
        $form->handleRequest($request);

        if ($form->isValid()) {
            // select action and redirect

            // put the data into use
            $data = $form->getData();
            list($symbol, $action) = array($data['symbol'], $data['action']);

            // symbol cannot empty
            if (!$symbol) {
                throw $this->createNotFoundException(
                    'Empty symbol name, please select it!'
                );
            }

            // select action url and error checking
            switch ($action) {
                case 'addEarning':
                    $actionURL = 'jack_import_event_add_earning';
                    break;
                case 'addAnalyst':
                    $actionURL = 'jack_import_event_add_analyst';
                    break;
                case 'addEvent':
                    $actionURL = 'jack_import_event_add';
                    break;
                case 'updateEvent':
                    $actionURL = 'jack_import_event_update';
                    break;
                case 'removeEvent':
                    $actionURL = 'jack_import_event_remove';
                    break;
                default:
                    throw $this->createNotFoundException(
                        'Please select action to handle symbol event!'
                    );
            }

            // return template page
            return $this->redirect(
                $this->generateUrl(
                    $actionURL,
                    array('symbol' => strtolower($symbol))
                )
            );
        }

        // put into template
        return $this->render(
            'JackImportBundle:Event:selectSymbol.html.twig',
            array(
                'form' => $form->createView()
            )
        );
    }

    /**
     * @param $symbol
     * underlying db use for insert data
     * @param Request $request
     * event data from use input
     * @return \Symfony\Component\HttpFoundation\Response
     * event from for user input data
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error happen
     */
    public function addEventAction($symbol, Request $request)
    {
        $notice = "";
        $error = "";

        // add new event into database
        //$event = new Event();
        $event = array(
            //'date' => new \DateTime("today"),
            'date' => new \DateTime("today"),
            'name' => '',
            'context' => '',
            'symbol' => $symbol
        );
        $eventForm = $this->createFormBuilder($event)
            // only work on chrome but not firefox or ie
            ->add('date', 'date', array(
                'widget' => 'choice',
                'format' => 'MM/dd/yyyy',
                'required' => true,
            ))
            ->add('name', 'choice', array(
                'choices' => array(
                    //'earning' => 'Earning Report',
                    //'analyst' => 'Analyst Revision',
                    'dividend' => 'Dividend',
                    'conference' => 'Conference Call',
                    'split' => 'Split Shares',
                    'special' => 'Special News',
                ),
                'required' => true,
                'multiple' => false,
            ))
            ->add('context', 'text', array(
                'required' => false,
            ))
            ->add('symbol', 'hidden', array(
                'required' => true,
            ))
            ->add('save', 'submit')
            ->getForm();

        // switch the symbol db before it run valid
        $this->get('jack_service.fastdb')->switchSymbolDb($symbol);
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        // get request
        $eventForm->handleRequest($request);

        if ($eventForm->isValid()) {
            // then get data from tables

            // the validation passed, completed

            // validate context cannot empty
            // (not including earning, analyst)
            $event = $eventForm->getData();
            list($date, $name, $context, $symbol) =
                array($event['date'], $event['name'],
                    $event['context'], $event['symbol']
                );

            // error checking
            if (!$date || !$name || !$symbol || !$context) {
                // if not conference call
                if ($name != 'conference') {
                    $error = 'Missing Event [ date , name , symbol, description ] field!';
                }
            } else {
                // check dividend is value
                if ($name == 'dividend' && !(is_numeric($context))) {
                    $error = 'Dividend value must be a number!';
                }
            }


            if (!($date instanceof \DateTime)) {
                throw $this->createNotFoundException(
                    'DateTime object error on form submit!'
                );
            }

            // search db and date exist
            // switch db first

            // get date from table
            $underlying = $symbolEM
                ->getRepository('JackImportBundle:Underlying')
                ->findOneBy(array('date' => $date));

            // date not found because not import
            if (!$underlying) {
                $error = 'Date [ ' . $date->format("m-d-Y") . ' ] ' .
                    'does not exist in ' . strtoupper($symbol)
                    . ' table, please import...';
            } elseif (!$error) {
                // insert into table
                $event = new Event();

                // set data into object
                $event->setName($name);
                $event->setContext($context);
                /** @var $underlying Underlying */
                $event->setUnderlyingid($underlying);

                // insert into db
                $symbolEM->persist($event);
                $symbolEM->flush();

                // set notice
                $notice = "Event " . $date->format('m-d-Y') .
                    " [ $name - $context ] have been added!";
            }

            // everything fine, insert into database
        }

        return $this->render(
            'JackImportBundle:Event:addEvent.html.twig',
            array(
                'symbol' => strtoupper($symbol),
                'form' => $eventForm->createView(),
                'notice' => $notice,
                'error' => $error,
            )
        );
    }


    /**
     * @param $symbol
     * underlying symbol for insert data
     * @param Request $request
     * form input data from user
     * @return \Symfony\Component\HttpFoundation\Response
     * template for input data
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error happen
     */
    public function addEarningAction($symbol, Request $request)
    {
        // ready for notice and error warning
        $notice = "";
        $error = "";

        // create a new object for form
        $earning = array(
            'date' => new \DateTime("today"),
            'name' => 'earning',
            'symbol' => $symbol,
            'marketHour' => '',
            'periodEnding' => new \DateTime("today"),
            'estimate' => 0,
            'actual' => 0,
        );

        // create form now
        $earningForm = $this->createFormBuilder($earning)
            ->add('date', 'date', array(
                'widget' => 'choice',
                'format' => 'MMM/dd/yyyy',
                'required' => true,
            ))
            ->add('name', 'hidden', array('required' => true))
            ->add('symbol', 'hidden', array('required' => true))
            ->add('marketHour', 'choice', array(
                'choices' => array(
                    '' => '---',
                    'before' => 'Before Market',
                    'during' => 'During Market',
                    'after' => 'After Market',
                ),
                'required' => true,
                'multiple' => false,
            ))
            ->add('periodEnding', 'date', array(
                    'input' => 'datetime',
                    'widget' => 'choice',
                    'format' => 'MMM/dd/yyyy',
                    'days' => range(1, 1),
                )
            )
            ->add('estimate', 'number', array(
                'required' => true,
                'precision' => 2,
                'invalid_message' => 'Warning: Invalid format 0.00',
            ))
            ->add('actual', 'number', array(
                'required' => true,
                'precision' => 2,
                'invalid_message' => 'Warning: Invalid format 0.00',
            ))
            ->add('save', 'submit')
            ->getForm();

        // switch the symbol db before it run valid
        $this->get('jack_service.fastdb')->switchSymbolDb($symbol);
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        $earningForm->handleRequest($request);

        if ($earningForm->isValid()) {
            // validation already done
            $earning = $earningForm->getData();

            list($date, $name, $symbol, $marketHour, $periodEnding,
                $estimate, $actual) = array(
                $earning['date'], $earning['name'],
                $earning['symbol'], $earning['marketHour'],
                $earning['periodEnding'], $earning['estimate'],
                $earning['actual']
            );

            // validate all symbol date
            if (!($date instanceof \DateTime)) {
                throw $this->createNotFoundException(
                    'DateTime object error on form submit!'
                );
            }

            // get date from table
            $underlying = $symbolEM
                ->getRepository('JackImportBundle:Underlying')
                ->findOneBy(array('date' => $date));

            // date not found because not import
            if (!$underlying) {
                $error = 'Date [ ' . $date->format("m-d-Y") . ' ] ' .
                    'does not exist in ' . strtoupper($symbol)
                    . ' table, please import...';
            } else {
                // insert into table
                $event = new Event();

                // set data into object
                $event->setName($name);
                /** @var $underlying Underlying */
                $event->setUnderlyingid($underlying);

                $earning = new Earning();

                $earning->setMarkethour($marketHour);
                $earning->setPeriodending($periodEnding);
                $earning->setEstimate($estimate);
                $earning->setActual($actual);
                $earning->setEventid($event);


                // insert into db
                $symbolEM->persist($event);
                $symbolEM->persist($earning);
                $symbolEM->flush();

                // set notice
                $notice = "Event " . $date->format('m-d-Y') .
                    " [ $name - $marketHour - $actual ] have been added into databases!";

            }
        }

        return $this->render(
            'JackImportBundle:Event:addEarning.html.twig',
            array(
                'symbol' => strtoupper($symbol),
                'form' => $earningForm->createView(),
                'notice' => $notice,
                'error' => $error,
            )
        );
    }

    /**
     * @param $symbol
     * underlying symbol for insert analyst
     * @param Request $request
     * form data that need validate and insert
     * @return \Symfony\Component\HttpFoundation\Response
     * template form for input data
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error happen
     */
    public function addAnalystAction($symbol, Request $request)
    {
        // TODO: next
        // ready for notice and error warning
        $notice = "";
        $error = "";

        // create a new object for form
        $analyst = array(
            'date' => new \DateTime("today"),
            'name' => 'analyst',
            'symbol' => $symbol,
            'firm' => '',
            'opinion' => '',
            'rating' => '',
            'target' => 0.00,
        );

        $analystForm = $this->createFormBuilder($analyst)
            ->add('date', 'date', array(
                'widget' => 'choice',
                'format' => 'MMM/dd/yyyy',
                'required' => true,
            ))
            ->add('name', 'hidden', array('required' => true))
            ->add('symbol', 'hidden', array('required' => true))
            ->add('firm', 'text', array(
                'max_length' => 200,
                'required' => true,
                'invalid_message' => 'Firm cannot be empty!',
            ))
            ->add('opinion', 'choice', array(
                'choices' => array(
                    '' => '---',
                    '-1' => 'Downgrade',
                    '0' => 'Initial',
                    '1' => 'Upgrade',
                ),
                'multiple' => false,
                'required' => true,
            ))
            ->add('rating', 'choice', array(
                'choices' => array(
                    '' => '---',
                    '0' => 'Strong Sell',
                    '1' => 'Sell',
                    '2' => 'Hold',
                    '3' => 'Buy',
                    '4' => 'Strong Buy',
                ),
                'multiple' => false,
                'required' => true,
            ))
            ->add('target', 'number', array(
                'required' => true,
                'precision' => 2,
                'invalid_message' => 'Warning: Invalid format 0.00',
            ))
            ->add('save', 'submit')
            ->getForm();


        // switch the symbol db before it run valid
        $this->get('jack_service.fastdb')->switchSymbolDb($symbol);
        $symbolEM = $this->getDoctrine()->getManager('symbol');

        $analystForm->handleRequest($request);

        if ($analystForm->isValid()) {

            $analyst = $analystForm->getData();
            list($date, $name, $symbol, $firm, $opinion, $rating, $target)
                = array($analyst['date'], $analyst['name'],
                $analyst['symbol'], $analyst['firm'],
                $analyst['opinion'], $analyst['rating'],
                $analyst['target']
            );

            // validate all symbol date
            if (!($date instanceof \DateTime)) {
                throw $this->createNotFoundException(
                    'DateTime object error on form submit!'
                );
            }

            // get date from table
            $underlying = $symbolEM
                ->getRepository('JackImportBundle:Underlying')
                ->findOneBy(array('date' => $date));

            // date not found because not import
            if (!$underlying) {
                $error = 'Date [ ' . $date->format("m-d-Y") . ' ] ' .
                    'does not exist in ' . strtoupper($symbol)
                    . ' table, please import...';
            } else {
                // insert into table
                $event = new Event();

                // set data into object
                $event->setName($name);
                /** @var $underlying Underlying */
                $event->setUnderlyingid($underlying);

                $analyst = new Analyst();

                $analyst->setFirm($firm);
                $analyst->setOpinion($opinion);
                $analyst->setRating($rating);
                $analyst->setTarget($target);
                $analyst->setEventid($event);

                // insert into db
                $symbolEM->persist($event);
                $symbolEM->persist($analyst);
                $symbolEM->flush();

                $notice = "Event " . $date->format('m-d-Y') .
                    " [ $name ] with target price [ $target ] have been added!";
            }
        }

        return $this->render(
            'JackImportBundle:Event:addAnalyst.html.twig',
            array(
                'symbol' => strtoupper($symbol),
                'form' => $analystForm->createView(),
                'notice' => $notice,
                'error' => $error,
            )
        );
    }

    // TODO: private display earning, event, analyst table
    // addition to private add delete button
    private function getEarningData($symbol, $delete)
    {
        /*
         * 1. switch db
         * 2. get data from earning (format)
         * 3. put it into array
         * 4. return array
         */


        return 0;
    }

    public function removeEventAction()
    {
        return $this->render('JackImportBundle:Event:removeEvent.html.twig');
    }

    public function updateEventAction()
    {
        return $this->render('JackImportBundle:Event:updateEvent.html.twig');
    }

}
