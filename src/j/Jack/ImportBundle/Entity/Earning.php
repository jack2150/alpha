<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Earning
 *
 * @ORM\Table(name="earning")
 * @ORM\Entity
 */
class Earning
{
    /**
     * @var string
     *
     * @ORM\Column(name="marketHour", type="string", length=7, nullable=false)
     */
    private $markethour;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="periodEnding", type="date", nullable=false)
     */
    private $periodending;

    /**
     * @var float
     *
     * @ORM\Column(name="estimate", type="float", nullable=false)
     */
    private $estimate;

    /**
     * @var float
     *
     * @ORM\Column(name="actual", type="float", nullable=false)
     */
    private $actual;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Jack\ImportBundle\Entity\Event
     *
     * @ORM\ManyToOne(targetEntity="Jack\ImportBundle\Entity\Event")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="eventId", referencedColumnName="id")
     * })
     */
    private $eventid;


}
