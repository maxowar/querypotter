<?php declare(strict_types=1);

/*
 * This file is part of QueryPotter.
 *
 * (c) Massimo Naccari <sendto@massimonaccari.it>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace QueryPotter\Repository;

use Doctrine\Inflector\InflectorFactory;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\QueryBuilder;
use QueryPotter\Query\Filter;
use QueryPotter\Query\FilterConfiguration;

abstract class QueryPottery extends EntityRepository
{
    public function getDefaultQuery(): QueryBuilder
    {
        return $this->createQueryBuilder($this->getRootAlias());
    }

    public function getRootAlias(): string
    {
        return InflectorFactory::create()->build()
            ->tableize(substr($this->getEntityName(), 1 + strrpos($this->getClassName(), '\\')));
    }

    public function getFilteredQueryBuilder(array $params = [], ?QueryBuilder $queryBuilder = null): QueryBuilder
    {
        if (!$queryBuilder) {
            $queryBuilder = $this->getDefaultQuery();
        }

        $filterClass = $this->getFilterClassName();

        $querySearch = new $filterClass(
            $queryBuilder,
            new FilterConfiguration($params)
        );

        return $querySearch->build();
    }

    public function getFilterClassName(): string
    {
        return Filter::class;
    }

    public function findOrThrow($id, $errorClassName)
    {
        if (!$entity = $this->find($id)) {
            throw new $errorClassName;
        }

        return $entity;
    }
}
