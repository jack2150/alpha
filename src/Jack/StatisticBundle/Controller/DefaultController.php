<?php

namespace Jack\StatisticBundle\Controller;

use Jack\FindBundle\Controller\FindController;

use Doctrine\ORM\Tools\SchemaTool;

/**
 * Class DefaultController
 * @package Jack\StatisticBundle\Controller
 */
class DefaultController extends FindController
{
    /*
    public function indexAction($name)
    {
        return $this->render('JackStatisticBundle:Default:index.html.twig', array('name' => $name));
    }
    */

    /**
     * @param $tableName
     * @return int
     */
    public function checkTableExist($tableName)
    {
        $schemaManager = $this->getDoctrine()->getConnection('symbol')->getSchemaManager();

        if ($schemaManager->tablesExist(array($tableName)) == true) {
            // table exists! ...
            return 1;
        }

        return 0;
    }

    // create a new table in db
    /**
     * @param $tableName
     */
    public function createTable($tableName)
    {
        // create new schema object
        $entityManager = $this->getDoctrine()->getManager('symbol');

        /** @var $entityManager \Doctrine\ORM\EntityManager */
        $tool = new SchemaTool($entityManager);

        $tool->createSchema(array(
            $entityManager->getClassMetadata($tableName),
        ));
    }


}
