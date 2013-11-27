<?php

namespace Jack\EarningBundle\Controller;


class SweetSpotController extends EstimateController
{
    public function sweetSpotResultAction($symbol)
    {


        return $this->render(
            'JackEarningBundle:SweetSpot:result.html.twig'
        );


    }

    public function redirectAction()
    {
    }

}
