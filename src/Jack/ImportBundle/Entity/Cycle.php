<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Cycle
 *
 * @ORM\Table(name="cycle")
 * @ORM\Entity
 */
class Cycle
{
    /**
     * @var string
     *
     * @ORM\Column(name="expireMonth", type="string", length=255, nullable=false)
     */
    private $expiremonth;

    /**
     * @var integer
     *
     * @ORM\Column(name="expireYear", type="integer", nullable=false)
     */
    private $expireyear;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="expireDate", type="date", nullable=false)
     */
    private $expiredate;

    /**
     * @var integer
     *
     * @ORM\Column(name="contractRight", type="integer", nullable=false)
     */
    private $contractright;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isWeekly", type="boolean", nullable=false)
     */
    private $isweekly;

    /**
     * @var boolean
     *
     * @ORM\Column(name="isMini", type="boolean", nullable=false)
     */
    private $ismini;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


    /**
     * Set expiremonth
     *
     * @param string $expiremonth
     * @return Cycle
     */
    public function setExpiremonth($expiremonth)
    {
        $this->expiremonth = $expiremonth;

        return $this;
    }

    /**
     * Get expiremonth
     *
     * @return string
     */
    public function getExpiremonth()
    {
        return $this->expiremonth;
    }

    /**
     * Set expireyear
     *
     * @param integer $expireyear
     * @return Cycle
     */
    public function setExpireyear($expireyear)
    {
        $this->expireyear = $expireyear;

        return $this;
    }

    /**
     * Get expireyear
     *
     * @return integer
     */
    public function getExpireyear()
    {
        return $this->expireyear;
    }

    /**
     * Set expiredate
     *
     * @param \DateTime $expiredate
     * @return Cycle
     */
    public function setExpiredate($expiredate)
    {
        $this->expiredate = $expiredate;

        return $this;
    }

    /**
     * Get expiredate
     *
     * @return \DateTime
     */
    public function getExpiredate()
    {
        return $this->expiredate;
    }

    /**
     * Set contractright
     *
     * @param integer $contractright
     * @return Cycle
     */
    public function setContractright($contractright)
    {
        $this->contractright = $contractright;

        return $this;
    }

    /**
     * Get contractright
     *
     * @return integer
     */
    public function getContractright()
    {
        return $this->contractright;
    }

    /**
     * Set isweekly
     *
     * @param boolean $isweekly
     * @return Cycle
     */
    public function setIsweekly($isweekly)
    {
        $this->isweekly = $isweekly;

        return $this;
    }

    /**
     * Get isweekly
     *
     * @return boolean
     */
    public function getIsweekly()
    {
        return $this->isweekly;
    }

    /**
     * Set ismini
     *
     * @param boolean $ismini
     * @return Cycle
     */
    public function setIsmini($ismini)
    {
        $this->ismini = $ismini;

        return $this;
    }

    /**
     * Get ismini
     *
     * @return boolean
     */
    public function getIsmini()
    {
        return $this->ismini;
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