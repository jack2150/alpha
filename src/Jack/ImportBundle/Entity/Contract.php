<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Contract
 *
 * @ORM\Table(name="contract")
 * @ORM\Entity
 */
class Contract
{
    /**
     * @var string
     *
     * @ORM\Column(name="category", type="string", length=4, nullable=false)
     */
    private $category;

    /**
     * @var float
     *
     * @ORM\Column(name="strike", type="float", nullable=false)
     */
    private $strike;

    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;


    /**
     * Set category
     *
     * @param string $category
     * @return Contract
     */
    public function setCategory($category)
    {
        $this->category = $category;

        return $this;
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory()
    {
        return $this->category;
    }

    /**
     * Set strike
     *
     * @param float $strike
     * @return Contract
     */
    public function setStrike($strike)
    {
        $this->strike = $strike;

        return $this;
    }

    /**
     * Get strike
     *
     * @return float
     */
    public function getStrike()
    {
        return $this->strike;
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