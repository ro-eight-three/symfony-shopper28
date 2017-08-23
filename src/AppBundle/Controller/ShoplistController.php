<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Form\NameType;
use AppBundle\Entity\Shoplist;

class ShoplistController extends Controller
{
    /**
     * @Route("/shoplist/", name="shoplist-listall")
     */
    public function listallAction(Request $request)
    {
        # TODO do this in the security config somehow
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Login required');

        $repository = $this->getDoctrine()->getRepository(Shoplist::class);
        $shoplists = $repository->findByOwner($this->getUser());
        
        return $this->render('shoplist/listall.html.twig', array(
            'shoplists' => $shoplists,
        ));
    }

    /**
     * @Route("/shoplist/create", name="shoplist-create")
     */
    public function createAction(Request $request)
    {
        # TODO do this in the security config somehow
        $this->denyAccessUnlessGranted('ROLE_USER', null, 'Login required');

        $form = $this->createForm(NameType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            $name = $form->get('name')->getData();
            $owner = $this->getUser();
            
            $shoplist = new Shoplist();
            $shoplist->name = $name;
            $shoplist->owner = $owner;

            $em = $this->getDoctrine()->getManager();
            $em->persist($shoplist);
            $em->flush();

            return $this->redirectToRoute('shoplist-listall');
        }

        return $this->render('shoplist/create.html.twig', array(
            'form' => $form->createView(),
        ));
    }
}
