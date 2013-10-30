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


    /**
     * Set name
     *
     * @param string $name
     * @return Underlying
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set date
     *
     * @param \DateTime $date
     * @return Underlying
     */
    public function setDate($date)
    {
        $this->date = $date;

        return $this;
    }

    /**
     * Get date
     *
     * @return \DateTime
     */
    public function getDate()
    {
        return $this->date;
    }

    /**
     * Set last
     *
     * @param float $last
     * @return Underlying
     */
    public function setLast($last)
    {
        $this->last = $last;

        return $this;
    }

    /**
     * Get last
     *
     * @return float
     */
    public function getLast()
    {
        return $this->last;
    }

    /**
     * Set netchange
     *
     * @param float $netchange
     * @return Underlying
     */
    public function setNetchange($netchange)
    {
        $this->netchange = $netchange;

        return $this;
    }

    /**
     * Get netchange
     *
     * @return float
     */
    public function getNetchange()
    {
        return $this->netchange;
    }

    /**
     * Set volume
     *
     * @param integer $volume
     * @return Underlying
     */
    public function setVolume($volume)
    {
        $this->volume = $volume;

        return $this;
    }

    /**
     * Get volume
     *
     * @return integer
     */
    public function getVolume()
    {
        return $this->volume;
    }

    /**
     * Set open
     *
     * @param float $open
     * @return Underlying
     */
    public function setOpen($open)
    {
        $this->open = $open;

        return $this;
    }

    /**
     * Get open
     *
     * @return float
     */
    public function getOpen()
    {
        return $this->open;
    }

    /**
     * Set high
     *
     * @param float $high
     * @return Underlying
     */
    public function setHigh($high)
    {
        $this->high = $high;

        return $this;
    }

    /**
     * Get high
     *
     * @return float
     */
    public function getHigh()
    {
        return $this->high;
    }

    /**
     * Set low
     *
     * @param float $low
     * @return Underlying
     */
    public function setLow($low)
    {
        $this->low = $low;

        return $this;
    }

    /**
     * Get low
     *
     * @return float
     */
    public function getLow()
    {
        return $this->low;
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
}