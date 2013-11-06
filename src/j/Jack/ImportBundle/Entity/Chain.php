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


}
