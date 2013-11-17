<?php

namespace Jack\ImportBundle\Controller;

// Doctrine
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\Tools\SchemaTool;
use Doctrine\ORM\EntityManager;
use Doctrine\DBAL\Driver\PDOMySql\Driver;
use Doctrine\DBAL\Connection;

// Symfony
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;
use Symfony\Component\Filesystem\Filesystem;

// Entity
use Jack\ImportBundle\Entity\Underlying;
use Jack\ImportBundle\Entity\Cycle;
use Jack\ImportBundle\Entity\Strike;
use Jack\ImportBundle\Entity\Chain;
use Jack\ImportBundle\Entity\Symbol;
use Jack\ImportBundle\Entity\Holiday;

/**
 * Class DefaultController
 * @package Jack\ImportBundle\Controller
 */
class DefaultController extends Controller
{
    public static $maxFilesInsert = 5;

    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        $importDirectory = '..\web\import';

        $result = $this->getFiles($importDirectory);

        $files = $result[0];
        $currentSymbol = $result[1];
        $remainSymbols = $result[2];
        $importedPaths = $result[3];
        $warning = "";

        // format each line
        $files = $this->formatLines(
            $this->formatFiles($files)
        );

        // convert files into object
        $object = $this->filesToObject($files);

        // insert object into database
        $warning = $this->insertObjectToDb($object);

        // remove files from folder
        $this->removeImportedFiles($importedPaths);

        // update symbol table in system
        $this->updateSymbolTable($currentSymbol, $importedPaths);


        // check still have other remaining underlying
        $importURL = "";
        if (count($remainSymbols)) {
            // repeating until no more files in import folder
            $importURL = $this->generateUrl('jack_import_default');
        }

        $templateArray = array(
            'current_symbol' => $currentSymbol,
            'remaining_symbols' => count($remainSymbols) ?
                implode(", ", $remainSymbols) : 0,
            'imported_paths' => $importedPaths,
            'import_url' => $importURL,
            'warning' => $warning,
        );

