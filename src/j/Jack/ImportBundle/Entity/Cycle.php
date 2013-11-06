<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cycle
 *
 * @ORM\Table(name="cycle")
 * @ORM\Entity
 */
class Cycle
{
    /**
     * @var string
     *
     * @ORM\Column(name="expireMonth", type="string", length=255, nullable=false)
     */
    private $expiremonth;

    /**
     * @var integer
     *
     * @ORM\Column(name="expireYear", type="integer", nullable=false)
     */
    private $expireyear;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expireDate", type="date", nullable=false)
     */
    private $expiredate;

    /**
     * @var integer
     *
     * @ORM\Column(name="contractRight", type="integer", nullable=false)
     */
    private $contractright;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isWeekly", type="boolean", nullable=false)
     */
    private $isweekly;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isMini", type="boolean", nullable=false)
     */
    private $ismini;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
