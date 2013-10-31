<?php

namespace Jack\ImportBundle\Controller;

use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

use Jack\ImportBundle\Entity\Underlying;
use Jack\ImportBundle\Entity\Cycle;
use Jack\ImportBundle\Entity\Strike;
use Jack\ImportBundle\Entity\Chain;


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

        $files = $this->formatLines($files);

        $object = $this->filesToObject($files);

        //$this->interpretFiles($files);


        return $this->render(
            'JackImportBundle:Default:index.html.twig',
            array(
                'name' => 'jack ong'
            )
        );
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

        /** @noinspection PhpUndefinedMethodInspection */
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
     * @param $files
     * input array of usable formatted files
     * @return array
     * return a files array that object ready
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error if cvs lines is not valid
     */
    public function formatLines($files)
    {
        $_monthArray = array(
            'JAN', 'FEB', 'MAR', 'APR', 'MAY', 'JUN', 'JUL', 'AUG', 'SEP', 'OCT', 'NOV', 'DEC'
        );

        $formatFiles = Array();

        foreach ($files as $file) {
            // remove all empty line and make an array
            $fileContents = array_values(array_filter(preg_split('/$\R?^/m', $file), "trim"));

            // remove other related column header

            $formatFile = "";
            $includeCycle = 0;
            foreach ($fileContents as $fileRow => $fileSingleLine) {
                //echo nl2br("$fileRow => $fileSingleLine");

                if (!(strstr($fileSingleLine, 'UNDERLYING') ||
                    strstr($fileSingleLine, 'Last') ||
                    strstr($fileSingleLine, 'Theo') ||
                    strstr($fileSingleLine, 'Delta') ||
                    $fileRow == 6)
                ) {
                    //echo nl2br($fileRow ."=>". $fileSingleLine);

                    // if first line, then do format it and do not add \n new line
                    // combine second line to make a underlying line
                    if ($fileRow == 0) {
                        //$underlyingArray = preg_split("/[\s,]+/", trim($fileSingleLine));
                        $lineArray = explode(" ", trim($fileSingleLine));

                        $formatFile .= "UNDERLYING=>" . $lineArray[7] . "," .
                            date('Y/m/d', strtotime(
                                '-1 days', strtotime($lineArray[9])
                            )) . ",";
                    } elseif ($fileRow == 3) {
                        // merge line 3 with line 0 to create symbol line
                        $formatFile .= trim($fileSingleLine) . "\n";

                        //echo $formatFile;
                    } elseif (in_array(strtoupper(substr($fileSingleLine, 0, 3)), $_monthArray)) {
                        // starting a line with month, a new cycle
                        // make line into array
                        $cycleArray = explode(" ", str_replace(
                            array('(', ')',), '', $fileSingleLine
                        ));

                        // check weeklys or mini exists
                        if (!isset($cycleArray[6])) {
                            $cycleArray[6] = 'Standards';
                        }

                        // merge files data
                        // also check cycle is not expired
                        if ($cycleArray[3] > -1 && count($cycleArray) > 4) {
                            //$currentSymbol."=>".
                            $formatFile .= "CYCLE=>" .
                                $cycleArray[0] . "," .
                                $cycleArray[1] . "," .
                                $cycleArray[3] . "," .
                                trim($cycleArray[5]) . "," .
                                $cycleArray[6] . "\n";

                            $includeCycle = 1;
                        } else {
                            $includeCycle = 0;
                        }

                        // set current cycle
                        //$currentCycle = $cycleArray[0].",".$cycleArray[1];
                    } elseif ($includeCycle) {
                        // cycle must not expired

                        //$formatFile .= $fileSingleLine . "\n";
                        // starting chain data row
                        $strikeArray = explode(',', $fileSingleLine);

                        //echo count($strikeArray)."<br>";
                        // array must have 36 values count
                        if (count($strikeArray) == 34) {
                            $callStrike = "CALL=>" . $strikeArray[17] . "=>" .
                                $strikeArray[0] . "," .
                                $strikeArray[1] . "," .
                                $strikeArray[2] . "," .
                                $strikeArray[3] . "," .
                                $strikeArray[4] . "," .
                                $strikeArray[5] . "," .
                                $strikeArray[6] . "," .
                                $strikeArray[7] . "," .
                                $strikeArray[8] . "," .
                                $strikeArray[9] . "," .
                                $strikeArray[10] . "," .
                                $strikeArray[11] . "," .
                                $strikeArray[12] . "," .
                                $strikeArray[13] . "," .
                                $strikeArray[14] . "," .
                                $strikeArray[15];

                            $putStrike = "PUT=>" . $strikeArray[17] . "=>" .
                                $strikeArray[20] . "," .
                                $strikeArray[21] . "," .
                                $strikeArray[22] . "," .
                                $strikeArray[23] . "," .
                                $strikeArray[24] . "," .
                                $strikeArray[25] . "," .
                                $strikeArray[26] . "," .
                                $strikeArray[27] . "," .
                                $strikeArray[28] . "," .
                                $strikeArray[29] . "," .
                                $strikeArray[30] . "," .
                                $strikeArray[31] . "," .
                                $strikeArray[32] . "," .
                                trim($strikeArray[33]) . "," .
                                $strikeArray[18] . "," .
                                $strikeArray[19];

                            $formatFile .= $callStrike . "\n" . $putStrike . "\n";
                            //echo $callStrike."<br>".$putStrike."<br><br>";
                        } else {
                            // incorrect chain data row
                            throw $this->createNotFoundException(
                                "Incorrect strike+chain data row on
                                (Row $fileRow => Count " .
                                count($strikeArray) . " => Data " .
                                $fileSingleLine . ")"
                            );
                        }
                    }
                }
            }


            // merge the formatFile into array
            $formatFiles[] = $formatFile;

            //echo nl2br($formatFile);
        }

        return $formatFiles;
    }

    /**
     * @param $files
     * input formatted clean files array that line is formatted too
     * @return array|ArrayCollection
     * return a mix of underlying object, cycle array object
     * strike array object, and chain array object with foreign key connected
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error found if invalid object type, invalid array count
     */
    public function filesToObject($files)
    {
        // contain all underlying, cycle, strike and chain
        $quoteArray = new ArrayCollection();

        foreach ($files as $file) {
            // reset all variable
            $underlyingObject = "";
            $cycleObject = "";
            $strikeObject = "";

            // define cycle, strike, chain object array
            $cycleObjectArray = new ArrayCollection();
            $strikeObjectArray = new ArrayCollection();
            $chainObjectArray = new ArrayCollection();

            $dayUntilExpire = "";

            // split the file into array containt every line array
            $fileLines = explode("\n", $file);

            foreach ($fileLines as $fileRow => $fileLine) {
                // split the line into array
                $fileLineArray = explode("=>", $fileLine);

                if ($fileLineArray[0] == 'UNDERLYING') {
                    // check correct no of array count
                    $underlyingArray = explode(',', $fileLineArray[1]);

                    if (count($underlyingArray) == 8) {
                        // underlying data line
                        $underlyingObject = new Underlying();

                        $underlyingObject->setName($underlyingArray[0]);
                        $underlyingObject->setDate($underlyingArray[1]);
                        $underlyingObject->setLast($underlyingArray[2]);
                        $underlyingObject->setNetchange($underlyingArray[3]);
                        $underlyingObject->setVolume($underlyingArray[4]);
                        $underlyingObject->setOpen($underlyingArray[5]);
                        $underlyingObject->setHigh($underlyingArray[6]);
                        $underlyingObject->setLow($underlyingArray[7]);
                    } else {
                        // error cycle data row
                        throw $this->createNotFoundException(
                            "Incorrect underlying data row on
                                (Row $fileRow => Count " .
                            count($underlyingArray) . " => Data " .
                            $fileLine . ")"
                        );
                    }
                } elseif ($fileLineArray[0] == 'CYCLE') {
                    // cycle data line
                    $cycleArray = explode(',', $fileLineArray[1]);

                    if (count($cycleArray) == 5) {
                        $cycleObject = new Cycle();

                        // get current date from underlying
                        $currentDate = $underlyingObject->getDate();
                        $dayUntilExpire = $cycleArray[2];

                        // set data into cycle object
                        $cycleObject->setExpiremonth($cycleArray[0]);
                        $cycleObject->setExpireyear($cycleArray[1]);
                        $cycleObject->setExpiredate(new \DateTime(
                            $currentDate . "+ " . $cycleArray[2] . " days"));
                        $cycleObject->setContractright($cycleArray[3]);
                        $cycleObject->setIsweekly(
                            trim($cycleArray[4]) == 'Weeklys' ? 1 : 0);
                        $cycleObject->setIsmini(
                            trim($cycleArray[4]) == 'Mini' ? 1 : 0);

                        // set it into strike object array
                        $cycleObjectArray[] = clone $cycleObject;
                    } else {
                        // error underlying data row
                        throw $this->createNotFoundException(
                            "Incorrect cycle data row on
                                (Row $fileRow => Count " .
                            count($cycleArray) . " => Data " .
                            $fileLine . ")"
                        );
                    }
                } elseif ($fileLineArray[0] == 'CALL' || $fileLineArray[0] == 'PUT') {
                    // strike and chain data line
                    // check strike is valid
                    if (is_numeric($fileLineArray[1])) {
                        // if exist use the existing strike object
                        // if not exist create new strike object
                        $duplicate = 0;

                        foreach ($strikeObjectArray as $tempStrikeObject) {
                            if ($tempStrikeObject instanceof Strike) {
                                if (($tempStrikeObject->getCategory() == $fileLineArray[0]
                                    && $tempStrikeObject->getStrike() == $fileLineArray[1])
                                ) {
                                    $duplicate = 1;

                                    // use the existing $strike object
                                    $strikeObject = $tempStrikeObject;
                                }
                            }
                        }

                        if (!$duplicate) {
                            // no duplicate found, create new strike object
                            $strikeObject = new Strike();

                            $strikeObject->setCategory($fileLineArray[0]);
                            $strikeObject->setStrike($fileLineArray[1]);

                            // set it into strike object array
                            $strikeObjectArray[] = clone $strikeObject;
                        }
                    } else {
                        // error strike and chain data row
                        throw $this->createNotFoundException(
                            "Incorrect strike and chain data row on
                                (Row $fileRow => Count " .
                            count($fileLineArray) . " => Data " .
                            $fileLine . ")"
                        );
                    }

                    $chainArray = explode(',', $fileLineArray[2]);
                    if (count($chainArray) == 16) {
                        $chainObject = new Chain();

                        // greek
                        $chainObject->setDelta($chainArray[0]);
                        $chainObject->setGamma($chainArray[1]);
                        $chainObject->setTheta($chainArray[2]);
                        $chainObject->setVega($chainArray[3]);
                        $chainObject->setRho($chainArray[4]);

                        // impl, prob
                        $chainObject->setTheo($chainArray[5]);
                        $chainObject->setImpl($chainArray[6]);
                        $chainObject->setProbitm($chainArray[7]);
                        $chainObject->setProbotm($chainArray[8]);
                        $chainObject->setProbtouch($chainArray[9]);

                        // volume, open interest
                        $chainObject->setVolume($chainArray[10]);
                        $chainObject->setOpeninterest($chainArray[11]);

                        // intrinsic, extrinsic
                        $chainObject->setIntrinsic($chainArray[12]);
                        $chainObject->setExtrinsic($chainArray[13]);

                        // bid, ask
                        $chainObject->setBid($chainArray[14]);
                        $chainObject->setAsk($chainArray[15]);

                        // set dte
                        $chainObject->setDte($dayUntilExpire);

                        // set foreign key, underlying, cycle, strike
                        $chainObject->setUnderlyingid($underlyingObject);
                        $chainObject->setCycleid($cycleObject);
                        $chainObject->setStrikeid($strikeObject);

                        // set it into chain object array
                        $chainObjectArray[] = clone $chainObject;
                    } else {
                        // error strike and chain data row
                        throw $this->createNotFoundException(
                            "Incorrect strike and chain data row on
                                (Row $fileRow => Count " .
                            count($chainArray) . " => Data " .
                            $fileLine . ")"
                        );
                    }
                }
            }

            // assign all object into an array
            $quoteArray[] = array(
                $underlyingObject,
                $cycleObjectArray,
                $strikeObjectArray,
                $chainObjectArray,
            );
        }

        return $quoteArray;
    }
}
