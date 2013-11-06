<?php

namespace Jack\ImportBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Strike
 *
 * @ORM\Table(name="strike")
 * @ORM\Entity
 */
class Strike
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


}
