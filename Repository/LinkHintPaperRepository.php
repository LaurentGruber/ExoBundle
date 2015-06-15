<?php

namespace UJM\ExoBundle\Repository;

use Doctrine\ORM\EntityRepository;

/**
 * LinkHintPaperRepository
 *
 * This class was generated by the Doctrine ORM. Add your own custom
 * repository methods below.
 */
class LinkHintPaperRepository extends EntityRepository
{

    /**
     * Allow to know if a hint is viewed in an assessment
     *
     * @access public
     *
     * @param integer $hintID id Hint
     * @param integer $paperID id Paper
     *
     * Return array[LinkHintPaper]
     */
    public function getLHP($hintID, $paperID)
    {
        $qb = $this->createQueryBuilder('lhp');
        $qb->join('lhp.paper', 'p')
            ->join('lhp.hint', 'h')
            ->where($qb->expr()->in('p.id', $paperID))
            ->andWhere($qb->expr()->in('h.id', $hintID));

        return $qb->getQuery()->getResult();
    }

    /**
     * Get hint viewed for a paper
     *
     * @access public
     *
     * @param integer $paperID id Paper
     *
     * Return array[LinkHintPaper]
     */
    public function getHintViewed($paperID)
    {
        $qb = $this->createQueryBuilder('lhp');
        $qb->where($qb->expr()->in('lhp.paper', $paperID))
            ->andWhere($qb->expr()->in('lhp.view', 1));

        return $qb->getQuery()->getResult();
    }
}
