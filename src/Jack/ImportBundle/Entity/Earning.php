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


    /**
     * Set markethour
     *
     * @param string $markethour
     * @return Earning
     */
    public function setMarkethour($markethour)
    {
        $this->markethour = $markethour;

        return $this;
    }

    /**
     * Get markethour
     *
     * @return string
     */
    public function getMarkethour()
    {
        return $this->markethour;
    }

    /**
     * Set periodending
     *
     * @param \DateTime $periodending
     * @return Earning
     */
    public function setPeriodending($periodending)
    {
        $this->periodending = $periodending;

        return $this;
    }

    /**
     * Get periodending
     *
     * @return \DateTime
     */
    public function getPeriodending()
    {
        return $this->periodending;
    }

    /**
     * Set estimate
     *
     * @param float $estimate
     * @return Earning
     */
    public function setEstimate($estimate)
    {
        $this->estimate = $estimate;

        return $this;
    }

    /**
     * Get estimate
     *
     * @return float
     */
    public function getEstimate()
    {
        return $this->estimate;
    }

    /**
     * Set actual
     *
     * @param float $actual
     * @return Earning
     */
    public function setActual($actual)
    {
        $this->actual = $actual;

        return $this;
    }

    /**
     * Get actual
     *
     * @return float
     */
    public function getActual()
    {
        return $this->actual;
    }

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set eventid
     *
     * @param \Jack\ImportBundle\Entity\Event $eventid
     * @return Earning
     */
    public function setEventid(\Jack\ImportBundle\Entity\Event $eventid = null)
    {
        $this->eventid = $eventid;

        return $this;
    }

    /**
     * Get eventid
     *
     * @return \Jack\ImportBundle\Entity\Event
     */
    public function getEventid()
    {
        return $this->eventid;
    }
}