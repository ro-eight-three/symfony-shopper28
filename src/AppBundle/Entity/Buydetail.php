<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Buydetail
{
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Shoplist")
     **/
    protected $shoplist;
    
    /**
     * @ORM\Id
     * @ORM\ManyToOne(targetEntity="Product")
     **/
    protected $product;

    /**
     * @ORM\Column(type="integer")
     **/    
    protected $quantity;
}
