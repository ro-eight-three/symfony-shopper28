<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\Shoplist;
use AppBundle\Entity\Product;
use AppBundle\Entity\Buydetail;

class ConvenientController extends Controller
{
	protected function assertIsPostRequest($request)
	{
		if ($request->getMethod() != 'POST')
		{
			throw new AccessDeniedException();
		}
	}

	protected function exeptionToFlash(\Exception $e)
	{
		$this->addFlash('error', $e->getMessage());
	}
}
