<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Sizzle
 *
 * @ORM\Table(name="sizzle")
 * @ORM\Entity
 */
class Sizzle
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
     * @ORM\Column(name="putIndex", type="float", nullable=false)
     */
    private $putindex;

    /**
     * @var float
     *
     * @ORM\Column(name="callIndex", type="float", nullable=false)
     */
    private $callindex;

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
     * @return Sizzle
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
     * Set putindex
     *
     * @param float $putindex
     * @return Sizzle
     */
    public function setPutindex($putindex)
    {
        $this->putindex = $putindex;

        return $this;
    }

    /**
     * Get putindex
     *
     * @return float
     */
    public function getPutindex()
    {
        return $this->putindex;
    }

    /**
     * Set callindex
     *
     * @param float $callindex
     * @return Sizzle
     */
    public function setCallindex($callindex)
    {
        $this->callindex = $callindex;

        return $this;
    }

    /**
     * Get callindex
     *
     * @return float
     */
    public function getCallindex()
    {
        return $this->callindex;
    }

    /**
     * Set value
     *
     * @param float $value
     * @return Sizzle
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
     * @return Sizzle
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