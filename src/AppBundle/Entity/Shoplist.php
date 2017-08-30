<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

/**
 * @ORM\Entity
 */
class Shoplist
{
	public static function storeUniqueNew($doctrine, $name, $owner)
	{
		$others =
			$doctrine->getRepository(Shoplist::class)
				->createQueryBuilder('a')
					->select('count(a.id)')
					->where('upper(a.name) = upper(:name)', 'a.owner = :owner')
					->setParameter('name', $name)
					->setParameter('owner', $owner)
					->getQuery()
					->getSingleScalarResult();

		if ($others > 0)
		{
			throw new \Exception("You already have a shoplist with this name");
		}

		$shoplist = new Shoplist();
		$shoplist->name = $name;
		$shoplist->owner = $owner;

		$em = $doctrine->getManager();
		$em->persist($shoplist);
		$em->flush();

		return $shoplist;
	}

	public static function getIfOwner($controller, $shoplist_id)
	{
		$shoplist = $controller->getDoctrine()->getManager()
			->getRepository(Shoplist::class)->find($shoplist_id);

		if (!$shoplist)
		{
			throw new NotFoundHttpException();
		}
		if ($shoplist->owner !== $controller->getUser())
		{
			throw new AccessDeniedException();
		}
		return $shoplist;
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
			/**
	 * @ORM\ManyToOne(targetEntity="User")
	 **/
	public $owner;
}
