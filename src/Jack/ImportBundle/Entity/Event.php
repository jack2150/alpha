<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Event
 *
 * @ORM\Table(name="event")
 * @ORM\Entity
 */
class Event
{
    /**
     * @var string
     *
     * @ORM\Column(name="name", type="string", length=100, nullable=false)
     */
    private $name;

    /**
     * @var string
     *
     * @ORM\Column(name="context", type="string", length=200, nullable=true)
     */
    private $context;

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
     * Set name
     *
     * @param string $name
     * @return Event
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
     * Set context
     *
     * @param string $context
     * @return Event
     */
    public function setContext($context)
    {
        $this->context = $context;

        return $this;
    }

    /**
     * Get context
     *
     * @return string
     */
    public function getContext()
    {
        return $this->context;
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
     * @return Event
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