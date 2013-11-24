<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Earning
 */
class Earning
{
    /**
     * @var string
     */
    private $markethour;

    /**
     * @var string
     */
    private $periodending;

    /**
     * @var float
     */
    private $estimate;

    /**
     * @var float
     */
    private $actual;

    /**
     * @var integer
     */
    private $id;

    /**
     * @var \Jack\ImportBundle\Entity\Event
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
     * @param string $periodending
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
     * @return string
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