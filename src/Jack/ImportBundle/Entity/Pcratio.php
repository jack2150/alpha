<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Pcratio
 *
 * @ORM\Table(name="pcratio")
 * @ORM\Entity
 */
class Pcratio
{
    /**
     * @var integer
     *
     * @ORM\Column(name="putVolume", type="integer", nullable=false)
     */
    private $putvolume;

    /**
     * @var integer
     *
     * @ORM\Column(name="callVolume", type="integer", nullable=false)
     */
    private $callvolume;

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
     * @ORM\JoinColumn(name="UnderlyingId", referencedColumnName="id")
     * })
     */
    private $underlyingid;


    /**
     * Set putvolume
     *
     * @param integer $putvolume
     * @return Pcratio
     */
    public function setPutvolume($putvolume)
    {
        $this->putvolume = $putvolume;

        return $this;
    }

    /**
     * Get putvolume
     *
     * @return integer
     */
    public function getPutvolume()
    {
        return $this->putvolume;
    }

    /**
     * Set callvolume
     *
     * @param integer $callvolume
     * @return Pcratio
     */
    public function setCallvolume($callvolume)
    {
        $this->callvolume = $callvolume;

        return $this;
    }

    /**
     * Get callvolume
     *
     * @return integer
     */
    public function getCallvolume()
    {
        return $this->callvolume;
    }

    /**
     * Set value
     *
     * @param float $value
     * @return Pcratio
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
     * @return Pcratio
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