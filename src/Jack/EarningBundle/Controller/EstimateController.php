<?php

namespace Jack\EarningBundle\Controller;

use Jack\EarningBundle\Controller\DefaultController;

class EstimateController extends DefaultController
{
    public function estimateResultAction()
    {
        // todo: next price estimation

        return $this->render(
            'JackEarningBundle:Estimate:result.html.twig'
        );
    }

}
