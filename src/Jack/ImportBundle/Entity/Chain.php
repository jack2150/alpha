<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Chain
 *
 * @ORM\Table(name="chain")
 * @ORM\Entity
 */
class Chain
{
    /**
     * @var integer
     *
     * @ORM\Column(name="dte", type="integer", nullable=false)
     */
    private $dte;

    /**
     * @var float
     *
     * @ORM\Column(name="bid", type="float", nullable=false)
     */
    private $bid;

    /**
     * @var float
     *
     * @ORM\Column(name="ask", type="float", nullable=false)
     */
    private $ask;

    /**
     * @var float
     *
     * @ORM\Column(name="delta", type="float", nullable=false)
     */
    private $delta;

    /**
     * @var float
     *
     * @ORM\Column(name="gamma", type="float", nullable=false)
     */
    private $gamma;

    /**
     * @var float
     *
     * @ORM\Column(name="theta", type="float", nullable=false)
     */
    private $theta;

    /**
     * @var float
     *
     * @ORM\Column(name="vega", type="float", nullable=false)
     */
    private $vega;

    /**
     * @var float
     *
     * @ORM\Column(name="rho", type="float", nullable=false)
     */
    private $rho;

    /**
     * @var float
     *
     * @ORM\Column(name="theo", type="float", nullable=false)
     */
    private $theo;

    /**
     * @var float
     *
     * @ORM\Column(name="impl", type="float", nullable=false)
     */
    private $impl;

    /**
     * @var float
     *
     * @ORM\Column(name="probITM", type="float", nullable=false)
     */
    private $probitm;

    /**
     * @var float
     *
     * @ORM\Column(name="probOTM", type="float", nullable=false)
     */
    private $probotm;

    /**
     * @var float
     *
     * @ORM\Column(name="probTouch", type="float", nullable=false)
     */
    private $probtouch;

    /**
     * @var integer
     *
     * @ORM\Column(name="volume", type="integer", nullable=false)
     */
    private $volume;

    /**
     * @var integer
     *
     * @ORM\Column(name="openInterest", type="integer", nullable=false)
     */
    private $openinterest;

    /**
     * @var float
     *
     * @ORM\Column(name="intrinsic", type="float", nullable=false)
     */
    private $intrinsic;

