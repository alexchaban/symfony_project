<?php

namespace AppBundle\Entity\Repository;

use Doctrine\ORM\EntityRepository;

class EventRepository extends EntityRepository
{
	public function getMyEvents($user, $search = null, $limit = null, $offset = null)
	{
		$userId = $user->getId();

		$em = $this->getEntityManager();
		$qb = $em->createQueryBuilder();
		$query = $qb->select('e')
					->from('AppBundle:Event', 'e')
					->where('e.user = :userId')
					->orderBy('e.startDateTime', 'ASC')
					->setParameter('userId', $userId);

		// we can ignore total count?! we don't use it (yet?)
		$query_total = clone $query;

		if (null !== $search)
		{
			$query->andWhere('e.title LIKE :search');
			$query->setParameter('search', '%'.$search.'%');
		}

		$query_filtered_total = clone $query;

		if (null !== $offset && null !== $limit)
		{
			$query->setFirstResult($offset);
			$query->setMaxResults($limit);
		}

		//var_dump($query->getQuery()->getSql());die;

		$result = $query->getQuery()->getResult();
		$result_total = count($query_total->getQuery()->getResult());
		$result_filtered_total = count($query_filtered_total->getQuery()->getResult());

		$response = array(
			'recordsTotal'    => $result_total,
			'recordsFiltered' => $result_filtered_total,
			'data'            => $result,
		);

		return $response;
	}
}
