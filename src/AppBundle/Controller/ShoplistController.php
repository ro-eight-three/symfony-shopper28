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
	private function getShoplistIfOwner($id)
	{
		$shoplist = $this->getDoctrine()->getRepository(Shoplist::class)->find($id);

		if (!$shoplist)
		{
			throw $this->createNotFoundException();
		}
		if ($shoplist->owner !== $this->getUser())
		{
			throw $this->createAccessDeniedException();
		}
		return $shoplist;
	}

	/**
	 * @Route("/shoplist/products", name="shoplist-products")
	 */
	public function productsAction(Request $request)
	{
		$products = $this->getDoctrine()->getRepository(Product::class)->findAll();

		return $this->render('shoplist/add-select.html.twig', array(
			'products' => $products,
		));
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

	private function exeptionToFlash(\Exception $e)
	{
		$this->addFlash('error', $e->getMessage());
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
		$shoplist = $this->getShoplistIfOwner($id);
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
		$shoplist = $this->getShoplistIfOwner($id);
		$buydetails = $this->getDoctrine()
			->getRepository(Buydetail::class)->findByShoplist($shoplist);

		return $this->render('shoplist/contents.html.twig', array(
			'shoplist' => $shoplist,
			'buydetails' => $buydetails,
		));
	}

	/**
	 * @Route("/shoplist/{id}/add-new", name="shoplist-add-new")
	 */
	public function addNewAction(Request $request, $id)
	{
		$shoplist = $this->getShoplistIfOwner($id);
		$form = $this->createForm(NameType::class);
		$form->handleRequest($request);

		if ($form->isSubmitted() && $form->isValid())
		{
			try {
				$em = $this->getDoctrine()->getManager();
				$product = new Product($form->get('name')->getData());
				$product->uniqueOrException($em);
				$em->persist($product);
				$em->flush();
				$em->persist(new Buydetail($shoplist, $product, 1));
				$em->flush();

				return $this->redirectToRoute('shoplist-contents', array(
					'id' => $shoplist->id,
				));
			} catch (\Exception $e) {
				$this->exeptionToFlash($e);
			}
		}

		return $this->render('shoplist/add-new.html.twig', [
			'form' => $form->createView(),
			'shoplist' => $shoplist,
		]);
	}

	/**
	 * @Route("/shoplist/{id}/add-many", name="shoplist-add-many")
	 */
	public function addManyAction(Request $request, $id)
	{
		$shoplist = $this->getShoplistIfOwner($id);

		if ($request->getMethod() == 'POST')
		{
			$product_ids = $request->request->get('product_id');

			try {
				if (!$product_ids)
				{
					throw new \Exception("No products selected");
				}

				$em = $this->getDoctrine()->getManager();
				$repo = $em->getRepository(Product::class);

				foreach ($product_ids as $product_id)
				{
					$product = $repo->find($product_id);
					if ($product)
					{
						$em->persist(new Buydetail($shoplist, $product, 1));
					}
				}
				$em->flush();

				return $this->redirectToRoute('shoplist-contents', array(
					'id' => $shoplist->id,
				));
			} catch (\Exception $e) {
				$this->exeptionToFlash($e);
			}
		}

		$products = Product::getAllNotInShoplist($this->getDoctrine(), $shoplist);

		return $this->render('shoplist/add-many.html.twig', array(
			'products' => $products,
			'shoplist' => $shoplist,
		));
	}

	/**
	 * @Route("/shoplist/{id}/add-select", name="shoplist-add-select")
	 */
	public function addSelectAction(Request $request, $id)
	{
		$shoplist = $this->getShoplistIfOwner($id);
		$products = Product::getAllNotInShoplist($this->getDoctrine(), $shoplist);

		return $this->render('shoplist/add-select.html.twig', array(
			'products' => $products,
			'shoplist' => $shoplist,
		));
	}

	/**
	 * @Route("/shoplist/{shoplist_id}/remove/{product_id}", name="remove-buydetail")
	 */
	public function removeBuydetailAction(Request $request, $shoplist_id, $product_id)
	{
		$shoplist = $this->getShoplistIfOwner($shoplist_id);

		try {
			Buydetail::remove($this->getDoctrine(), $shoplist_id, $product_id);

		} catch (\Exception $e) {
			$this->exeptionToFlash($e);
		}

		return $this->redirectToRoute('shoplist-contents', array(
			'id' => $shoplist->id,
		));
	}

	/**
	 * @Route("/shoplist/{shoplist_id}/toggle/{product_id}", name="toggle-buydetail")
	 */
	public function toggleBuydetailAction(Request $request, $shoplist_id, $product_id)
	{
		$shoplist = $this->getShoplistIfOwner($shoplist_id);

		try {
			$em = $this->getDoctrine()->getManager();
			$buydetail = $em->getRepository(Buydetail::class)->find([
				'shoplist' => $shoplist_id,
				'product' => $product_id,
			]);
			$buydetail->marked = !$buydetail->marked;
			$em->flush();
		} catch (\Exception $e) {
			$this->exeptionToFlash($e);
		}

		return $this->redirectToRoute('shoplist-contents', array(
			'id' => $shoplist->id,
		));
	}

	/**
	 * @Route("/shoplist/{shoplist_id}/add/{product_id}", name="add-buydetail")
	 */
	public function addBuydetailAction(Request $request, $shoplist_id, $product_id)
	{
		try {
			$shoplist = $this->getShoplistIfOwner($shoplist_id);
			$product = Product::fetchOrNotFoundException($this->getDoctrine(), $product_id);

			$em = $this->getDoctrine()->getManager();
			$em->persist(new Buydetail($shoplist, $product, 1));
			$em->flush();

		} catch (\Exception $e) {
			$this->exeptionToFlash($e);
		}

		return $this->redirectToRoute('shoplist-add-select', ['id' => $shoplist->id,]);
	}
}
