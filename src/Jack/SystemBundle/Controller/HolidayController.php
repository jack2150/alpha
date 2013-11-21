<?php

namespace Jack\SystemBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

use Jack\ImportBundle\Entity\Holiday;

class HolidayController extends Controller
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $notice = "";
        $error = "";

        // create new holiday object
        $holiday = new Holiday();

        // set date
        $holiday->setDate(new \DateTime("today"));

        $holidayForm = $this->createFormBuilder($holiday)
            // only work on chrome but not firefox or ie
            ->add('date', 'date', array(
                'widget' => 'single_text',
                'format' => 'yyyy-MM-dd',
                'required' => true,
            ))
            ->add('name', 'text',
                array('required' => true)
            )
            ->add('save', 'submit')
            ->getForm();

        // get request
        $holidayForm->handleRequest($request);

        if ($holidayForm->isValid()) {

            $symbolEM = $this->getDoctrine()->getManager('system');

            $symbolEM->persist($holiday);
            $symbolEM->flush();

            $notice = "Holiday " . $holiday->getDate()->format('M-d-Y') .
                " [ " . $holiday->getName() . " ] have been added!";
        }

        return $this->render(
            'JackSystemBundle:Holiday:addHoliday.html.twig',
            array(
                'form' => $holidayForm->createView(),
                'notice' => $notice,
                'error' => $error,
                'holidays' => $this->show(),
            )
        );
    }

    /**
     * @return array
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     */
    private function show()
    {
        $holidays = $this->getDoctrine('system')
            ->getRepository('JackImportBundle:Holiday')
            ->findBy(array(), array('date' => 'desc'));

        $holidayArray = array();
        $formatHoliday = array();
        foreach ($holidays as $holiday) {
            if (!$holiday instanceof Holiday) {
                throw $this->createNotFoundException(
                    'Error [ holiday ] data object from database!'
                );
            }

            $formatHoliday['id'] = $holiday->getId();
            $formatHoliday['date'] = $holiday->getDate()->format("M-d-Y");
            $formatHoliday['name'] = $holiday->getName();

            // generate delete id
            $formatHoliday['delete'] = $this->generateUrl(
                'jack_system_holiday_remove',
                array(
                    'id' => $holiday->getId()
                )
            );

            $holidayArray[] = $formatHoliday;
        }

        return $holidayArray;
    }


    public function removeAction($id)
    {
        $symbolEM = $this->getDoctrine()->getManager('system');
        $holiday = $symbolEM
            ->getRepository('JackImportBundle:Holiday')
            ->findOneBy(array('id' => $id));

        if (!$holiday) {
            throw $this->createNotFoundException(
                "Holiday id [ $id ]  do not found on database!"
            );
        }

        $symbolEM->remove($holiday);
        $symbolEM->flush();

        return $this->redirect($this->generateUrl('jack_system_holiday'));
    }
}
