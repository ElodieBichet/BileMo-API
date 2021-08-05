<?php

namespace App\Service;

use Doctrine\ORM\QueryBuilder;
use JMS\Serializer\SerializerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Cache\Adapter\FilesystemTagAwareAdapter;

class PaginationService
{
    protected $defaults;
    protected $serializer;
    protected $logger;

    public function __construct(array $defaults, SerializerInterface $serializer, LoggerInterface $logger)
    {
        $this->defaults = $defaults;
        $this->serializer = $serializer;
        $this->logger = $logger;
    }

    public function paginate(Request $request, QueryBuilder $queryBuilder, ?int $customerId = null)
    {
        $start = microtime(true);
        $entityName = $queryBuilder->getRootAliases()[0];

        // get default options defined in services.yaml
        $default_options = $this->defaults[$entityName];

        // get parameters according to the request
        $page = ($request->get('page')) ?: 1;

        $limit = (null !== $request->get('limit')) ? (int) $request->get('limit') : $default_options['limit'];

        $orderby = ($request->get('orderby')) ?: $default_options['orderby'];

        $order = $default_options['order'];
        $inverse = $request->get('inverse');
        if (null !== $inverse) {
            $order = ($inverse === "false" or $inverse === "no" or (bool) $inverse === false) ? 'ASC' : 'DESC';
        }

        // Cache init
        $cache = new FilesystemTagAwareAdapter();
        $cache_key = sprintf("%s_p%s_l%s_ord%s-%s", $entityName, $page, $limit, $orderby, $order);
        if ($customerId) $cache_key .= "_$customerId";
        $cached_data = $cache->getItem($cache_key);

        if (!$cached_data->isHit()) {
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

            $data = $queryBuilder->getQuery()->getResult();

            // Cache saving
            $cached_data->set($data);
            $cached_data->expiresAfter(3600);
            $cache->save($cached_data);
        }

        $this->logger->info("duration: " . (microtime(true) - $start));
        return $cached_data->get();
    }
}
