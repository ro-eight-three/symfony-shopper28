<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity
 */
class Buydetail
{
	public function __construct($shoplist, $product, $quantity)
	{
		$this->shoplist = $shoplist;
		$this->product = $product;
		$this->quantity = $quantity;
	}

	public static function remove($doctrine, $shoplist_id, $product_id)
	{
		$buydetail = $doctrine->getRepository(Buydetail::class)->find([
			'shoplist' => $shoplist_id,
			'product' => $product_id,
		]);

		$em = $doctrine->getManager();
		$em->remove($buydetail);
		$em->flush();
	}

	/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Shoplist")
	 **/
	public $shoplist;
			/**
	 * @ORM\Id
	 * @ORM\ManyToOne(targetEntity="Product")
	 **/
	public $product;

	/**
	 * @ORM\Column(type="integer")
	 **/
	public $quantity;

	/**
	 * @ORM\Column(type="boolean")
	 **/
	public $marked = false;
}
