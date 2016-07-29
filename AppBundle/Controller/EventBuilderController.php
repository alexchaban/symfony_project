<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Validator\Constraints\DateTime;
use AppBundle\Entity\Location;
use AppBundle\Entity\Document;
use AppBundle\Entity\Event;
use AppBundle\Entity\Organization;
use AppBundle\Entity\ParticipantType;
use AppBundle\Entity\EventInvitee;
use AppBundle\Entity\BillingAddress;
use AppBundle\Entity\Zip;
use AppBundle\Entity\EventType;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use AppBundle\Entity\File;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;

class EventBuilderController extends Controller
{
	public function addNewEventAction(Request $request, $eventId)
	{
		if ($request->isMethod('POST'))
		{
			$data = $request->get('event');

			if ($eventId == 'new')
			{
				$event = new Event;
			}
			else
			{
				$event = $this->getDoctrine()->getRepository('AppBundle:Event')->findOneBy(array("hash" => $eventId));

				if (!$event)
				{
					throw $this->createNotFoundException('The event does not exist');
				}
			}

			$inputs = array(
				'title' => 'text',
				'type' => 'text',
				'startDateTime' => 'datetime',
				'endDateTime' => 'datetime',
				'startRegDateTime' => 'datetime',
				'endRegDateTime' => 'datetime',
			);
			$validation = $this->validateInputFields($data, $inputs);
			if (!empty($validation))
			{
				$response = array('inputs' => $validation);
				return new Response(json_encode($response), 200, array('Content-Type' => 'application/json'));
			}

			$userRepo = $this->getDoctrine()->getRepository('AppBundle:User');
			$user = $this->find_me($request);

			$orgRepo = $this->getDoctrine()->getRepository('AppBundle:Organization');
			$organization = $orgRepo->findOneById($data['organizedBy']);

			$eventTypeRepo = $this->getDoctrine()->getRepository('AppBundle:EventType');
			$eventType = $eventTypeRepo->findOneById($data['type']);

			$event->setUser($user);
			$event->setType($eventType);
			$event->setTitle($data['title']);
			$event->setDescription($data['description']);

			$event->setTimezone($data['timezone']);
			$event->setOrganizedBy($organization);

			$em = $this->getDoctrine()->getManager();
			$em->persist($event);

			// files upload
			$files = $request->files->get('event');
			$errors = array();

			$deletedFiles = $request->get('event');
			$deletedFilesArr = array();

			if (isset($deletedFiles['deleted-pictures']))
			{
				foreach ($deletedFiles['deleted-pictures'] as $deletedFile)
				{
					$deletedFilesArr[] = substr($deletedFile, strpos($deletedFile, 'image/')+6);
				}
			}

			if (!empty($deletedFilesArr))
			{
				foreach ($deletedFilesArr as $deletedFile)
				{
					$image = $this->getDoctrine()->getRepository('AppBundle:File')->findOneBy(array('path' => $deletedFile));

					if ($image)
					{
						$em->remove($image);
					}
				}

				$em->flush();
			}


			if (isset($files['pictures']))
			{
				foreach ($files['pictures'] as $key => $picture)
				{
					if ($picture instanceof UploadedFile && $picture->getError() == '0')
					{
						$originalName = $picture->getClientOriginalName();
						$originalExtension = $picture->getClientOriginalExtension();
						$validFileTypes = array('jpeg', 'jpg', 'png');

						if (in_array(strtolower($originalExtension), $validFileTypes))
						{
							$type = ($key === 'logo') ? 'logo' : 'picture';

							$file = new File();
							$file->setName($originalName);
							$file->setType($type);
							$file->setFile($picture);
							$file->setEvent($event); 
							
							$em = $this->getDoctrine()->getManager();
							$em->persist($file);
						}
						else
						{
							$errors[] = array('status' => 'error', 'msg' => 'Invalid File Type');
						}
					}
					else
					{
						$errors[] = array('status' => 'error', 'msg' => 'Invalid File Format');
					}
				}
			}

			if (!empty($errors))
			{
				return new Response(json_encode($errors), 200, array('Content-Type' => 'application/json'));
			}

			$em->flush();

			$eventId = $event->getId();
			$session = $request->getSession();
			$session->set('event_id', $eventId);

			return new Response(json_encode(array('route' => $this->generateUrl('event_locations'))), 200, array('Content-Type' => 'application/json'));
			//return $this->redirectToRoute('event_locations', array(), 301);
		}
		else
		{
			$me =  $this->find_me($request);
			$eventTypeRepo = $this->getDoctrine()->getRepository('AppBundle:EventType');
			$eventTypes = $eventTypeRepo->getEventTypes($me->getId());
			$organizationRepo = $this->getDoctrine()->getRepository('AppBundle:Organization');
			$organizations = $organizationRepo->findBy(array("user" => $me));

			$event = $images = $logo = array();

			if ($eventId != 'new')
			{
				$event = $this->getDoctrine()->getRepository('AppBundle:Event')->findOneBy(array("hash" => $eventId));

				if (empty($event))
				{
					throw $this->createNotFoundException('The event does not exist');
				}
				$user = $this->find_me($request);
				if ($event->getuser()->getId() != $user->getId())
				{
					return $this->redirectToRoute('app_homepage');
				}
				
				$eventId = $event->getId();
				$images = $this->getDoctrine()->getRepository('AppBundle:File')->findBy(array('event' => $eventId, 'type' => 'picture'));
				$logo = $this->getDoctrine()->getRepository('AppBundle:File')->findOneBy(array('event' => $eventId, 'type' => 'logo'));
				
				$session = $request->getSession();
				$session->set('event_id', $eventId);
				$timezones = $this->get_timezones($event->getTimezone());
			}
			else
				$timezones = $this->get_timezones("");
			$data = array(
				'me' => $me,
				'eventTypes' => $eventTypes,
				'organizations' => $organizations,
				'event' => ($event) ? $event : 'new',
				'eventId' => $eventId,
				'eventLogo' => $logo,
				'eventImages' => $images,
				'timezones' => $timezones,
				'step' => 1
			);
			
			return $this->render('AppBundle:pages:eventbuilder/event_builder.html.twig', $data);
		}

	}
	
