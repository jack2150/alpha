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


    /**
     * Set name
     *
     * @param string $name
     * @return Symbol
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
     * Set importdate
     *
     * @param \DateTime $importdate
     * @return Symbol
     */
    public function setImportdate($importdate)
    {
        $this->importdate = $importdate;

        return $this;
    }

    /**
     * Get importdate
     *
     * @return \DateTime
     */
    public function getImportdate()
    {
        return $this->importdate;
    }

    /**
     * Set firstdate
     *
     * @param \DateTime $firstdate
     * @return Symbol
     */
    public function setFirstdate($firstdate)
    {
        $this->firstdate = $firstdate;

        return $this;
    }

    /**
     * Get firstdate
     *
     * @return \DateTime
     */
    public function getFirstdate()
    {
        return $this->firstdate;
    }

    /**
     * Set lastdate
     *
     * @param \DateTime $lastdate
     * @return Symbol
     */
    public function setLastdate($lastdate)
    {
        $this->lastdate = $lastdate;

        return $this;
    }

    /**
     * Get lastdate
     *
     * @return \DateTime
     */
    public function getLastdate()
    {
        return $this->lastdate;
    }

    /**
     * Set type
     *
     * @param string $type
     * @return Symbol
     */
    public function setType($type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * Get type
     *
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * Set industry
     *
     * @param string $industry
     * @return Symbol
     */
    public function setIndustry($industry)
    {
        $this->industry = $industry;

        return $this;
    }

    /**
     * Get industry
     *
     * @return string
     */
    public function getIndustry()
    {
        return $this->industry;
    }

    /**
     * Set sector
     *
     * @param string $sector
     * @return Symbol
     */
    public function setSector($sector)
    {
        $this->sector = $sector;

        return $this;
    }

    /**
     * Get sector
     *
     * @return string
     */
    public function getSector()
    {
        return $this->sector;
    }

    /**
     * Set country
     *
     * @param string $country
     * @return Symbol
     */
    public function setCountry($country)
    {
        $this->country = $country;

        return $this;
    }

    /**
     * Get country
     *
     * @return string
     */
    public function getCountry()
    {
        return $this->country;
    }

    /**
     * Set marketcap
     *
     * @param string $marketcap
     * @return Symbol
     */
    public function setMarketcap($marketcap)
    {
        $this->marketcap = $marketcap;

        return $this;
    }

    /**
     * Get marketcap
     *
     * @return string
     */
    public function getMarketcap()
    {
        return $this->marketcap;
    }

    /**
     * Set shortable
     *
     * @param boolean $shortable
     * @return Symbol
     */
    public function setShortable($shortable)
    {
        $this->shortable = $shortable;

        return $this;
    }

    /**
     * Get shortable
     *
     * @return boolean
     */
    public function getShortable()
    {
        return $this->shortable;
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