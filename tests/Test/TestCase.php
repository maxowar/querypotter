<?php declare(strict_types=1);

/*
 * This file is part of QueryPotter.
 *
 * (c) Massimo Naccari <sendto@massimonaccari.it>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace QueryPotter\Test;

use Doctrine\ORM\Query\Expr;
use Doctrine\ORM\QueryBuilder;
use PHPUnit\Framework\MockObject\MockObject;

abstract class TestCase extends \PHPUnit\Framework\TestCase
{
    public function getQueryBuilderMock(): MockObject|QueryBuilder
    {
        $queryBuilder = $this->getMockBuilder(QueryBuilder::class)->disableOriginalConstructor()->getMock();
        $queryBuilder->method('setFirstResult')->willReturn($queryBuilder);
        $queryBuilder->method('expr')->willReturn(new Expr());
        return $queryBuilder;
    }
}