	public function eventLocationsAction(Request $request)
	{
		$me = $this->find_me($request);
		$eventRepository = $this->getDoctrine() ->getRepository('AppBundle:Event');
		$locationRepository = $this->getDoctrine() ->getRepository('AppBundle:Location');
		$documentRepository = $this->getDoctrine() ->getRepository('AppBundle:Document');
		$participantsRepository = $this->getDoctrine() ->getRepository('AppBundle:ParticipantType');
		$inviteeRepository = $this->getDoctrine() ->getRepository('AppBundle:EventInvitee');
		
		$session = $request->getSession();
		$eventId = $session->get('event_id');
		
		$event = $eventRepository->findOneById($eventId);
		$locations = $locationRepository->findBy(array("eventId" => $event->getId()));
		$documents = $documentRepository->findBy(array("eventId" => $event->getId()));
		$participant_types = $participantsRepository->findBy(array("event" => $event->getId()));
		$invitee = $inviteeRepository->findBy(array("event" => $event->getId()));
		if ($request->isMethod('POST')) 
		{
			$data = $request->request->get('data');
			$event->setTotalSeats($data['totalSeats']);
			$event->setRegisterTogheter($data['registerTogheter']);
			$event->setWaitingList($data['waitingList']);
			$event->setWaitingSeats($data['waitingSeats']);
			$event->setCanCancel($data['canCancel']);
			$event->setDayInformation($data['dayInformation']);
			$event->setInvitationContact($data['invitationContact']);
			$event->setHasFee(isset($data['hasFee']) ? $data['hasFee'] : 0);
			if ($data['invitationDate'])
				$event->setInvitationDate(\DateTime::createFromFormat('m/d/Y', $data['invitationDate']));
			if ($data['cancelDate'])
				$event->setCancelDate(\DateTime::createFromFormat('m/d/Y', $data['cancelDate']));
			
			$datetime = new \DateTime();
			$startDateTime = $data['startDateTime'];
			$endDateTime = $data['endDateTime'];
			$startRegDateTime = $data['startRegDateTime'];
			$endRegDateTime = $data['endRegDateTime'];

			$event->setStartDateTime($datetime->createFromFormat('m/d/Y g:i A', $startDateTime));
			$event->setEndDateTime($datetime->createFromFormat('m/d/Y g:i A', $endDateTime));
			if ($startRegDateTime)
				$event->setStartRegDateTime($datetime->createFromFormat('m/d/Y g:i A', $startRegDateTime));
			if ($endRegDateTime)
				$event->setEndRegDateTime($datetime->createFromFormat('m/d/Y g:i A', $endRegDateTime));
			
			$em = $this->getDoctrine()->getManager();
			if ($data['partycipant_type'] == 0)
			{
				$q = $em->createQuery('delete from AppBundle\Entity\ParticipantType p where p.event = ' . $event->getId());
				$q->execute();
			}
			else
			{
				$participants = $request->request->get('participant');
				$id_arr = array();
				foreach($participants['id'] as $k=>$id)
				{
					$name = $participants['name'][$k];
					$min = $participants['min'][$k];
					$max = $participants['max'][$k];
					if (!$id)
					{
						$participant = new ParticipantType;
						$participant->setName($name);
						$participant->setMinUsers($min);
						$participant->setMaxUsers($max);
						$participant->setEvent($event);
						$em->persist($participant);
					}
					else
					{
						$id_arr[] = $id;
						$participant = $participantsRepository->findOneById($id);
						$participant->setName($name);
						$participant->setMinUsers($min);
						$participant->setMaxUsers($max);
						$participant->setEvent($event);
						$em->persist($participant);
					}
				}
				foreach($participant_types as $participant_type)
				{
					$p_id = $participant_type->getId();
					if (!in_array($p_id , $id_arr))
						$em->remove($participant_type);
				}
			}
			
			$invitations = $request->request->get('invitee');
			$id_arr = array();
			foreach($invitations['id'] as $k=>$id)
			{
				$name = $invitations['name'][$k];
				$email = $invitations['email'][$k];
				if (!$id)
				{
					$invitation = new EventInvitee;
					$invitation->setName($name);
					$invitation->setEmail($email);
					$invitation->setEvent($event);
					$em->persist($invitation);
				}
				else
				{
					$id_arr[] = $id;
					$invitation = $inviteeRepository->findOneById($id);
					$invitation->setName($name);
					$invitation->setEmail($email);
					$invitation->setEvent($event);
					$em->persist($invitation);
				}
			}
			foreach($invitee as $invitation)
			{
				$p_id = $invitation->getId();
				if (!in_array($p_id , $id_arr))
					$em->remove($invitation);
			}
			
			$em->persist($event);
			$em->flush();
			
			if (!isset($data['hasFee']) || $data['hasFee'] == 0)
				return $this->redirectToRoute('event_thks');
			return $this->redirectToRoute('event_pricing');
		}
		
		$data = array(
			'me' => $me, 
			'event' => $event, 
			'locations' => $locations, 
			'documents' => $documents,
			'participant_types' => $participant_types,
			'invitee' => $invitee,
			'step' => 2
		);
		return $this->render('AppBundle:pages:eventbuilder/locations.html.twig', $data);
	}
	
