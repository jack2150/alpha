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
     * @ORM\Column(name="opinion", type="integer", nullable=false)
     */
    private $opinion = 0;

    /**
     * @var integer
     *
     * @ORM\Column(name="rating", type="integer", nullable=false)
     */
    private $rating = 3;

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
     * @var \Jack\ImportBundle\Entity\Event
     *
     * @ORM\ManyToOne(targetEntity="Jack\ImportBundle\Entity\Event")
     * @ORM\JoinColumns({
     * @ORM\JoinColumn(name="eventId", referencedColumnName="id")
     * })
     */
    private $eventid;


    /**
     * Set firm
     *
     * @param string $firm
     * @return Analyst
     */
    public function setFirm($firm)
    {
        $this->firm = $firm;

        return $this;
    }

    /**
     * Get firm
     *
     * @return string
     */
    public function getFirm()
    {
        return $this->firm;
    }

    /**
     * Set opinion
     *
     * @param integer $opinion
     * @return Analyst
     */
    public function setOpinion($opinion)
    {
        $this->opinion = $opinion;

        return $this;
    }

    /**
     * Get opinion
     *
     * @return integer
     */
    public function getOpinion()
    {
        return $this->opinion;
    }

    /**
     * Set rating
     *
     * @param integer $rating
     * @return Analyst
     */
    public function setRating($rating)
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * Get rating
     *
     * @return integer
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Set target
     *
     * @param float $target
     * @return Analyst
     */
    public function setTarget($target)
    {
        $this->target = $target;

        return $this;
    }

    /**
     * Get target
     *
     * @return float
     */
    public function getTarget()
    {
        return $this->target;
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
     * Set eventid
     *
     * @param \Jack\ImportBundle\Entity\Event $eventid
     * @return Analyst
     */
    public function setEventid(\Jack\ImportBundle\Entity\Event $eventid = null)
    {
        $this->eventid = $eventid;

        return $this;
    }

    /**
     * Get eventid
     *
     * @return \Jack\ImportBundle\Entity\Event
     */
    public function getEventid()
    {
        return $this->eventid;
    }
}