        return $this->render(
            'JackImportBundle:Default:index.html.twig',
            $templateArray
        );
    }


    /**
     * @param string $folder
     * import quote files directory
     * @return array of (array $files, array $remainSymbol)
     * return an array of files list and remaining symbols
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * folder not exist, no csv files, no file size > 1KB, empty folder
     * no unreadable dir, must contains world 'thinkBack'
     */
    private function getFiles($folder)
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


        // get all holiday data
        $systemEM = $this->getDoctrine()->getManager('system');

        // highlight holiday date from array
        $holidays = $systemEM->getRepository('JackImportBundle:Holiday')
            ->findAll();

        if ($holidays) {
            foreach ($holidays as $holiday) {
                if (!$holiday instanceof Holiday) {
                    throw $this->createNotFoundException(
                        'Error [ Holiday ] object from entity manager!'
                    );
                }

                $holidayDate[] = $holiday->getDate()->format('Y-m-d');
            }
            unset($holidays);
        }

        // check similar underlying symbol using file name
        //
        $files = Array();
        $notFirstFile = 0;
        $firstSymbol = "";
        $remainSymbols = Array();
        $fileLinks = Array();
        $maxFilesInsert = self::$maxFilesInsert;

        foreach ($finder as $file) {
            if (!($file instanceof SplFileInfo)) {
                throw $this->createNotFoundException(
                    "Passing invalid object (not SplFileInf) type!"
                );
            }

            // only get similar underlying symbol
            $fileSymbol = substr(
                $file->getFilename(), 33,
                strpos($file->getFilename(), '.') - 33
            );

            if ($notFirstFile == 0) {
                $firstSymbol = substr(
                    $file->getFilename(), 33,
                    strpos($file->getFilename(), '.') - 33
                );

                // only run once for first files
                $notFirstFile++;
            }

            $currentDate = substr(basename($file->getFilename()), 0, 10);
            $weekday = date('l', strtotime($currentDate));

            $notWeekend = 0;
            if ($weekday != 'Saturday' && $weekday != 'Sunday') {
                $notWeekend = 1;
            }

            // now check is not holiday
            // open system table, get all holiday
            $notHoliday = 1;
            if (isset($holidayDate)) {
                foreach ($holidayDate as $holiday) {
                    if ($currentDate == $holiday) {
                        $notHoliday = 0;
                    }
                }
            }

            // if same symbol then add content into files array
            // then delete the files in the import folder
            if ($notWeekend && $notHoliday) {
                if ($fileSymbol == $firstSymbol && $maxFilesInsert) {
                    // set content into files
                    $files[] = $file->getContents();

                    // get link path for display
                    $fileLinks[] = $file;

                    // max 10 files each insert
                    $maxFilesInsert--;
                } else {
                    $remainSymbols[] = $fileSymbol;
                }
            }
        }

        return array($files, $firstSymbol, array_unique($remainSymbols), $fileLinks);
    }

    /**
     * @param $importedFiles
     * input the imported files path to be remove
     */
    private function removeImportedFiles($importedFiles)
    {
        // remove the files
        $fileSystem = new Filesystem();
        foreach ($importedFiles as $importedFile) {
            $fileSystem->remove($importedFile);
        }
    }

    /**
     * @param array string $files
     * array of unusable plain file contents from cvs
     * @return array string $new_files
     * array of usable formatted file contents with format
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error if files do not set
     */
    private function formatFiles($files)
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
     * @param $files
     * input array of usable formatted files
     * @return array
     * return a files array that object ready
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error if cvs lines is not valid
     */
    private function formatLines($files)
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
     * important note, only single underlying per import
     */
    private function filesToObject($files)
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
                        $underlyingObject->setDate(
                            new \DateTime($underlyingArray[1])
                        );
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
                        /** @noinspection PhpUndefinedMethodInspection */
                        $cycleObject->setExpiredate(new \DateTime(
                            date("Y-m-d", $currentDate->getTimestamp())
                            . "+ " . $cycleArray[2] . " days"));
                        $cycleObject->setContractright($cycleArray[3]);
                        $cycleObject->setIsweekly(
                            trim($cycleArray[4]) == 'Weeklys' ? 1 : 0);
                        $cycleObject->setIsmini(
                            trim($cycleArray[4]) == 'Mini' ? 1 : 0);

                        // set it into strike object array
                        $cycleObjectArray[] = $cycleObject;
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
                                    && $tempStrikeObject->getPrice() == $fileLineArray[1])
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
                            $strikeObject->setPrice($fileLineArray[1]);

                            // set it into strike object array
                            $strikeObjectArray[] = $strikeObject;
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
                        $chainObjectArray[] = $chainObject;
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

    /**
     * @param $quoteObjectArray
     * a set of multi-dimension array contain
     * first dimension is array for each files (no)
     * then it have 4 different array inside it
     * 1. underlying object
     * 2. array of cycle object
     * 3. array of strike object
     * 4. array of chain object
     * @return array
     * return warning if duplicate underlying date found
     * @throws \Symfony\Component\HttpKernel\Exception\NotFoundHttpException
     * error when any data object, insertion, entitymanager
     * or anything else related to insert will trigger this
     */

    private function insertObjectToDb($quoteObjectArray)
    {
        date_default_timezone_set('UTC');

        //set warning empty
        $warning = Array();

        $entityManager = $this->getDoctrine()->getManager('symbol');

        // loop for each files object that contain 4 objects
        foreach ($quoteObjectArray as $quoteObject) {
            $doInsert = 1;

            // loop quoteObject into each 1 => object, 2,3,4 => array objects
            // 1 underlying object, 2 array of cycle object
            // 3 array of strike object, 4 array of chain object
            foreach ($quoteObject as $objectKey => $objectArray) {
                if ($objectKey == 0) {
                    // underlying object is here
                    // check database exist

                    // open symbol table in system em
                    $underlyingObject = $objectArray;
                    if ($underlyingObject instanceof Underlying) {
                        /** @noinspection PhpUndefinedMethodInspection */
                        $symbol = $this->getDoctrine()
                            ->getRepository('JackImportBundle:Symbol', 'system')
                            ->findOneByName(strtoupper($underlyingObject->getName()));

                        if (!$symbol) {
                            // symbol not found in tables
                            // insert new row into symbol table in system db
                            $this->insertSymbol($underlyingObject->getName());

                            // create new database
                            $this->createNewDb($underlyingObject->getName());
                        } else {
                            // use the new connection with old db name
                            $this->switchDatabase(
                                $underlyingObject->getName(),
                                $this->container->getParameter('database_user'),
                                $this->container->getParameter('database_password')
                            );
                        }

                        // if exist, then skip this insert and other object, to next file
                        $existUnderlying = $this->findOneByArray(
                            array('date' => $underlyingObject->getDate()),
                            'Underlying'
                        );

                        if ($existUnderlying) {
                            // skip all object insert including underlying
                            $doInsert = 0;

                            if ($existUnderlying instanceof Underlying) {
                                $warning[] = $existUnderlying->getName()
                                    . ": " . $existUnderlying->getDate()->format("Y-m-d");
                            }
                        } else {
                            // insert into database for underlying
                            $entityManager->persist($underlyingObject);
                            $entityManager->flush();
                        }


                    } else {
                        // error if first item is not underlying object
                        throw $this->createNotFoundException(
                            "Invalid Underlying object (Key $objectKey)"
                        );
                    }
                } elseif ($objectKey == 1 && $doInsert) {
                    // cycle object is here
                    // loop all the cycle object
                    foreach ($objectArray as &$cycleObject) {
                        // check if it exist
                        if ($cycleObject instanceof Cycle) {
                            // find exact same cycle
                            $existCycle = $this->findOneByArray(
                                array(
                                    'expiredate' => $cycleObject->getExpiredate(),
                                    'expiremonth' => $cycleObject->getExpiremonth(),
                                    'expireyear' => $cycleObject->getExpireyear(),
                                    'contractright' => $cycleObject->getContractright(),
                                    'ismini' => $cycleObject->getIsmini(),
                                    'isweekly' => $cycleObject->getIsweekly()
                                ),
                                'Cycle'
                            );

                            if (!$existCycle) {
                                // if not exist add into persist query
                                $entityManager->persist($cycleObject);
                            }
                        } else {
                            // error if first item is not cycle object
                            throw $this->createNotFoundException(
                                "Invalid Cycle object (Key $objectKey)"
                            );
                        }

                        // insert into database for cycle
                        $entityManager->flush();
                    }
                } elseif ($objectKey == 2 && $doInsert) {
                    // strike object is here
                    // loop all the strike object
                    foreach ($objectArray as &$strikeObject) {
                        // check if it exist
                        if ($strikeObject instanceof Strike) {
                            // find exact same strike
                            $existStrike = $this->findOneByArray(
                                array(
                                    'category' => $strikeObject->getCategory(),
                                    'price' => $strikeObject->getPrice(),
                                ),
                                'Strike'
                            );

                            if (!$existStrike) {
                                // if not exist add into persist query
                                $entityManager->persist($strikeObject);
                            }
                        } else {
                            throw $this->createNotFoundException(
                                "Invalid Strike object (Key $objectKey)"
                            );
                        }
                    }

                    // insert into database for strike
                    $entityManager->flush();
                } elseif ($objectKey == 3 && $doInsert) {
                    // chain object with other foreign key
                    $countUnderlying = 0;
                    $existUnderlying = "";
                    foreach ($objectArray as $chainObject) {
                        if ($chainObject instanceof Chain) {
                            // use inserted strike id
                            $existStrike = $this->findOneByArray(
                                array(
                                    'category' => $chainObject->getStrikeid()->getCategory(),
                                    'price' => $chainObject->getStrikeid()->getPrice(),
                                ),
                                'Strike'
                            );

                            if ($existStrike instanceof Strike) {
                                $strike = $entityManager->getReference(
                                    'Jack\ImportBundle\Entity\Strike',
                                    $existStrike->getId()
                                );

                                $chainObject->setStrikeid($strike);
                            }

                            // use inserted cycle id
                            $existCycle = $this->findOneByArray(
                                array(
                                    'expiredate' => $chainObject->getCycleid()->getExpiredate(),
                                    'expiremonth' => $chainObject->getCycleid()->getExpiremonth(),
                                    'expireyear' => $chainObject->getCycleid()->getExpireyear(),
                                    'contractright' => $chainObject->getCycleid()->getContractright(),
                                    'ismini' => $chainObject->getCycleid()->getIsmini(),
                                    'isweekly' => $chainObject->getCycleid()->getIsweekly()
                                ),
                                'Cycle'
                            );

                            if ($existCycle instanceof Cycle) {
                                $chainObject->setCycleid($existCycle);
                            }

                            // use inserted underlying id
                            if (!$countUnderlying) {
                                $existUnderlying = $this->findOneByArray(
                                    array('date' => $chainObject->getUnderlyingid()->getDate()),
                                    'Underlying'
                                );

                                $countUnderlying++;
                            }

                            if ($existUnderlying instanceof Underlying) {
                                $chainObject->setUnderlyingid($existUnderlying);
                            }

                            // add into persist query
                            $entityManager->persist($chainObject);
                        } else {
                            throw $this->createNotFoundException(
                                "Invalid Chain object (Key $objectKey)"
                            );
                        }
                    }

                    // insert into database for chain
                    $entityManager->flush();
                }

                $entityManager->clear();
            }
        }

        return $warning;
    }

    /**
     * @param $currentSymbol
     * input the current symbol for search table
     * @param $pathArray
     * input the path array to get max/min date
     * compare it with table then insert
     */
    private function updateSymbolTable($currentSymbol, $pathArray)
    {
        date_default_timezone_set('UTC');

        $entityManager = $this->getDoctrine()->getManager('system');
        /** @noinspection PhpUndefinedMethodInspection */
        $symbol = $entityManager
            ->getRepository('JackImportBundle:Symbol'
            )->findOneByName($currentSymbol);

        // extract date from file name
        $dateArray = Array();

        foreach ($pathArray as $filePath) {
            $dateArray[] = substr(basename($filePath), 0, 10);
        }

        $maxDate = strtotime(max($dateArray));
        $minDate = strtotime(min($dateArray));

        if ($symbol instanceof Symbol) {
            // set start date if start date is null
            // or start date is bigger than min date
            if (is_null($symbol->getFirstdate()) ||
                $symbol->getFirstdate()->getTimestamp() > $minDate
            ) {
                $symbol->setFirstdate(
                    new \DateTime(date("Y-M-d", $minDate))
                );
            }

            // or last date is bigger than max date
            if (is_null($symbol->getLastdate()) ||
                $symbol->getLastdate()->getTimestamp() < $maxDate
            ) {
                $symbol->setLastdate(
                    new \DateTime(date("Y-M-d", $maxDate))
                );
            }

            $entityManager->flush();
        }
    }

    /**
     * @param $searchArray
     * array of search field names and values
     * @param $table
     * what table use for searching
     * @return object
     * return any of underlying, cycle, strike object
     */
    private function findOneByArray($searchArray, $table)
    {
        /** @noinspection PhpUndefinedMethodInspection */
        return $this->getDoctrine()
            ->getRepository('JackImportBundle:' . $table, 'symbol')
            ->findOneBy($searchArray);
    }

    /**
     * @param $dbName
     * input new db name to create new db and
     * switch the current 'symbol' connection
     * into new db
     */
    private function createNewDb($dbName)
    {
        date_default_timezone_set('UTC');

        // create new drive, connection object then create new db
        $driver = new Driver;
        $conn = new Connection(array(
            'driver' => $this->container->getParameter('database_driver'),
            'host' => $this->container->getParameter('database_host'),
            'port' => $this->container->getParameter('database_port'),
            'user' => $this->container->getParameter('database_user'),
            'password' => $this->container->getParameter('database_password'),
            'charset' => 'UTF8',
            'persistent' => 'FALSE'
        ), $driver);

        $schemaManager = $conn->getSchemaManager();
        $schemaManager->createDatabase($dbName);

        // use the new connection with new db name
        $this->switchDatabase(
            $dbName,
            $this->container->getParameter('database_user'),
            $this->container->getParameter('database_password')
        );

        // use the new db entity manager
        $entityManager = $this->getDoctrine()->getManager('symbol');

        // create new schema object
        /** @var $entityManager \Doctrine\ORM\EntityManager */
        $tool = new SchemaTool($entityManager);

        $tool->createSchema(array(
            $entityManager->getClassMetadata('Jack\ImportBundle\Entity\Underlying'),
            $entityManager->getClassMetadata('Jack\ImportBundle\Entity\Cycle'),
            $entityManager->getClassMetadata('Jack\ImportBundle\Entity\Strike'),
            $entityManager->getClassMetadata('Jack\ImportBundle\Entity\Chain'),
            $entityManager->getClassMetadata('Jack\ImportBundle\Entity\Event'),
            $entityManager->getClassMetadata('Jack\ImportBundle\Entity\Earning'),
            $entityManager->getClassMetadata('Jack\ImportBundle\Entity\Analyst'),
        ));
    }

    /**
     * @param $symbolName
     * input the symbol name to be insert into table
     * @return int
     * retrieve the last insert symbol table id
     */
    private function insertSymbol($symbolName)
    {
        date_default_timezone_set('UTC');

        // create new symbol object
        $symbol = new Symbol();

        // set require column
        $symbol->setName($symbolName);
        $symbol->setImportdate(new \DateTime('now'));

        // create em and insert row
        $entityManager = $this->getDoctrine()->getManager();
        $entityManager->persist($symbol);
        $entityManager->flush();

        return $symbol->getId();
    }

    /**
     * @param $dbName
     * db name you want to use
     * @param $dbUser
     * db user that use for connect
     * @param $dbPass
     * db password that use for connect
     */
    private function switchDatabase($dbName, $dbUser, $dbPass)
    {
        $connection = $this->container->get(sprintf('doctrine.dbal.%s_connection', 'symbol'));

        $refConn = new \ReflectionObject($connection);
        $refParams = $refConn->getProperty('_params');
        $refParams->setAccessible('public'); //we have to change it for a moment

        $params = $refParams->getValue($connection);
        $params['dbname'] = $dbName;
        $params['user'] = $dbUser;
        $params['password'] = $dbPass;

        $refParams->setAccessible('private');
        $refParams->setValue($connection, $params);
    }
}