	public function eventPricingAction(Request $request)
	{
		$me = $this->find_me($request);
		$eventRepository = $this->getDoctrine() ->getRepository('AppBundle:Event');
		$participantsRepository = $this->getDoctrine() ->getRepository('AppBundle:ParticipantType');
		$locationRepository = $this->getDoctrine() ->getRepository('AppBundle:Location');
		
		$session = $request->getSession();
		$eventId = $session->get('event_id');
		
		$event = $eventRepository->findOneById($eventId);
		$participant_types = $participantsRepository->findBy(array("event" => $event->getId()));
		$locations = $locationRepository->findBy(array("eventId" => $event->getId()));
		
		if ($request->isMethod('POST')) 
		{
			$em = $this->getDoctrine()->getManager();
			
			$data = $request->request->get('data');
			$event->setCanCancel($data['canCancel']);
			$event->setHasFee(isset($data['hasFee']) ? $data['hasFee'] : 0);
			if ($data['cancelDate'])
				$event->setCancelDate(\DateTime::createFromFormat('m/d/Y', $data['cancelDate']));
			
			
			if (isset($data['fees']['per_participant']) && $data['fees']['per_participant'] == 1)
			{
				foreach($request->request->get('participant') as $k=>$v)
				{
					$participant = $participantsRepository->findOneById($k);
					$participant->setFee($v);
					$em->persist($participant);
				}
			}
			$additional = array();
			$post_ad = $request->request->get('additional');
			foreach($post_ad['id'] as $k=>$v)
			{
				$amount = $post_ad['amount'][$k];
				$title = $post_ad['title'][$k];
				$refund = isset($post_ad['refund'][$k]) ? $post_ad['refund'][$k] : 0;
				$required = isset($post_ad['required'][$k]) ? $post_ad['required'][$k] : 0;
				$additional[] = array("title" => $title, "amount" => $amount, "required" => $required, "refund" => $refund);
			}
			$data['fees']['additional'] = $additional;
			
			$donations = array();
			$post_donations = $request->request->get('donation');
			foreach($post_donations['id'] as $k=>$v)
			{
				$amount = $post_donations['amount'][$k];
				$title = $post_donations['title'][$k];
				$freq = $post_donations['freq'][$k];
				$type = isset($post_donations['type'][$k]) ? $post_donations['type'][$k] : 0;
				$date = $post_donations['date'][$k];
				
				$donations[] = array("title" => $title, "amount" => $amount, "freq" => $freq, "type" => $type, "date" => $date);
			}
			$data['fees']['donations'] = $donations;
			
			
			$refund = array();
			$post_refund= $request->request->get('refund');
			foreach($post_refund['id'] as $k=>$v)
			{
				$date = $post_refund['date'][$k];
				$amount = $post_refund['amount'][$k];
				$or_amount = $post_refund['or_amount'][$k];
				
				$refund[] = array("date" => $date, "amount" => $amount, "or_amount" => $or_amount);
			}
			$data['fees']['refund'] = $refund;
			
			$event->setFees($data['fees']);
			$em->persist($event);
			$em->flush();
			
			return $this->redirectToRoute('event_thks');
		}
		
		$fees = $event->getFees();
		if (!isset($fees['additional']) || !is_array($fees['additional']) || !count($fees['additional']))
		{
			$fees['additional'] = array(array('title' => '', 'amount' =>'', 'refund' => 0, 'required' => 0));
		}
		if (!isset($fees['donations']) || !is_array($fees['donations']) || !count($fees['donations']))
		{
			$fees['donations'] = array(array('title' => '', 'freq' =>'', 'date' => '', 'type' => '', 'amount' => ''));
		}
		if (!isset($fees['refund']) || !is_array($fees['refund']) || !count($fees['refund']))
		{
			$fees['refund'] = array(array('date' => '', 'amount' =>'', 'or_amount' => ''));
		}
		if (!isset($fees['per_participant']))
			$fees['per_participant'] = null;
		
		$event->setFees($fees);
		$data = array(
			'me' => $me, 
			'event' => $event, 
			'participant_types' => $participant_types,
			'locations' => $locations,
			'step' => 3
		);
		return $this->render('AppBundle:pages:eventbuilder/pricing.html.twig', $data);
	}
	
