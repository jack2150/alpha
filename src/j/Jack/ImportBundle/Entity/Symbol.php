<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Symbol
 *
 * @ORM\Table(name="symbol")
 * @ORM\Entity
 */
class Symbol
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="importDate", type="date", nullable=false)
     */
    private $importdate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="firstDate", type="date", nullable=true)
     */
    private $firstdate;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="lastDate", type="date", nullable=true)
     */
    private $lastdate;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", length=100, nullable=true)
     */
    private $type;

    /**
     * @var string
     *
     * @ORM\Column(name="industry", type="string", length=100, nullable=true)
     */
    private $industry;

    /**
     * @var string
     *
     * @ORM\Column(name="sector", type="string", length=100, nullable=true)
     */
    private $sector;

    /**
     * @var string
     *
     * @ORM\Column(name="country", type="string", length=100, nullable=false)
     */
    private $country;

    /**
     * @var string
     *
     * @ORM\Column(name="marketCap", type="string", length=100, nullable=false)
     */
    private $marketcap;

    /**
     * @var boolean
     *
     * @ORM\Column(name="shortable", type="boolean", nullable=false)
     */
    private $shortable;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
