<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\NameType;
use AppBundle\Entity\Shoplist;
use AppBundle\Entity\Product;
use AppBundle\Entity\Buydetail;

class ProductController extends ConvenientController
{
	/**
	 * @Route("/product/create/for/{shoplist_id}", name="product-create")
	 */
	public function createAction(Request $request, $shoplist_id)
	{
		$shoplist = Shoplist::getIfOwner($this, $shoplist_id);
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

				return $this->redirectToRoute('shoplist-contents', ['id' => $shoplist->id,]);

			} catch (\Exception $e) {
				$this->exeptionToFlash($e);
			}
		}

		return $this->render('product/create.html.twig', [
			'form' => $form->createView(),
			'shoplist' => $shoplist,
		]);
	}
}