	public function previewAction(Request $request, $hash)
	{
		$me = $this->find_me($request);
		$eventRepository = $this->getDoctrine() ->getRepository('AppBundle:Event');
		$eventTypeRepo = $this->getDoctrine()->getRepository('AppBundle:EventType');
		$locationRepository = $this->getDoctrine() ->getRepository('AppBundle:Location');
		
		$eventTypes = $eventTypeRepo->getEventTypes($me->getId());
		$organizationRepo = $this->getDoctrine()->getRepository('AppBundle:Organization');
		$organizations = $organizationRepo->findBy(array("user" => $me));
		
		$images = $logo = array();
			
		$event = $eventRepository->findOneBy(array("hash" => $hash));
		$images = $this->getDoctrine()->getRepository('AppBundle:File')->findBy(array('event' => $event->getId(), 'type' => 'picture'));
		$logo = $this->getDoctrine()->getRepository('AppBundle:File')->findOneBy(array('event' => $event->getId(), 'type' => 'logo'));
				
		$locations = $locationRepository->findBy(array("eventId" => $event->getId()));
		$data = array(
			'me' => $me,
			'event' => $event,
			'images' => $images,
			'logo' => $logo,
			'locations' => $locations
		);
		return $this->render('AppBundle:pages:eventbuilder/preview.html.twig', $data);
	}
			
	public function eventSubscriptionAction(Request $request)
	{
		$me = $this->find_me($request);
		$eventRepository = $this->getDoctrine() ->getRepository('AppBundle:Event');
		
		$session = $request->getSession();
		$eventId = $session->get('event_id');
		
		$event = $eventRepository->findOneById($eventId);
		if ($request->isMethod('POST')) 
		{
			return $this->redirectToRoute('event_thks');
		}
		$data = array(
			'me' => $me,
			'event' => $event,
			'step' => 4
		);
		return $this->render('AppBundle:pages:eventbuilder/subscription.html.twig', $data);
	}
			
	public function eventThksAction(Request $request)
	{
		$me = $this->find_me($request);
		$eventRepository = $this->getDoctrine() ->getRepository('AppBundle:Event');
		
		$session = $request->getSession();
		$eventId = $session->get('event_id');
		
		$event = $eventRepository->findOneById($eventId);
		$data = array(
			'me' => $me,
			'event' => $event,
			'step' => 5
		);
		return $this->render('AppBundle:pages:eventbuilder/thks.html.twig', $data);
	}
		
	public function addLocationAction(Request $request)
	{
		$repository = $this->getDoctrine() ->getRepository('AppBundle:Event');
		$session = $request->getSession();
		$eventId = $session->get('event_id');
		$event = $repository->findOneById($eventId);
		
		$error = null;
		
		if ($request->isMethod('POST')) 
		{
			$data = $request->request->get('data');
			
			$repository = $this->getDoctrine() ->getRepository('AppBundle:Zip');
		
			$zip_row = $repository->findOneBy(  array('zip' => $data['zip']));
			if (!$zip_row)
			{
				$error =  'Zip Code is Incorrect';
			}
			elseif (strlen($data['purpose']) < 2)
			{
				$error = "Too short purpose";
			}
			
			if (!$error)
			{
				$location = new Location();
			
				$location->setPurpose($data['purpose']);
				$location->setAddress($data['address']);
				$location->setAddress2($data['address_2']);
				$location->setCity($data['city']);
				$location->setZip($data['zip']);
				$location->setShowInEvent(isset($data['showInEvent']) ? $data['showInEvent'] : 0); 
				$location->setPreferred(isset($data['preferred']) ? $data['preferred'] : 0); 
				$location->setEventId($event); 
				$location->setInfo($data['info']); 
				$location->setState($data['state']); 
				
				$em = $this->getDoctrine()->getManager();
				$em->persist($location);
				$em->flush();
				$data['id'] = $location->getId();
				
				return new Response(json_encode($data));
			}
			else
			{
				return new Response(json_encode(array("error" => $error)));
			}
		}
		else
		{
			return $this->render('AppBundle:pages:eventbuilder/add_location.html.twig' , array("states" => $this->states));
		}
	}	
	
