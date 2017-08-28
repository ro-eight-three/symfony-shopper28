<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use AppBundle\Entity\Buydetail;

/**
 * @ORM\Entity
 * @ORM\Table(name="product")
 */
class Product
{
	public function __construct($name)
	{
		$this->name = $name;
	}

	public function uniqueOrException($em)
	{
		$others =
			$em->getRepository(Product::class)
				->createQueryBuilder('a')
					->select('count(a.id)')
					->where('upper(a.name) = upper(:name)')
					->setParameter('name', $this->name)
					->getQuery()
					->getSingleScalarResult();
		if ($others > 0)
		{
			throw new \Exception("Product with the name already exists");
		}
	}

	public static function fetchOrNotFoundException($doctrine, $id)
	{
		$product = $doctrine->getRepository(Product::class)->find($id);
		if (!$product)
		{
			throw new NotFoundHttpException("Product does not exist");
		}
		return $product;
	}

	public static function getAllNotInShoplist($doctrine, $shoplist)
	{
		return $doctrine->getManager()
				->createQuery("
					SELECT theP FROM AppBundle\Entity\Product theP
					WHERE theP.id NOT IN (
						SELECT p.id FROM AppBundle\Entity\Product p
						JOIN AppBundle\Entity\Buydetail b
						WHERE p.id = b.product AND b.shoplist = :shoplist
						)
					ORDER BY theP.name
				")
				->setParameter('shoplist', $shoplist)
				->getResult();
	}

	/**
	 * @ORM\Id
	 * @ORM\Column(type="integer")
	 * @ORM\GeneratedValue(strategy="AUTO")
	 */
	public $id;

	/**
	 * @ORM\Column(type="string", length=100)
	 */
	public $name;
}
