<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Shoplist;
use AppBundle\Entity\Product;
use AppBundle\Entity\Buydetail;

class BuydetailController extends Controller
{
	private function asserIsPostRequest($request)
	{
		if ($request->getMethod() != 'POST')
		{
			throw new AccessDeniedException();
		}
	}

	/**
	 * @Route("/buydetail/add/{product_id}/to/{shoplist_id}", name="buydetail-add")
	 */
	public function addAction(Request $request, $product_id, $shoplist_id)
	{
		$this->asserIsPostRequest($request);

		try {
			$shoplist = Shoplist::getIfOwner($this, $shoplist_id);
			$product = Product::fetchOrNotFoundException($this->getDoctrine(), $product_id);

			$em = $this->getDoctrine()->getManager();
			$em->persist(new Buydetail($shoplist, $product, 1));
			$em->flush();

		} catch (\Exception $e) {
			$this->exeptionToFlash($e);
		}

		return $this->redirectToRoute('buydetail-select', ['shoplist_id' => $shoplist->id,]);
	}

	/**
	 * @Route("/shoplist/remove/{product_id}/from/{shoplist_id}", name="buydetail-remove")
	 */
	public function removeAction(Request $request, $shoplist_id, $product_id)
	{
		$this->asserIsPostRequest($request);

		$shoplist = Shoplist::getIfOwner($this, $shoplist_id);

		try {
			Buydetail::remove($this->getDoctrine(), $shoplist_id, $product_id);

		} catch (\Exception $e) {
			$this->exeptionToFlash($e);
		}

		return $this->redirectToRoute('shoplist-contents', ['id' => $shoplist->id,]);
	}

	/**
	 * @Route("/buydetail/toggle/{product_id}/in/{shoplist_id}", name="buydetail-toggle")
	 */
	public function toggleAction(Request $request, $product_id, $shoplist_id)
	{
		$this->asserIsPostRequest($request);

		$shoplist = Shoplist::getIfOwner($this, $shoplist_id);

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

		return $this->redirectToRoute('shoplist-contents', ['id' => $shoplist->id,]);
	}

	/**
	 * @Route("/buydetail/select/for/{shoplist_id}", name="buydetail-select")
	 */
	public function selectAction(Request $request, $shoplist_id)
	{
		$shoplist = Shoplist::getIfOwner($this, $shoplist_id);
		$products = Product::getAllNotInShoplist($this->getDoctrine(), $shoplist);

		return $this->render('buydetail/select.html.twig', [
			'products' => $products,
			'shoplist' => $shoplist,
		]);
	}

	/**
	 * @Route("/buydetail/multi/for/{shoplist_id}", name="buydetail-multi")
	 */
	public function multiAction(Request $request, $shoplist_id)
	{
		$shoplist = Shoplist::getIfOwner($this, $shoplist_id);

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

				return $this->redirectToRoute('shoplist-contents', ['id' => $shoplist->id,]);

			} catch (\Exception $e) {
				$this->exeptionToFlash($e);
			}
		}

		$products = Product::getAllNotInShoplist($this->getDoctrine(), $shoplist);

		return $this->render('buydetail/multi.html.twig', [
			'products' => $products,
			'shoplist' => $shoplist,
		]);
	}
}