	public function deleteLocationAction(Request $request)
	{
		$repository = $this->getDoctrine() ->getRepository('AppBundle:Location');
		
		if ($request->isMethod('POST')) 
		{
			$id = $request->request->get('id');
			$location = $repository->findOneById($id);
			
			$em = $this->getDoctrine()->getManager();
			$em->remove($location);
			$em->flush();
			
			return new Response(json_encode(array("success" => true)));
		}
	}
	

	public function addDocumentAction(Request $request)
	{
		$repository = $this->getDoctrine() ->getRepository('AppBundle:Event');
		$session = $request->getSession();
		$eventId = $session->get('event_id');
		$event = $repository->findOneById($eventId);
		
		if ($request->isMethod('POST')) 
		{
			$data = $request->request->get('data');
			$file = $request->files->get('document_file');
 
			if ($file instanceof UploadedFile && $file->getError() == '0')
			{
				if ($file->getSize() < 150000000)
				{
					$originalName = $file->getClientOriginalName();
					$originalExtension = $file->getClientOriginalExtension();
					$validFileTypes = array('jpeg', 'jpg', 'png', 'pdf', 'doc', 'docx', 'ppt', 'xls', 'xlsx');
	 
					if (in_array(strtolower($originalExtension), $validFileTypes))
					{
						$document = new Document();
					
						$document->setName($data['name']);
						$document->setInformation($data['information']);
						$document->setOptions($data['options']);
						$document->setIsRequired($data['isRequired']);
						$document->setFile($file);
						$document->setEventId($event); 
						
						$em = $this->getDoctrine()->getManager();
						$em->persist($document);
						$em->flush();
						$data['id'] = $document->getId();
						
						$data['display'] = $data['options'] == "event" ? "Event page" : ($data['options'] == "registration" ? "Registration page" : "Both");
						$data['required'] = $data['isRequired'] == 1 ? "Yes" : "No";
						return new Response(json_encode($data));
					}
					else
						return new Response(json_encode(array("error" => "Wrong file extendion")));
				}
				else
					return new Response(json_encode(array("error" => "Too big file")));
			}
			else
				return new Response(json_encode(array("error" => "No file was uploaded")));
		}
		else
		{
			return $this->render('AppBundle:pages:eventbuilder/add_document.html.twig');
		}
	}	
	
	public function deleteDocumentAction(Request $request)
	{
		$repository = $this->getDoctrine() ->getRepository('AppBundle:Document');
		
		if ($request->isMethod('POST')) 
		{
			$id = $request->request->get('id');
			$document = $repository->findOneById($id);
			
			$em = $this->getDoctrine()->getManager();
			$em->remove($document);
			$em->flush();
			
			return new Response(json_encode(array("success" => true)));
		}
	}

	public function addOrganizationAction(Request $request)
	{
		$me = $this->find_me($request);
		if ($request->isMethod('POST')) 
		{
			$data = $request->request->get('data');
			
			if (!$data['name'] || !$data['description'] || !$data['type'] || !$data['contactName'] || !$data['contactEmail'] || !$data['contactPhone'])
				return new Response(json_encode(array("error" => "Please complete all the fields")));
			
			$organization = new Organization();
		
			$organization->setName($data['name']);
			$organization->setDescription($data['description']);
			$organization->setType($data['type']);
			$organization->setContactName($data['contactName']);
			$organization->setContactEmail($data['contactEmail']);
			$organization->setContactPhone($data['contactPhone']);
			$organization->setContactFax($data['contactFax']);
			$organization->setContactWebPage($data['contactWebPage']);
			
			$organization->setUser($me); 
			
			$em = $this->getDoctrine()->getManager();
			$em->persist($organization);
			$em->flush();
			$data['id'] = $organization->getId();
			
			return new Response(json_encode($data));
		}
		else
		{
			return $this->render('AppBundle:pages:eventbuilder/add_organization.html.twig');
		}
	}	
	
