<?php

namespace Jack\FindBundle\Controller;

use Symfony\Component\HttpFoundation\Request;

use Jack\FindBundle\Controller\FindController;

/**
 * Class StrikeController
 * @package Jack\FindBundle\Controller
 */
class StrikeController extends FindController
{
    /**
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction(Request $request)
    {
        $findStrikeData = array(
            'symbol' => '',
            'action' => '------',
        );

        $findStrikeForm = $this->createFormBuilder($findStrikeData)
            ->add('symbol', 'choice', array(
                'choices' => $this->getSymbolArray(),
                'required' => true,
                'multiple' => false,
            ))
            ->add('action', 'choice', array(
                'choices' => array(
                    'findByCategory' => 'Find By Category',
                    'findAll' => 'Find All',
                ),
                'required' => true,
                'multiple' => false,
            ))
            ->add('find', 'submit')
            ->getForm();

        $findStrikeForm->handleRequest($request);

        if ($findStrikeForm->isValid()) {
            $findStrikeData = $findStrikeForm->getData();

            $symbol = $findStrikeData['symbol'];
            $action = $findStrikeData['action'];

            $returnUrl = '';
            $params = array();
            switch ($action) {
                case 'findByCategory':
                    $returnUrl = 'jack_find_strike_result_findbycategory';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower($action),
                        'category' => 'any',
                    );
                    break;

                case 'findAll':
                default:
                    $returnUrl = 'jack_find_strike_result_findall';
                    $params = array(
                        'symbol' => strtolower($symbol),
                        'action' => strtolower($action),
                    );
                    break;
            }

            return $this->redirect(
                $this->generateUrl(
                    $returnUrl,
                    $params
                )
            );


        }

        return $this->render(
            'JackFindBundle:Strike:index.html.twig',
            array(
                'findStrikeForm' => $findStrikeForm->createView(),
            )
        );
    }


    public function resultAction($symbol, $action, $category = 0)
    {
        $this->symbol = $symbol;
        $this->getSymbolObject($symbol);

        $this->get('jack_service.fastdb')->switchSymbolDb($symbol);

        $searchLinks = array();
        switch ($action) {
            case 'findByCategory':
                $searchName = "Find By Category";
                if ($category == 'call' || $category == 'put') {
                    $strikes = $this->findStrikeByCategory($category, 'desc');
                } else {
                    $strikes = $this->findStrikeAll('desc');
                }
                $linkType = 'findbycategory';
                $searchLinks = $this->getCategoryLinks(
                    $category, 'jack_find_strike_result_findbycategory', 1
                );


                break;

            case 'findall':
            default:
                $searchName = "Find All";
                $strikes = $this->findStrikeAll('desc');
                $linkType = 'findall';
        }

        // count underlying
        $resultCount = 0;
        if (!empty($strikes)) {
            $resultCount = count($strikes);
        }

        return $this->render(
            'JackFindBundle:Strike:result.html.twig',
            array(
                'symbol' => $symbol,
                'searchName' => $searchName,
                'strikes' => $strikes,
                'resultCount' => $resultCount,
                'linkType' => $linkType,
                'searchLinks' => $searchLinks,
            )
        );
    }

    /**
     * @param $currentCategory
     * selected category type, in 'call', 'put', 'any'
     * @param $returnURL
     * href url use for link
     * @param int $useAny
     * use any selection or not
     * @return array
     * a list of strike objects from db
     */
    private function getCategoryLinks($currentCategory, $returnURL, $useAny = 0)
    {
        $categoryValue = array(1 => 'call', '2' => 'put');
        $categoryLinkArray = Array();

        for ($category = 1; $category <= 2; $category++) {
            $useCategory = $categoryValue[$category];
            $useUrl = $returnURL;

            if ($useCategory == $currentCategory) {
                $useUrl = '#';
            }

            $categoryLinkArray[] = array(
                'category' => $useCategory,
                'url' => $useUrl,
            );
        }

        if ($useAny) {
            $useUrl = $returnURL;
            if ($currentCategory == 'any') {
                $useUrl = '#';
            }

            $categoryLinkArray[] = array(
                'category' => 'any',
                'url' => $useUrl,
            );
        }

        return $categoryLinkArray;
    }

    // TODO: find chain by probOTM, probITM, probTouch
    // findOneChainByProbOTM, findOneChainByProbITM, findOneChainByProbTouch

    // TODO: find chain by volume, open interest (condition)


}