    /**
     * @var float
     *
     * @ORM\Column(name="extrinsic", type="float", nullable=false)
     */
    private $extrinsic;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Jack\ImportBundle\Entity\Strike
     *
     * @ORM\ManyToOne(targetEntity="Jack\ImportBundle\Entity\Strike")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="strikeId", referencedColumnName="id")
     * })
     */
    private $strikeid;

    /**
     * @var \Jack\ImportBundle\Entity\Cycle
     *
     * @ORM\ManyToOne(targetEntity="Jack\ImportBundle\Entity\Cycle")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="cycleId", referencedColumnName="id")
     * })
     */
    private $cycleid;

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
     * Set dte
     *
     * @param integer $dte
     * @return Chain
     */
    public function setDte($dte)
    {
        $this->dte = $dte;

        return $this;
    }

    /**
     * Get dte
     *
     * @return integer
     */
    public function getDte()
    {
        return $this->dte;
    }

    /**
     * Set bid
     *
     * @param float $bid
     * @return Chain
     */
    public function setBid($bid)
    {
        $this->bid = $bid;

        return $this;
    }

    /**
     * Get bid
     *
     * @return float
     */
    public function getBid()
    {
        return $this->bid;
    }

    /**
     * Set ask
     *
     * @param float $ask
     * @return Chain
     */
    public function setAsk($ask)
    {
        $this->ask = $ask;

        return $this;
    }

    /**
     * Get ask
     *
     * @return float
     */
    public function getAsk()
    {
        return $this->ask;
    }

    /**
     * Set delta
     *
     * @param float $delta
     * @return Chain
     */
    public function setDelta($delta)
    {
        $this->delta = $delta;

        return $this;
    }

    /**
     * Get delta
     *
     * @return float
     */
    public function getDelta()
    {
        return $this->delta;
    }

    /**
     * Set gamma
     *
     * @param float $gamma
     * @return Chain
     */
    public function setGamma($gamma)
    {
        $this->gamma = $gamma;

        return $this;
    }

    /**
     * Get gamma
     *
     * @return float
     */
    public function getGamma()
    {
        return $this->gamma;
    }

    /**
     * Set theta
     *
     * @param float $theta
     * @return Chain
     */
    public function setTheta($theta)
    {
        $this->theta = $theta;

        return $this;
    }

    /**
     * Get theta
     *
     * @return float
     */
    public function getTheta()
    {
        return $this->theta;
    }

    /**
     * Set vega
     *
     * @param float $vega
     * @return Chain
     */
    public function setVega($vega)
    {
        $this->vega = $vega;

        return $this;
    }

    /**
     * Get vega
     *
     * @return float
     */
    public function getVega()
    {
        return $this->vega;
    }

    /**
     * Set rho
     *
     * @param float $rho
     * @return Chain
     */
    public function setRho($rho)
    {
        $this->rho = $rho;

        return $this;
    }

    /**
     * Get rho
     *
     * @return float
     */
    public function getRho()
    {
        return $this->rho;
    }

    /**
     * Set theo
     *
     * @param float $theo
     * @return Chain
     */
    public function setTheo($theo)
    {
        $this->theo = $theo;

        return $this;
    }

    /**
     * Get theo
     *
     * @return float
     */
    public function getTheo()
    {
        return $this->theo;
    }

    /**
     * Set impl
     *
     * @param float $impl
     * @return Chain
     */
    public function setImpl($impl)
    {
        $this->impl = $impl;

        return $this;
    }

    /**
     * Get impl
     *
     * @return float
     */
    public function getImpl()
    {
        return $this->impl;
    }

    /**
     * Set probitm
     *
     * @param float $probitm
     * @return Chain
     */
    public function setProbitm($probitm)
    {
        $this->probitm = $probitm;

        return $this;
    }

    /**
     * Get probitm
     *
     * @return float
     */
    public function getProbitm()
    {
        return $this->probitm;
    }

    /**
     * Set probotm
     *
     * @param float $probotm
     * @return Chain
     */
    public function setProbotm($probotm)
    {
        $this->probotm = $probotm;

        return $this;
    }

    /**
     * Get probotm
     *
     * @return float
     */
    public function getProbotm()
    {
        return $this->probotm;
    }

    /**
     * Set probtouch
     *
     * @param float $probtouch
     * @return Chain
     */
    public function setProbtouch($probtouch)
    {
        $this->probtouch = $probtouch;

        return $this;
    }

    /**
     * Get probtouch
     *
     * @return float
     */
    public function getProbtouch()
    {
        return $this->probtouch;
    }

    /**
     * Set volume
     *
     * @param integer $volume
     * @return Chain
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
     * Set openinterest
     *
     * @param integer $openinterest
     * @return Chain
     */
    public function setOpeninterest($openinterest)
    {
        $this->openinterest = $openinterest;

        return $this;
    }

    /**
     * Get openinterest
     *
     * @return integer
     */
    public function getOpeninterest()
    {
        return $this->openinterest;
    }

    /**
     * Set intrinsic
     *
     * @param float $intrinsic
     * @return Chain
     */
    public function setIntrinsic($intrinsic)
    {
        $this->intrinsic = $intrinsic;

        return $this;
    }

    /**
     * Get intrinsic
     *
     * @return float
     */
    public function getIntrinsic()
    {
        return $this->intrinsic;
    }

    /**
     * Set extrinsic
     *
     * @param float $extrinsic
     * @return Chain
     */
    public function setExtrinsic($extrinsic)
    {
        $this->extrinsic = $extrinsic;

        return $this;
    }

    /**
     * Get extrinsic
     *
     * @return float
     */
    public function getExtrinsic()
    {
        return $this->extrinsic;
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
     * Set strikeid
     *
     * @param \Jack\ImportBundle\Entity\Strike $strikeid
     * @return Chain
     */
    public function setStrikeid(\Jack\ImportBundle\Entity\Strike $strikeid = null)
    {
        $this->strikeid = $strikeid;

        return $this;
    }

    /**
     * Get strikeid
     *
     * @return \Jack\ImportBundle\Entity\Strike
     */
    public function getStrikeid()
    {
        return $this->strikeid;
    }

    /**
     * Set cycleid
     *
     * @param \Jack\ImportBundle\Entity\Cycle $cycleid
     * @return Chain
     */
    public function setCycleid(\Jack\ImportBundle\Entity\Cycle $cycleid = null)
    {
        $this->cycleid = $cycleid;

        return $this;
    }

    /**
     * Get cycleid
     *
     * @return \Jack\ImportBundle\Entity\Cycle
     */
    public function getCycleid()
    {
        return $this->cycleid;
    }

    /**
     * Set underlyingid
     *
     * @param \Jack\ImportBundle\Entity\Underlying $underlyingid
     * @return Chain
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