	public function addEventTypeAction(Request $request)
	{
		$eventTypeRepo = $this->getDoctrine()->getRepository('AppBundle:EventType');
		$me = $this->find_me($request);
		if ($request->isMethod('POST')) 
		{
			$data = $request->request->get('data');
			
			if (!$data['name'])
				return new Response(json_encode(array("error" => "Please complete all the fields")));
			
			$flague = $eventTypeRepo->getByName($data['name'], $me->getId());
			if (count($flague))
				return new Response(json_encode(array("error" => "This event type allready exists")));
			
			$eventType = new EventType();
		
			$eventType->setName($data['name']);
			$eventType->setUserId($me->getId());
			
			$em = $this->getDoctrine()->getManager();
			$em->persist($eventType);
			$em->flush();
			$data['id'] = $eventType->getId();
			
			return new Response(json_encode($data));
		}
		else
		{
			return $this->render('AppBundle:pages:eventbuilder/add_event_type.html.twig');
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
		$repository = $this->getDoctrine() ->getRepository('AppBundle:user');
		$user = $repository->findOneById($user->getId());
		return $user;
	}
	
	function checkZipAction(Request $request)
	{
		$zip = $request->request->get('zip');
		$error = false;
		
		$repository = $this->getDoctrine() ->getRepository('AppBundle:Zip');
		
		$zip_row = $repository->findOneBy(  array('zip' => $zip));
		if (!$zip_row)
		{
			$error =  'Zip Code is Incorrect';
		}
		if ($error)
			return new Response(json_encode(array('error' => $error)));
		else
			return new Response(json_encode(array('ok' => true)));
	}

	function validateInputFields($data, $inputs)
	{
		$response = array();

		foreach ($data as $key => $value)
		{
			if (array_key_exists($key, $inputs))
			{
				switch ($inputs[$key]) {
					case 'text':
						if (empty($data[$key]))
							$response[] = array('status' => 'error', 'key' => $key);
						break;

					case 'datetime':
							$validation = $this->validateDate($data[$key]);
							if (empty($data[$key]) || $validation) {
								$response[] = array('status' => 'error', 'key' => $key);
							}
						break;

					default:
						//
						break;
				}
			}
		}

		return $response;
	}

	function validateDate($date)
	{
		$d = \DateTime::createFromFormat('Y-m-d', $date);
		return $d && $d->format('Y-m-d') === $date;
	}
	
	function get_timezones($pr_code)
	{
		$final = '';
		$final .= '<option value="" disabled="disabled" >Select Timezone</option>';
		
		foreach ($this->timezones as $tz_name=>$tz_code)
			$final .= '<option value="'.$tz_code.'" '.(($tz_code==$pr_code)?'selected="selected"':'').'>'.$tz_name . '</option>';
		return $final;
	}
	
	public $timezones = array (
		'(GMT-11:00) Midway Island' => 'Pacific/Midway',
		'(GMT-11:00) Samoa' => 'Pacific/Samoa',
		'(GMT-10:00) Hawaii' => 'Pacific/Honolulu',
		'(GMT-09:00) Alaska' => 'US/Alaska',
		'(GMT-08:00) Pacific Time (US &amp; Canada)' => 'America/Los_Angeles',
		'(GMT-08:00) Tijuana' => 'America/Tijuana',
		'(GMT-07:00) Arizona' => 'US/Arizona',
		'(GMT-07:00) Chihuahua' => 'America/Chihuahua',
		'(GMT-07:00) La Paz' => 'America/Chihuahua',
		'(GMT-07:00) Mazatlan' => 'America/Mazatlan',
		'(GMT-07:00) Mountain Time (US &amp; Canada)' => 'US/Mountain',
		'(GMT-06:00) Central America' => 'America/Managua',
		'(GMT-06:00) Central Time (US &amp; Canada)' => 'US/Central',
		'(GMT-06:00) Guadalajara' => 'America/Mexico_City',
		'(GMT-06:00) Mexico City' => 'America/Mexico_City',
		'(GMT-06:00) Monterrey' => 'America/Monterrey',
		'(GMT-06:00) Saskatchewan' => 'Canada/Saskatchewan',
		'(GMT-05:00) Bogota' => 'America/Bogota',
		'(GMT-05:00) Eastern Time (US &amp; Canada)' => 'US/Eastern',
		'(GMT-05:00) Indiana (East)' => 'US/East-Indiana',
		'(GMT-05:00) Lima' => 'America/Lima',
		'(GMT-05:00) Quito' => 'America/Bogota',
		'(GMT-04:00) Atlantic Time (Canada)' => 'Canada/Atlantic',
		'(GMT-04:30) Caracas' => 'America/Caracas',
		'(GMT-04:00) La Paz' => 'America/La_Paz',
		'(GMT-04:00) Santiago' => 'America/Santiago',
		'(GMT-03:30) Newfoundland' => 'Canada/Newfoundland',
		'(GMT-03:00) Brasilia' => 'America/Sao_Paulo',
		'(GMT-03:00) Buenos Aires' => 'America/Argentina/Buenos_Aires',
		'(GMT-03:00) Georgetown' => 'America/Argentina/Buenos_Aires',
		'(GMT-03:00) Greenland' => 'America/Godthab',
		'(GMT-02:00) Mid-Atlantic' => 'America/Noronha',
		'(GMT-01:00) Azores' => 'Atlantic/Azores',
		'(GMT-01:00) Cape Verde Is.' => 'Atlantic/Cape_Verde',
		'(GMT+00:00) Casablanca' => 'Africa/Casablanca',
		'(GMT+00:00) Edinburgh' => 'Europe/London',
		'(GMT+00:00) Greenwich Mean Time : Dublin' => 'Etc/Greenwich',
		'(GMT+00:00) Lisbon' => 'Europe/Lisbon',
		'(GMT+00:00) London' => 'Europe/London',
		'(GMT+00:00) Monrovia' => 'Africa/Monrovia',
		'(GMT+00:00) UTC' => 'UTC',
		'(GMT+01:00) Amsterdam' => 'Europe/Amsterdam',
		'(GMT+01:00) Belgrade' => 'Europe/Belgrade',
		'(GMT+01:00) Berlin' => 'Europe/Berlin',
		'(GMT+01:00) Bern' => 'Europe/Berlin',
		'(GMT+01:00) Bratislava' => 'Europe/Bratislava',
		'(GMT+01:00) Brussels' => 'Europe/Brussels',
		'(GMT+01:00) Budapest' => 'Europe/Budapest',
		'(GMT+01:00) Copenhagen' => 'Europe/Copenhagen',
		'(GMT+01:00) Ljubljana' => 'Europe/Ljubljana',
		'(GMT+01:00) Madrid' => 'Europe/Madrid',
		'(GMT+01:00) Paris' => 'Europe/Paris',
		'(GMT+01:00) Prague' => 'Europe/Prague',
		'(GMT+01:00) Rome' => 'Europe/Rome',
		'(GMT+01:00) Sarajevo' => 'Europe/Sarajevo',
		'(GMT+01:00) Skopje' => 'Europe/Skopje',
		'(GMT+01:00) Stockholm' => 'Europe/Stockholm',
		'(GMT+01:00) Vienna' => 'Europe/Vienna',
		'(GMT+01:00) Warsaw' => 'Europe/Warsaw',
		'(GMT+01:00) West Central Africa' => 'Africa/Lagos',
		'(GMT+01:00) Zagreb' => 'Europe/Zagreb',
		'(GMT+02:00) Athens' => 'Europe/Athens',
		'(GMT+02:00) Bucharest' => 'Europe/Bucharest',
		'(GMT+02:00) Cairo' => 'Africa/Cairo',
		'(GMT+02:00) Harare' => 'Africa/Harare',
		'(GMT+02:00) Helsinki' => 'Europe/Helsinki',
		'(GMT+02:00) Istanbul' => 'Europe/Istanbul',
		'(GMT+02:00) Jerusalem' => 'Asia/Jerusalem',
		'(GMT+02:00) Kyiv' => 'Europe/Helsinki',
		'(GMT+02:00) Pretoria' => 'Africa/Johannesburg',
		'(GMT+02:00) Riga' => 'Europe/Riga',
		'(GMT+02:00) Sofia' => 'Europe/Sofia',
		'(GMT+02:00) Tallinn' => 'Europe/Tallinn',
		'(GMT+02:00) Vilnius' => 'Europe/Vilnius',
		'(GMT+03:00) Baghdad' => 'Asia/Baghdad',
		'(GMT+03:00) Kuwait' => 'Asia/Kuwait',
		'(GMT+03:00) Minsk' => 'Europe/Minsk',
		'(GMT+03:00) Nairobi' => 'Africa/Nairobi',
		'(GMT+03:00) Riyadh' => 'Asia/Riyadh',
		'(GMT+03:00) Volgograd' => 'Europe/Volgograd',
		'(GMT+03:30) Tehran' => 'Asia/Tehran',
		'(GMT+04:00) Abu Dhabi' => 'Asia/Muscat',
		'(GMT+04:00) Baku' => 'Asia/Baku',
		'(GMT+04:00) Moscow' => 'Europe/Moscow',
		'(GMT+04:00) Muscat' => 'Asia/Muscat',
		'(GMT+04:00) St. Petersburg' => 'Europe/Moscow',
		'(GMT+04:00) Tbilisi' => 'Asia/Tbilisi',
		'(GMT+04:00) Yerevan' => 'Asia/Yerevan',
		'(GMT+04:30) Kabul' => 'Asia/Kabul',
		'(GMT+05:00) Islamabad' => 'Asia/Karachi',
		'(GMT+05:00) Karachi' => 'Asia/Karachi',
		'(GMT+05:00) Tashkent' => 'Asia/Tashkent',
		'(GMT+05:30) Chennai' => 'Asia/Calcutta',
		'(GMT+05:30) Kolkata' => 'Asia/Kolkata',
		'(GMT+05:30) Mumbai' => 'Asia/Calcutta',
		'(GMT+05:30) New Delhi' => 'Asia/Calcutta',
		'(GMT+05:30) Sri Jayawardenepura' => 'Asia/Calcutta',
		'(GMT+05:45) Kathmandu' => 'Asia/Katmandu',
		'(GMT+06:00) Almaty' => 'Asia/Almaty',
		'(GMT+06:00) Astana' => 'Asia/Dhaka',
		'(GMT+06:00) Dhaka' => 'Asia/Dhaka',
		'(GMT+06:00) Ekaterinburg' => 'Asia/Yekaterinburg',
		'(GMT+06:30) Rangoon' => 'Asia/Rangoon',
		'(GMT+07:00) Bangkok' => 'Asia/Bangkok',
		'(GMT+07:00) Hanoi' => 'Asia/Bangkok',
		'(GMT+07:00) Jakarta' => 'Asia/Jakarta',
		'(GMT+07:00) Novosibirsk' => 'Asia/Novosibirsk',
		'(GMT+08:00) Beijing' => 'Asia/Hong_Kong',
		'(GMT+08:00) Chongqing' => 'Asia/Chongqing',
		'(GMT+08:00) Hong Kong' => 'Asia/Hong_Kong',
		'(GMT+08:00) Krasnoyarsk' => 'Asia/Krasnoyarsk',
		'(GMT+08:00) Kuala Lumpur' => 'Asia/Kuala_Lumpur',
		'(GMT+08:00) Perth' => 'Australia/Perth',
		'(GMT+08:00) Singapore' => 'Asia/Singapore',
		'(GMT+08:00) Taipei' => 'Asia/Taipei',
		'(GMT+08:00) Ulaan Bataar' => 'Asia/Ulan_Bator',
		'(GMT+08:00) Urumqi' => 'Asia/Urumqi',
		'(GMT+09:00) Irkutsk' => 'Asia/Irkutsk',
		'(GMT+09:00) Osaka' => 'Asia/Tokyo',
		'(GMT+09:00) Sapporo' => 'Asia/Tokyo',
		'(GMT+09:00) Seoul' => 'Asia/Seoul',
		'(GMT+09:00) Tokyo' => 'Asia/Tokyo',
		'(GMT+09:30) Adelaide' => 'Australia/Adelaide',
		'(GMT+09:30) Darwin' => 'Australia/Darwin',
		'(GMT+10:00) Brisbane' => 'Australia/Brisbane',
		'(GMT+10:00) Canberra' => 'Australia/Canberra',
		'(GMT+10:00) Guam' => 'Pacific/Guam',
		'(GMT+10:00) Hobart' => 'Australia/Hobart',
		'(GMT+10:00) Melbourne' => 'Australia/Melbourne',
		'(GMT+10:00) Port Moresby' => 'Pacific/Port_Moresby',
		'(GMT+10:00) Sydney' => 'Australia/Sydney',
		'(GMT+10:00) Yakutsk' => 'Asia/Yakutsk',
		'(GMT+11:00) Vladivostok' => 'Asia/Vladivostok',
		'(GMT+12:00) Auckland' => 'Pacific/Auckland',
		'(GMT+12:00) Fiji' => 'Pacific/Fiji',
		'(GMT+12:00) International Date Line West' => 'Pacific/Kwajalein',
		'(GMT+12:00) Kamchatka' => 'Asia/Kamchatka',
		'(GMT+12:00) Magadan' => 'Asia/Magadan',
		'(GMT+12:00) Marshall Is.' => 'Pacific/Fiji',
		'(GMT+12:00) New Caledonia' => 'Asia/Magadan',
		'(GMT+12:00) Solomon Is.' => 'Asia/Magadan',
		'(GMT+12:00) Wellington' => 'Pacific/Auckland',
		'(GMT+13:00) Nuku\'alofa' => 'Pacific/Tongatapu'
		);
		
	public $states = array(
		'AL'=>'Alabama',
		'AK'=>'Alaska',
		'AZ'=>'Arizona',
		'AR'=>'Arkansas',
		'CA'=>'California',
		'CO'=>'Colorado',
		'CT'=>'Connecticut',
		'DE'=>'Delaware',
		'DC'=>'District of Columbia',
		'FL'=>'Florida',
		'GA'=>'Georgia',
		'HI'=>'Hawaii',
		'ID'=>'Idaho',
		'IL'=>'Illinois',
		'IN'=>'Indiana',
		'IA'=>'Iowa',
		'KS'=>'Kansas',
		'KY'=>'Kentucky',
		'LA'=>'Louisiana',
		'ME'=>'Maine',
		'MD'=>'Maryland',
		'MA'=>'Massachusetts',
		'MI'=>'Michigan',
		'MN'=>'Minnesota',
		'MS'=>'Mississippi',
		'MO'=>'Missouri',
		'MT'=>'Montana',
		'NE'=>'Nebraska',
		'NV'=>'Nevada',
		'NH'=>'New Hampshire',
		'NJ'=>'New Jersey',
		'NM'=>'New Mexico',
		'NY'=>'New York',
		'NC'=>'North Carolina',
		'ND'=>'North Dakota',
		'OH'=>'Ohio',
		'OK'=>'Oklahoma',
		'OR'=>'Oregon',
		'PA'=>'Pennsylvania',
		'RI'=>'Rhode Island',
		'SC'=>'South Carolina',
		'SD'=>'South Dakota',
		'TN'=>'Tennessee',
		'TX'=>'Texas',
		'UT'=>'Utah',
		'VT'=>'Vermont',
		'VA'=>'Virginia',
		'WA'=>'Washington',
		'WV'=>'West Virginia',
		'WI'=>'Wisconsin',
		'WY'=>'Wyoming',
	);
}
