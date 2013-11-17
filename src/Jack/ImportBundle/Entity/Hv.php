<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Hv
 *
 * @ORM\Table(name="hv")
 * @ORM\Entity
 */
class Hv
{
    /**
     * @var integer
     *
     * @ORM\Column(name="sample", type="integer", nullable=false)
     */
    private $sample;

    /**
     * @var float
     *
     * @ORM\Column(name="value", type="float", nullable=false)
     */
    private $value;

    /**
     * @var float
     *
     * @ORM\Column(name="yearHigh", type="float", nullable=true)
     */
    private $yearhigh;

    /**
     * @var float
     *
     * @ORM\Column(name="yearLow", type="float", nullable=true)
     */
    private $yearlow;

    /**
     * @var float
     *
     * @ORM\Column(name="rank", type="float", nullable=true)
     */
    private $rank;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Jack\ImportBundle\Entity\Underlying
     *
     * @ORM\ManyToOne(targetEntity="Jack\ImportBundle\Entity\Underlying")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="underlyingId", referencedColumnName="id")
     * })
     */
    private $underlyingid;


    /**
     * Set sample
     *
     * @param integer $sample
     * @return Hv
     */
    public function setSample($sample)
    {
        $this->sample = $sample;

        return $this;
    }

    /**
     * Get sample
     *
     * @return integer
     */
    public function getSample()
    {
        return $this->sample;
    }

    /**
     * Set value
     *
     * @param float $value
     * @return Hv
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Get value
     *
     * @return float
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * Set yearhigh
     *
     * @param float $yearhigh
     * @return Hv
     */
    public function setYearhigh($yearhigh)
    {
        $this->yearhigh = $yearhigh;

        return $this;
    }

    /**
     * Get yearhigh
     *
     * @return float
     */
    public function getYearhigh()
    {
        return $this->yearhigh;
    }

    /**
     * Set yearlow
     *
     * @param float $yearlow
     * @return Hv
     */
    public function setYearlow($yearlow)
    {
        $this->yearlow = $yearlow;

        return $this;
    }

    /**
     * Get yearlow
     *
     * @return float
     */
    public function getYearlow()
    {
        return $this->yearlow;
    }

    /**
     * Set rank
     *
     * @param float $rank
     * @return Hv
     */
    public function setRank($rank)
    {
        $this->rank = $rank;

        return $this;
    }

    /**
     * Get rank
     *
     * @return float
     */
    public function getRank()
    {
        return $this->rank;
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
     * Set underlyingid
     *
     * @param \Jack\ImportBundle\Entity\Underlying $underlyingid
     * @return Hv
     */
    public function setUnderlyingid(\Jack\ImportBundle\Entity\Underlying $underlyingid = null)
    {
        $this->underlyingid = $underlyingid;

        return $this;
    }

    /**
     * Get underlyingid
     *
     * @return \Jack\ImportBundle\Entity\Underlying
     */
    public function getUnderlyingid()
    {
        return $this->underlyingid;
    }
}