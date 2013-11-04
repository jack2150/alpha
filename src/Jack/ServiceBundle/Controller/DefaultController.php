<?php
/**
 * REMEMBER ALWAYS CLEAR CACHE AFTER EDIT THIS PAGE
 */

namespace Jack\ServiceBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\DependencyInjection\ContainerInterface as Container;

/**
 * Class DefaultService
 * @package Jack\ServiceBundle
 */
class DefaultController
{
    private $container;

    // refer container into use
    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    /**
     * @param $dbName
     */
    public function switchSymbolDb($dbName)
    {
        $connectionFactory = $this->container
            ->get('doctrine.dbal.connection_factory');
        $connection = $connectionFactory
            ->createConnection(
                array(
                    'driver' => $this->container->getParameter('database_driver'),
                    'host' => $this->container->getParameter('database_host'),
                    'user' => $this->container->getParameter('database_user'),
                    'password' => $this->container->getParameter('database_password'),
                    'dbname' => $dbName,
                )
            );

        $this->container->set('doctrine.dbal.symbol_connection', $connection);
    }
}
