<?php

namespace App\Service;

use Doctrine\ORM\EntityRepository;
use Symfony\Component\HttpFoundation\Request;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

class PaginationService
{
    protected $defaults;

    public function __construct(array $defaults)
    {
        $this->defaults = $defaults;
    }

    public function paginate(Request $request, string $entityName, EntityRepository $entityRepository)
    {
        $default_options = $this->defaults[$entityName];

        $page = ($request->get('page')) ?: 1;
        $limit = (null !== $request->get('limit')) ? (int) $request->get('limit') : $default_options['limit'];
        $orderby = ($request->get('orderby')) ?: $default_options['orderby'];
        $order = $default_options['order'];
        $inverse = $request->get('inverse');
        if (null !== $inverse) {
            $order = ($inverse === "false" or $inverse === "no" or (bool) $inverse === false) ? 'ASC' : 'DESC';
        }

        $queryBuilder = $entityRepository->createQueryBuilder('item')
            ->orderBy('item.' . $orderby, $order);

        if ((bool) $limit) {
            $offset = (int) ($page - 1) * $limit;
            $queryBuilder
                ->setMaxResults($limit)
                ->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
