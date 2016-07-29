<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class HomeController extends Controller
{
    public function indexAction(Request $request)
    {
		$session = $request->getSession();
		$user = $session->get("access_user");
		if ($user)
		{
			return $this->redirect($this->generateUrl("app_my_events"));
		}
		else
			return $this->render('AppBundle:pages:index.html.twig' , array("user_confirmed" => false));
    }
}