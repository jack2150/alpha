<?php

namespace Jack\ImportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;

class ParallelController extends Controller
{
    public static $maxFilesInsert = 5;

    public static $host = 'jack';
    public static $url = '/app_dev.php/import/index/';


    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        // start first request and wait until it finish
        file_get_contents('http://' . self::$host . self::$url . 0);

        return $this->render(
            'JackImportBundle:Parallel:index.html.twig'
        );
    }

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function resultAction()
    {
        $folder = '..\web\import';

        // count total files in folder
        $finder = new Finder();

        /** @noinspection PhpUndefinedMethodInspection */
        $finder = $finder->files()
            ->in($folder)
            ->name('*.csv')
            ->size('> 1K')
            ->contains('thinkBack')
            ->sortByName()
            ->ignoreUnreadableDirs();

        $totalFiles = count($finder);

        // loop for each 5
        $countParallel = 0;
        for ($count = 0; $count <= $totalFiles; $count += self::$maxFilesInsert, $countParallel++) {
            $this->JobStartASync(self::$host, self::$url . $count);
        }

        return $this->render(
            'JackImportBundle:Parallel:result.html.twig',
            array(
                'countParallel' => $countParallel
            )
        );
    }


    /**
     * @param $server
     * @param $url
     * @param int $port
     * @param int $conn_timeout
     * @param int $rw_timeout
     * @return bool|resource
     */
    public function JobStartASync($server, $url, $port = 80, $conn_timeout = 30, $rw_timeout = 86400)
    {
        $errorNo = '';
        $errorStr = '';

        set_time_limit(0);

        $fp = fsockopen($server, $port, $errorNo, $errorStr, $conn_timeout);
        if (!$fp) {
            echo "$errorStr ($errorNo)<br />\n";
            return false;
        }
        $out = "GET $url HTTP/1.1\r\n";
        $out .= "Host: $server\r\n";
        $out .= "Connection: Close\r\n\r\n";

        stream_set_blocking($fp, false);
        stream_set_timeout($fp, $rw_timeout);
        fwrite($fp, $out);

        return $fp;
    }


}
