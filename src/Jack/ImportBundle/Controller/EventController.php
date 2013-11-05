<?php

namespace Jack\ImportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Jack\ImportBundle\Entity\Symbol;
use Jack\ImportBundle\Entity\Underlying;
use Jack\ImportBundle\Entity\Event;

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
        $select = array('symbol' => null, 'action' => 'addEvent');
        $form = $this->createFormBuilder($select)
            ->add('symbol', 'choice', array(
                'choices' => $symbolNames,
                'required' => true,
                'multiple' => false,
            ))
            ->add('action', 'choice', array(
                'choices' => array(
                    'addEvent' => 'Add event',
                    'updateEvent' => 'Update event',
                    'removeEvent' => 'Remove event',
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
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    public function addEventAction($symbol, Request $request)
    {
        $notice = "";
        $error = "";

        // add new event into database
        //$event = new Event();
        $event = array(
            //'date' => new \DateTime("today"),
            'date' => new \DateTime("2013-10-30"),
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

        // TODO: earning form, analyst form,
        // create new page to handle both, new url too
        // earning, analyst table validate


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
                    " [$name - $context] have been added into databases!";
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


    public function showEventAction($symbol = 'fb')
    {
        $this->get('jack_service.fastdb')->switchSymbolDb('fb');

        // then get data from tables
        $symbolEM = $this->getDoctrine()->getManager('symbol');
        $underlying = $symbolEM
            ->getRepository('JackImportBundle:Underlying')
            ->findAll();

        print_r($underlying);

        return $this->render('JackImportBundle:Event:showEvent.html.twig');
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
