<?php

namespace AppBundle\Entity\Repository;

/**
 * EventTypeRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class EventTypeRepository extends \Doctrine\ORM\EntityRepository
{
	public function getByName($name, $userId)
	{
		$em = $this->getEntityManager();
		$qb = $em->createQueryBuilder();
		$query = $qb->select('e')
					->from('AppBundle:EventType', 'e')
					->where(
						$qb->expr()->andX(
							$qb->expr()->eq('e.name', ':name'),
							$qb->expr()->orX(
								$qb->expr()->eq('e.userId', ':userId'),
								$qb->expr()->eq('e.userId', '0')
							)
						)
					)
					->setParameter('userId', $userId)
					->setParameter('name', $name);
		$result = $query->getQuery()->getResult();
		return $result;
	}
	
	public function getEventTypes($userId)
	{
		$em = $this->getEntityManager();
		$qb = $em->createQueryBuilder();
		$query = $qb->select('e')
					->from('AppBundle:EventType', 'e')
					->where(
						$qb->expr()->orX(
							$qb->expr()->eq('e.userId', ':userId'),
							$qb->expr()->eq('e.userId', '0')
						)
					)
					->setParameter('userId', $userId);
		$result = $query->getQuery()->getResult();
		return $result;
	}
}

// name = x and (user_id = y or user_id = 0)