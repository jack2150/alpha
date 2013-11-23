<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Vwap
 *
 * @ORM\Table(name="vwap")
 * @ORM\Entity
 */
class Vwap
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
     * @return Vwap
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
     * @return Vwap
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
     * @return Vwap
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