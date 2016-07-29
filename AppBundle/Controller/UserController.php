<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use AppBundle\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpFoundation\Session\Session;

class UserController extends Controller
{
    public function loginAction(Request $request)
    {
		$data = $request->request->get('data');
		$data['password'] = $this->encrypt($data['password']);
		
		$error = null;
		
		$repository = $this->getDoctrine()->getRepository('AppBundle:User');
		$user = $repository->findOneBy(  array('login' => $data['login']));
		if ($user)
		{
			$status = $user->getStatus();
			if ($status == 0)
				return new Response(json_encode(array('callback' => "app.open_confirm('" . $data['email'] . "')")));
			else
			{
				if ($data['password'] == $user->getPassword())
				{
					$session = $request->getSession();
					$session->set('access_user', $user);
				}	
				else
				{
					$error = 'Wrong password or username';
				}
			}
		}
		else 
			$error = 'Wrong password or username';
		
		if (!$error)
		{
			return new Response(json_encode(array('location' => $this->generateUrl('app_homepage'))));
		}
		else
		{
			return new Response(json_encode(array('error' => $error)));
		}
	}

    public function registerAction(Request $request)
    {
		$repository = $this->getDoctrine() ->getRepository('AppBundle:User');
		$data = $request->request->get('data');
		
		$error = null;
		
		if (strlen($data['login']) < 4)
		{
			$error = 'Too short login';
		}
		if (strlen($data['password']) < 4)
		{
			$error = 'Too short password';
		}
		if (strlen($data['first_name']) < 2)
		{
			$error = 'Too short first name';
		}
		if (strlen($data['last_name']) < 2)
		{
			$error = 'Too short last name';
		}
		if (!$this->check_email($data['email']))
		{
			$error = 'Please check your email';
		}
		if ($data['password'] != $data['password_confirm'])
		{
			$error =  'Pasword does not match';
		}
		
		$params=array('secret' => '6Lc_lB0TAAAAAJ91zWzUr9Xa_zYwd6tOeImcPX61', 'response' => $_POST['g-recaptcha-response']);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,'https://www.google.com/recaptcha/api/siteverify');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$server_output = curl_exec ($ch);
		curl_close ($ch);
		
		$response = json_decode($server_output, true);
		
		if ($response['success'] != true)
			$error = 'Please complete Captcha';
		
		$data['password'] = $this->encrypt($data['password']);
		
		$client = $repository->findOneBy(  array('email' => $data['email']));
		if ($client)
		{
			$error =  'This email is already in use';
		}
		else
		{
			$client = $repository->findOneBy(  array('login' => $data['login']));
			if ($client)
			{
				$error =  'This login is already in use';
			}
		}
		
