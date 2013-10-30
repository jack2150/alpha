<?php

namespace Jack\ImportBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Class DefaultController
 * @package Jack\ImportBundle\Controller
 */
class DefaultController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $files = $this->formatFiles(
            $this->getFiles("../web/import")
        );

        $this->formatLines($files);

        //$this->interpretFiles($files);


        return $this->render(
            'JackImportBundle:Default:index.html.twig',
            array(
                'name' => 'jack ong'
            )
        );
    }

    /**
     * @param string $folder
     * import quote files directory
     * @return array $files
     * return an array of files list
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * folder not exist, no csv files, no file size > 1KB, empty folder
     * no unreadable dir, must contains world 'thinkBack'
     */
    public function getFiles($folder)
    {
        $finder = new Finder();

        $finder = $finder->files()
            ->in($folder)
            ->name('*.csv')
            ->size('> 1K')
            ->contains('thinkBack')
            ->sortByName()
            ->ignoreUnreadableDirs();

        if (count($finder) == 0) {
            throw $this->createNotFoundException(
                'Folder is empty or does not have correct csv files!'
            );
        }

        $files = Array();

        foreach ($finder as $file) {
            if (!($file instanceof SplFileInfo)) {
                throw $this->createNotFoundException(
                    "Passing invalid object (not SplFileInf) type!"
                );
            }

            $files[] = $file->getContents();
        }

        return $files;
    }


    /**
     * @param array string $files
     * array of unusable plain file contents from cvs
     * @return array string $new_files
     * array of usable formatted file contents with format
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error if files do not set
     */
    public function formatFiles($files)
    {
        $formatFiles = Array();

        if (!isset($files)) {
            throw $this->createNotFoundException(
                'Files content error!'
            );
        }

        foreach ($files as $file) {
            // replace all '--', '-.', ',.'
            $file = str_replace(
                array("--", "-.", "+.", ",."),
                array("0", "-0.", "+0.", ",0."),
                $file
            );

            // replace all double ,, in files
            // including start and end
            while (strpos($file, ",,") != false) {
                $file = str_replace
                (
                    array(',,', "\n,", ",\r"),
                    array(',0,', "\n0,", ",0\r"),
                    $file
                );
            }

            // replace all double comma values
            if (preg_match_all('/".*?"|\'.*?\'/',
                $file, $doubleQuotesValues, PREG_PATTERN_ORDER)
            ) {
                foreach ($doubleQuotesValues[0] as $match_value) {
                    $replace_value = str_replace(
                        array("\"", ","),
                        '',
                        $match_value
                    );

                    $file = str_replace($match_value, $replace_value, $file);
                }
            }

            $formatFiles[] = $file;
        }

        return $formatFiles;
    }

    public function formatLines($files)
    {
        $formatFiles = Array();

        foreach ($files as $file) {
            // remove all empty line and make an array
            $fileContents = array_values(array_filter(preg_split('/$\R?^/m', $file), "trim"));

            // remove other related column header
            $formatFile = "";
            foreach ($fileContents as $fileSingleLine) {
                if (!(strstr($fileSingleLine, 'UNDERLYING') ||
                    strstr($fileSingleLine, 'Last') ||
                    strstr($fileSingleLine, 'Theo') ||
                    strstr($fileSingleLine, 'Delta'))
                ) {
                    //echo $fileSingleLine ."<br>";
                    $formatFile .= $fileSingleLine . "\n";
                }

                // TODO: merge 1st and 2nd line into underlying
                if (strstr($fileSingleLine, "thinkBack")) {


                }


            }


            $formatFiles[] = $formatFile;
            echo $formatFile;
        }

        return $formatFiles;
    }


    // TODO: imcomplete interpret data
    public function interpretFiles($files)
    {
        foreach ($files as $file) {
            $fileContents = preg_split('/$\R?^/m', $file);

            //print_r($fileContents);

            // interpret using header
            $symbolArray = Array();
            foreach ($fileContents as $fileRow => $fileSingleLine) {
                // find first line
                if (strstr($fileSingleLine, "thinkBack") && $fileRow == 0) {
                    $underlyingArray = preg_split("/[\s,]+/", trim($fileSingleLine));

                    $symbolArray = array(
                        'name' => $underlyingArray[7],
                        'date' => date(
                            'Y/m/d',
                            strtotime(
                                '-1 days',
                                strtotime($underlyingArray[9])
                            )
                        )
                    );
                } // find fourth line
                elseif ($fileRow == 4) {
                    $underlyingArray = preg_split("/[\s,]+/", trim($fileSingleLine));

                    $symbolArray = array_merge(
                        $symbolArray,
                        array(
                            'last' => $underlyingArray[0],
                            'netchange' => $underlyingArray[1],
                            'volume' => $underlyingArray[2],
                            'open' => $underlyingArray[3],
                            'high' => $underlyingArray[4],
                            'low' => $underlyingArray[5],
                        )
                    );

                    print_r($symbolArray);
                }


                // TODO: continue option cycle, contract and chain


            }


        }


    }


}
