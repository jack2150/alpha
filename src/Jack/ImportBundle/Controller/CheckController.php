<?php

namespace Jack\ImportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Jack\ImportBundle\Entity\Symbol;
use Acme\BlogBundle\Form\SymbolType;
use Jack\ImportBundle\Entity\Underlying;

/**
 * Class CheckController
 * @package Jack\ImportBundle\Controller
 * use to check quote imported into database is correct
 * range within first and last date is correct
 * number of cycle is correct and not blank foreign key
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
            /*
                ->add('name', 'choice', array(
                    'choices'   => $nameArray,
                    'required'  => true,
                    'multiple'  => false,
                ))
            */
            ->add('name', 'text')
            ->add('check', 'submit')
            ->getForm();

        $form->handleRequest($request);

        // TODO: try validate form using yml
        if ($form->isValid()) {
            // the validation passed, do something with the $author object

            $validator = $this->get('validator');
            $errors = $validator->validate($form);

            if (count($errors) == 0) {
                //return new Response(print_r($errors, true));
                echo $errors;
            }
            /*
            return $this->redirect(
                $this->generateUrl(
                    'jack_import_check_report',
                    array(
                        'symbol' => strtolower($form->getData()->getName())
                    )
                )
            );
            */
        }

        return $this->render('JackImportBundle:Check:form.html.twig',
            array(
                'form' => $form->createView(),
            )
        );
    }

    public function reportAction($symbol)
    {
        // TODO check date range


        return $this->render(
            'JackImportBundle:Check:report.html.twig',
            array('symbol' => $symbol)
        );
    }

}
