<?php declare(strict_types=1);

/*
 * This file is part of QueryPotter.
 *
 * (c) Massimo Naccari <sendto@massimonaccari.it>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace QueryPotter\Test\Query;

use Doctrine\ORM\QueryBuilder;
use QueryPotter\Query\Filter;
use QueryPotter\Query\FilterConfiguration;
use QueryPotter\Test\TestCase;


class FilterTest extends TestCase
{

    public function testBuildWithEmptyParams()
    {
        $filter = new Filter($this->getQueryBuilderMock(), new FilterConfiguration([]));
        $this->assertInstanceOf(QueryBuilder::class, $filter->build());
    }

    public function testBuildGroupByParams()
    {
        $queryBuilderMock = $this->getQueryBuilderMock();
        $queryBuilderMock->expects($this->once())->method('groupBy');
        $filter = new Filter($queryBuilderMock, new FilterConfiguration(['group_by' => 'alias.id']));
        $filter->build();
    }

    public function testBuildLimitParams()
    {
        $queryBuilderMock = $this->getQueryBuilderMock();
        $queryBuilderMock->expects($this->once())->method('setFirstResult')->with(25);
        $queryBuilderMock->expects($this->once())->method('setMaxResults')->with(25);
        $filter = new Filter($queryBuilderMock, new FilterConfiguration(['items' => 25, 'page' => 2]));
        $filter->build();
    }

    public function testBuildOrderParams()
    {
        $queryBuilderMock = $this->getQueryBuilderMock();
        $queryBuilderMock->expects($this->once())->method('addOrderBy');
        $filter = new Filter($queryBuilderMock, new FilterConfiguration([
            'order' => [
                ['by'  => 'created_at']
            ]
        ]));
        $filter->build();
    }

    public function testBuildFilters()
    {
        $queryBuilderMock = $this->getQueryBuilderMock();
        $queryBuilderMock->expects($this->once())->method('andWhere');
        $filter = new Filter($queryBuilderMock, new FilterConfiguration([
            'filters' => ['id'  => 1]
        ]));
        $filter->build();
    }

    public function testSetOperators()
    {
        $filter = new Filter($this->getQueryBuilderMock(), new FilterConfiguration([]));
        $filter->setOperators([
            'field1' => 'eq'
        ]);
        $this->assertEquals('eq', $filter->getOperatorsMapping()['field1']);
    }
}
