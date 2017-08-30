<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\NameType;
use AppBundle\Entity\Shoplist;
use AppBundle\Entity\Buydetail;
use AppBundle\Entity\Product;
use AppBundle\Controller\BuydetailController;

class ShoplistController extends Controller
{
	private function exeptionToFlash(\Exception $e)
	{
		$this->addFlash('error', $e->getMessage());
	}

	/**
	 * @Route("/shoplist/", name="shoplist-listall")
	 */
	public function listallAction(Request $request)
	{
		$shoplists = $this->getDoctrine()->getRepository(Shoplist::class)->findByOwner($this->getUser());

		return $this->render('shoplist/listall.html.twig', array(
			'shoplists' => $shoplists,
		));
	}

	/**
	 * @Route("/shoplist/create", name="shoplist-create")
	 */
	public function createAction(Request $request)
	{
		$form = $this->createForm(NameType::class);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			try {
				$name = $form->get('name')->getData();
				$owner = $this->getUser();

				$shoplist = Shoplist::storeUniqueNew($this->getDoctrine(), $name, $owner);

				return $this->redirectToRoute('shoplist-contents', array(
					'id' => $shoplist->id,
				));
			} catch (\Exception $e) {
				$this->exeptionToFlash($e);
			}
		}

		return $this->render('shoplist/create.html.twig', array(
			'form' => $form->createView(),
		));
	}

	/**
	 * @Route("/shoplist/remove/{id}", name="shoplist-remove")
	 */
	public function removeAction(Request $request, $id)
	{
		$shoplist = Shoplist::getIfOwner($this, $id);
		$em = $this->getDoctrine()->getManager();

		$buydetails = $em->getRepository(Buydetail::class)->findByShoplist($shoplist);
		if ($buydetails)
		{
			foreach($buydetails as $buydetail)
			{
				$em->remove($buydetail);
			}
		}
		$em->remove($shoplist);
		$em->flush();

		return $this->redirectToRoute('shoplist-listall');
	}

	/**
	 * @Route("/shoplist/{id}", name="shoplist-contents")
	 */
	public function contentsAction(Request $request, $id)
	{
		$shoplist = Shoplist::getIfOwner($this, $id);
		$buydetails = $this->getDoctrine()
			->getRepository(Buydetail::class)->findByShoplist($shoplist);

		return $this->render('shoplist/contents.html.twig', array(
			'shoplist' => $shoplist,
			'buydetails' => $buydetails,
		));
	}
}