		if (!$error)
		{
			$code = rand(1,99).rand(1,99).rand(1,99).chr(rand(65,90)).rand(1,99).chr(rand(65,90)).rand(1,99).chr(rand(65,90));			
			$user = new User();
		
			$user->setFirstName($data['first_name']);
			$user->setLastName($data['last_name']);
			$user->setEmail($data['email']);
			$user->setPassword($data['password']);
			$user->setStatus(0); // uncofirmed
			$user->setZip($data['zip']); // uncofirmed
			$user->setConfirmationCode($code); // uncofirmed
			$user->setLogin($data['login']);
			

			$em = $this->getDoctrine()->getManager();
			$em->persist($user);
			$em->flush();
			
			$host = 'http://' . $_SERVER['HTTP_HOST'];
			$confirmation_link = $this->generateUrl('app_confirm_user', array('code' => $code) , UrlGeneratorInterface::ABSOLUTE_URL);
			//$this->app_mail->send_confirmation($data['email'], array('code' => $data['code'], 'name' => $data['first_name']));
			$message = \Swift_Message::newInstance()
				->setSubject('Please activate your account')
				->setFrom('alerts@EventTrx.com')
				->setTo($data['email'])
				->setBody(
					$this->renderView(
						'email/confirmation.twig',
						array('code' => $code, 'name' => $data['first_name'], 'confirmation_link' => $confirmation_link, 'host' => $host)
					),
					'text/html'
				);
			$this->get('mailer')->send($message);
			return new Response(json_encode(array('callback' => 'app.open_confirm()')));
		}
		else
		{
			return new Response(json_encode(array('error' => $error)));
		}

	}

	public function socialLoginAction(Request $request, $provider)
	{
		$config = $this->get('kernel')->getRootDir() . '/../libs/hybridauth/config.php';
		require_once( $this->get('kernel')->getRootDir() . '/../libs/hybridauth/Hybrid/Auth.php' );
		
		try{
			$hybridauth = new \Hybrid_Auth( $config );

			$cl = $hybridauth->authenticate( $provider );

			$user_profile = $cl->getUserProfile();
			
			$repository = $this->getDoctrine()->getRepository('AppBundle:User');
			$user = $repository->findOneBy(  array('email' => $user_profile->email));
			if (!$user)
				$user = $repository->findOneBy(  array('login' => $user_profile->identifier));
			if ($user)
			{
				$session = $request->getSession();
				$session->set('access_user', $user);
				return $this->redirect($this->generateUrl('app_homepage'));
			}
			else
			{
				$user = new User();
			
				$user->setFirstName($user_profile->firstName ? $user_profile->firstName : "");
				$user->setLastName($user_profile->lastName ? $user_profile->lastName : "");
				$user->setEmail($user_profile->email);
				$user->setLogin($user_profile->identifier);
				$user->setPassword("");
				$user->setStatus(1); // uncofirmed
				$user->setZip($user_profile->zip ? $user_profile->zip : ""); // uncofirmed
				$user->setConfirmationCode(""); // uncofirmed
				$user->setCodeExpire(0); // uncofirmed
				
				$em = $this->getDoctrine()->getManager();
				$em->persist($user);
				$em->flush();
				
				$session = $request->getSession();
				$session->set('access_user', $user);
				return $this->redirect($this->generateUrl('app_homepage'));
			}
			die();
		}
		catch( Exception $e ){
			echo "Ooophs, we got an error: " . $e->getMessage();
		}
	}

	public function socialLoginGoAction()
	{
		require_once( $this->get('kernel')->getRootDir() . '/../libs/hybridauth/index.php' );
	}
	
	public function userCheckEmailAction(Request $request)
	{
		$email = $request->request->get('email');
		$error = false;
		
		$repository = $this->getDoctrine() ->getRepository('AppBundle:User');
		
		$client = $repository->findOneBy(  array('email' => $email));
		if ($client)
		{
			$error =  'This email is already in use';
		}
		if (!$this->check_email($email))
		{
			$error = 'Wrong email';
		}
		if ($error)
			return new Response(json_encode(array('error' => $error)));
		else
			return new Response(json_encode(array('ok' => true)));
	}
	
	public function userCheckLoginAction(Request $request)
	{
		$login = $request->request->get('login');
		$error = false;
		
		$repository = $this->getDoctrine() ->getRepository('AppBundle:User');
		
		$client = $repository->findOneBy(  array('login' => $login));
		if ($client)
		{
			$error =  'This login is already in use';
		}
		if ($error)
			return new Response(json_encode(array('error' => $error)));
		else
			return new Response(json_encode(array('ok' => true)));
	}

    public function confirmUserAction($code)
    {
		$repository = $this->getDoctrine() ->getRepository('AppBundle:User');
		$user = $repository->findOneBy(  array('confirmation_code' => $code));
		if ($user)
		{
			$user->setStatus(1);
			$em = $this->getDoctrine()->getManager();
			$em->flush();
		}
		return $this->render('AppBundle:pages:index.html.twig' , array('user_confirmed' => true));
	}

    public function resetPasswordAction(Request $request, $code)
    {
		$repository = $this->getDoctrine() ->getRepository('AppBundle:User');
		$user = $repository->findOneBy(  array('confirmation_code' => $code));
		if ($user)
		{
			if ($user->getCodeExpire() < time())
			{
				$user->setCodeExpire(0);
				$user->setConfirmationCode("");
				$em = $this->getDoctrine()->getManager();
				$em->flush();
				
				return $this->redirectToRoute('app_homepage');
			}
			$session = $request->getSession();
			$session->set('reset_user_id',  $user->getId());
			return $this->render('AppBundle:pages:index.html.twig' , array('show_reset' => true , "user_id" => $user->getId()));
		}
		else	return $this->redirectToRoute('app_homepage');
	}

    public function forgotPasswordAction()
    {
		$error = '';
		
		$params=array('secret' => '6Lc_lB0TAAAAAJ91zWzUr9Xa_zYwd6tOeImcPX61', 'response' => $_POST['g-recaptcha-response']);
		
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL,'https://www.google.com/recaptcha/api/siteverify');
		curl_setopt($ch, CURLOPT_POST, 1);
		curl_setopt($ch, CURLOPT_POSTFIELDS,http_build_query($params));
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

		$server_output = curl_exec ($ch);
		curl_close ($ch);
		
		$response = json_decode($server_output, true);
		
		if ($response['success'] != true)
			$error = 'Please complete the captcha verification';
		else
		{
			$repository = $this->getDoctrine() ->getRepository('AppBundle:User');
			$user = $repository->findOneBy(  array('email' => $_POST['email']));
			if ($user)
			{
				$code = rand(1,99).rand(1,99).rand(1,99).chr(rand(65,90)).rand(1,99).chr(rand(65,90)).rand(1,99).chr(rand(65,90));
				$user->setConfirmationCode($code);
				$user->setCodeExpire(time() + 1800);
				$em = $this->getDoctrine()->getManager();
				$em->flush();
				
				$host = 'http://' . $_SERVER['HTTP_HOST'];
				$confirmation_link = $this->generateUrl('app_reset_password' , array('code' => $code) , UrlGeneratorInterface::ABSOLUTE_URL);
				$message = \Swift_Message::newInstance()
					->setSubject('Reset your password')
					->setFrom('alerts@EventTrx.com')
					->setTo($_POST['email'])
					->setBody(
						$this->renderView(
							'email/forgot_password.twig',
							array( 'name' => $user->getFirstName(), 'confirmation_link' => $confirmation_link, 'host' => $host)
						),
						'text/html'
					);
				$this->get('mailer')->send($message);
			}
			else
				$error = "This email is not registered";
		}
		
		if ($error)
			return new Response(json_encode(array('error' => $error)));
		else
			return new Response(json_encode(array('ok' => true)));
	}

	public function savePasswordResetAction(Request $request)
	{
		$repository = $this->getDoctrine() ->getRepository('AppBundle:User');
		$error = "";
		
		$data = $request->request->get('data');
		if (strlen($data['password']) < 4)
		{
			$error = 'Too short password';
		}
		if ($data['password'] != $data['password_confirm'])
		{
			$error =  'Pasword does not match';
		}
		
		if (!$error)
		{
			$session = $request->getSession();
			$reset_user_id = $session->get('reset_user_id');
			
			$user = $repository->findOneBy(  array('id' => $reset_user_id));
			$user->setConfirmationCode("");
			$user->setCodeExpire(0);
			$user->setPassword($this->encrypt($data['password']));
			$em = $this->getDoctrine()->getManager();
			$em->flush();
			return new Response(json_encode(array('callback' => 'app.open_login()')));
		}
		else
			return new Response(json_encode(array('error' => $error)));
	}

	public function logoutAction(Request $request)
	{
		$session = $request->getSession();
		$session->remove('access_user');
		
		return $this->redirect($this->generateUrl('app_homepage'));
	}
	
	public function encrypt($str)
	{
		$majorsalt = '';
		$pass = str_split($str);
		foreach ($pass as $hashpass)
			$majorsalt .= bin2hex(md5($hashpass,true));
		
		$pass = bin2hex(md5($majorsalt,true));
		return $pass;
	}

	public function check_email($mail_address) {
		$pattern = '/^[\w-]+(\.[\w-]+)*@';
		$pattern .= '([0-9a-z][0-9a-z-]*[0-9a-z]\.)+([a-z]{2,4})$/i';
		if (preg_match($pattern, $mail_address)) 
		{
			$parts = explode('@', $mail_address);
			if (function_exists('checkdnsrr'))
			{
				if (checkdnsrr($parts[1], 'MX'))
				{
					return true;
				}
				else 
				{
					return false;
				}
			}
			else 
				return true;
		} 
		else 
		{
			return false;
		}
	}
}
