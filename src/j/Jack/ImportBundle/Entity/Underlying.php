<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Underlying
 *
 * @ORM\Table(name="underlying")
 * @ORM\Entity
 */
class Underlying
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=255, nullable=false)
     */
    private $name;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="date", type="date", nullable=false)
     */
    private $date;

    /**
     * @var float
     *
     * @ORM\Column(name="last", type="float", nullable=false)
     */
    private $last;

    /**
     * @var float
     *
     * @ORM\Column(name="netChange", type="float", nullable=false)
     */
    private $netchange;

    /**
     * @var integer
     *
     * @ORM\Column(name="volume", type="integer", nullable=false)
     */
    private $volume;

    /**
     * @var float
     *
     * @ORM\Column(name="open", type="float", nullable=false)
     */
    private $open;

    /**
     * @var float
     *
     * @ORM\Column(name="high", type="float", nullable=false)
     */
    private $high;

    /**
     * @var float
     *
     * @ORM\Column(name="low", type="float", nullable=false)
     */
    private $low;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


}
