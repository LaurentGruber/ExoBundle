<?php

namespace UJM\ExoBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * InteractionQCMRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class InteractionQCMRepository extends EntityRepository
{

    /**
     * Get InteractionQCM linked with an interaction
     *
     * @access public
     *
     * @param integer $interactionId id Interaction
     *
     * Return array[InteractionQCM]
     */
    public function getInteractionQCM($interactionId)
    {
        $qb = $this->createQueryBuilder('iqcm');
        $qb->join('iqcm.interaction', 'i')
            ->where($qb->expr()->in('i.id', $interactionId));

        return $qb->getQuery()->getSingleResult();
    }
}
