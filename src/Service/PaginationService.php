<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Doctrine\ORM\QueryBuilder;

class PaginationService
{
    protected $defaults;

    public function __construct(array $defaults)
    {
        $this->defaults = $defaults;
    }

    public function paginate(Request $request, QueryBuilder $queryBuilder)
    {
        $entityName = $queryBuilder->getRootAliases()[0];

        // get default options defined in services.yaml
        $default_options = $this->defaults[$entityName];

        // get parameters according to the request
        $page = ($request->get('page')) ?: 1;
        $limit = (null !== $request->get('limit')) ? (int) $request->get('limit') : $default_options['limit'];

        $orderby = ($request->get('orderby')) ?: $default_options['orderby'];
        $orderby = ($orderby == "quantity") ? "availableQuantity" : $orderby;
        $orderby = ($orderby == "date") ? "createdAt" : $orderby;

        $order = $default_options['order'];
        $inverse = $request->get('inverse');
        if (null !== $inverse) {
            $order = ($inverse === "false" or $inverse === "no" or (bool) $inverse === false) ? 'ASC' : 'DESC';
        }

        // Update query builder with options
        $queryBuilder
            ->orderBy("$entityName.$orderby", $order);

        // If there is a limit, paginate results
        if ((bool) $limit) {
            $offset = (int) ($page - 1) * $limit;
            $queryBuilder
                ->setMaxResults($limit)
                ->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }
}
