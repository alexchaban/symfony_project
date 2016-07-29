<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use AppBundle\Entity\Event;

class MyEventsController extends Controller
{
	public function eventsAction(Request $request)
	{
		$me = $this->find_me($request);
		$data = array(
			'me' => $me,
			'page_title' => 'My events',
		);

		$params = $request->query->all();

		$search = (isset($params['s']) ? $params['s'] : null);
		$page = (isset($params['p']) ? $params['p'] : null);
		$limit = 5;
		$offset = 0;

		if (null !== $page)
		{
			$offset = abs(($limit * $page) - $limit);
		}

		$em = $this->getDoctrine()->getManager();
		$events = $em->getRepository('AppBundle:Event')->getMyEvents($me, $search, $limit, $offset);

		$data['events'] = $events;

		return $this->render('AppBundle:pages:my_events.html.twig', $data);
	}

	public function deleteEventAction(Request $request, $eventId)
	{
		if ($request->isXmlHttpRequest())
		{
			$em = $this->getDoctrine()->getManager();
			$event = $em->getRepository('AppBundle:Event')->findOneById($eventId);

			$response = array();

			if ($event)
			{
				try {
					$files = $em->getRepository('AppBundle:File')->findBy(array('event' => $event));
					$documents = $em->getRepository('AppBundle:Document')->findBy(array('eventId' => $event));
					$locations = $em->getRepository('AppBundle:Location')->findBy(array('eventId' => $event));
					foreach ($files as $file)
					{
						$em->remove($file);
					}
					foreach ($documents as $document)
					{
						$em->remove($document);
					}
					foreach ($locations as $location)
					{
						$em->remove($location);
					}
					$em->flush();
					$em->remove($event);
					$em->flush();

					$response = array('status' => 'success', 'msg' => 'Event successfully deleted');
				} catch (\Exception $e) {
					$this->get('logger')->error($e->getMessage());
					$response = array('status' => 'error', 'msg' => 'Can\'t delete this event');
					//$response = array($e->getMessage());
				}
			}
			else
			{
				$response = array('status' => 'error', 'msg' => 'No such event');
			}

			return new Response(json_encode($response), 200, array('Content-Type' => 'application/json'));
		}
		else
		{
			throw new HttpException(406, "You can't access this page directly.");
		}
	}

	public function cancelEventAction(Request $request, $eventId)
	{
		if ($request->isXmlHttpRequest())
		{
			$em = $this->getDoctrine()->getManager();
			$event = $em->getRepository('AppBundle:Event')->findOneById($eventId);

			$response = array();

			if ($event)
			{
				try {
					$event->setStatus(2);
					$em->flush();

					$response = array('status' => 'success', 'msg' => 'Event successfully canceled');
				} catch (\Exception $e) {
					$this->get('logger')->error($e->getMessage());
					$response = array('status' => 'error', 'msg' => 'Can\'t delete this event');
					//$response = array($e->getMessage());
				}
			}
			else
			{
				$response = array('status' => 'error', 'msg' => 'No such event');
			}

			return new Response(json_encode($response), 200, array('Content-Type' => 'application/json'));
		}
		else
		{
			throw new HttpException(406, "You can't access this page directly.");
		}
	}

	public function copyEventAction(Request $request, $eventId)
	{
		$em = $this->getDoctrine()->getManager();
		$event = $em->getRepository('AppBundle:Event')->findOneById($eventId);

		if ($event)
		{
			$newEvent = clone $event;
			$em->persist($newEvent);
			$em->flush();

			$newEventId = $newEvent->getId();

			return $this->redirectToRoute('app_new_event', array('eventId' => $newEventId), 301);
		}
		else
		{
			throw $this->createNotFoundException('The event doesn\'t exist');
		}
	}

	public function viewImageAction($path)
	{
		$repository = $this->getDoctrine()->getRepository('AppBundle:File');
		$image = $repository->findOneBy(array('path' => $path));

		if ($image)
		{
			$filename = $image->getAbsolutePath();

			$response = new Response();
			$response->headers->set('Cache-Control', 'private');
			$response->headers->set('Content-type', mime_content_type($filename));
			$response->headers->set('Content-Disposition', 'inline; filename="' . basename($filename) . '";');
			$response->headers->set('Content-length', filesize($filename));
			$response->sendHeaders();
			$response->setContent(file_get_contents($filename));

			return $response;
		}
		else
		{
			throw new HttpException(404, "Image not found");
			
		}
	}

	function find_me(Request $request)
	{
		$session = $request->getSession();
		$user = $session->get('access_user');
		
		if (!$user)
		{
			return $this->redirect($this->generateUrl("app_homepage"));
		}
		return $user;
	}
}
