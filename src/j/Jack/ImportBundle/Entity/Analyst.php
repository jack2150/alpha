<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Analyst
 *
 * @ORM\Table(name="analyst")
 * @ORM\Entity
 */
class Analyst
{
    /**
     * @var string
     *
     * @ORM\Column(name="firm", type="string", length=200, nullable=true)
     */
    private $firm;

    /**
     * @var integer
     *
     * @ORM\Column(name="change", type="integer", nullable=false)
     */
    private $change;

    /**
     * @var integer
     *
     * @ORM\Column(name="rating", type="integer", nullable=false)
     */
    private $rating;

    /**
     * @var float
     *
     * @ORM\Column(name="target", type="float", nullable=false)
     */
    private $target;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var \Jack\ImportBundle\Entity\Earning
     *
     * @ORM\ManyToOne(targetEntity="Jack\ImportBundle\Entity\Earning")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="eventId", referencedColumnName="id")
     * })
     */
    private $eventid;


}
