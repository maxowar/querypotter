<?php declare(strict_types=1);

/*
 * This file is part of QueryPotter.
 *
 * (c) Massimo Naccari <sendto@massimonaccari.it>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace QueryPotter\Query;

use Doctrine\ORM\QueryBuilder;
use function Symfony\Component\String\u;

/**
 * Automagically build a Query
 */
class Filter
{
    protected QueryBuilder $query;

    protected array $configuration;

    /**
     * @param QueryBuilder        $query
     * @param FilterConfiguration $params
     */
    public function __construct(QueryBuilder $query, FilterConfiguration $params)
    {
        $this->query = $query;
        $this->configuration = $params->getConfiguration();
    }

    public function build(): QueryBuilder
    {
        $this->buildFilters();

        $this->buildOrder();

        $this->buildGroup();

        $this->buildLimit();

        return $this->query;
    }

    protected function buildOrder(): void
    {
        if (!empty($this->configuration['order'])) {
            foreach ($this->configuration['order'] as $order) {
                if (strrpos($order['by'], '.') !== false) {
                    $orderByField = $order['by'];
                } else {
                    $orderByField = $this->query->getRootAliases()[0].'.'.$order['by'];
                }

                $this->query->addOrderBy($orderByField, $order['dir']);
            }
        }
    }

    protected function buildGroup(): void
    {
        if (!empty($this->configuration['group_by'])) {
            $this->query->groupBy($this->configuration['group_by']);
        }
    }

    protected function buildLimit(): void
    {
        if ($this->configuration['items']) {
            $this->query
                ->setFirstResult($this->configuration['items'] * ($this->configuration['page'] - 1))
                ->setMaxResults($this->configuration['items']);
        }
    }


    protected function buildFilters(): void
    {
        foreach ($this->configuration['filters'] as $field => $value) {
            if (!is_string($field)) {
                throw new \InvalidArgumentException('$field must be a string representing a real or virtual field name');
            }

            if ($this->skipNullValue() && $value === null) {
                continue;
            }

            $methodName = method_exists($this, $this->getFilterCallbackName($field)) ?
                $this->getFilterCallbackName($field) :
                'genericFilterField'
            ;
            $this->$methodName($field, $value);
        }
    }

    protected function getFilterCallbackName($field): string
    {
        return 'filter'. u($field)->camel()->title();
    }

    protected function skipNullValue(): bool
    {
        return $this->configuration['skip_null_values'];
    }

    private function genericFilterField($field, $value): void
    {
        switch ($this->getOperator($field)) {
            case 'eq':
            case 'neq':
            case 'lt':
            case 'lte':
            case 'gt':
            case 'gte':
                $this->andWhereFieldAndPlaceholder($field, $value);
                break;
            case 'isNull':
            case 'isNotNull':
                $this->andWhereField($field);
                break;
            case 'like':
            case 'notLike':
                $this->andWhereLike($field, $value);
                break;
            case 'in':
            case 'notIn':
                $this->andWhereIn($field, $value);
                break;
            default:
                throw new \InvalidArgumentException('Unknown operator');
        }
    }

    private function andWhereFieldAndPlaceholder($field, $value): void
    {
        $key = $this->alreadyContainsAlias($field) ? $field : $this->query->getRootAliases()[0] . '.' . $field;
        $placeholder = ':' . $this->sanitizePlaceholder($field);

        $this->query->andWhere(
            $this->query->expr()->{$this->getOperator($field)}(
                $key,
                $placeholder
            )
        );

        $this->query->setParameter($placeholder, $value);
    }

    private function sanitizePlaceholder($string): string
    {
        return str_replace('.', '_', $string);
    }

    private function alreadyContainsAlias($string): bool
    {
        return str_contains($string, '.');
    }

    private function andWhereField($field): void
    {
        $this->query->andWhere(
            $this->query->expr()->{$this->getOperator($field)}($this->query->getRootAliases()[0].'.'.$field)
        );
    }

    private function andWhereLike($field, $value): void
    {
        $this->query->andWhere(
            $this->query->expr()->{$this->getOperator($field)}(
                $this->query->getRootAliases()[0].'.'.$field,
                $this->query->expr()->literal("%{$value}%")
            )
        );
    }

    private function andWhereIn($field, $value): void
    {

        if (!empty($value) && is_object($value[0]) && method_exists($value[0], 'getId')) {
            $value = array_map(function ($item) {
                return $item->getId();
            }, $value);
        }

        $this->query->andWhere(
            $this->query->expr()->{$this->getOperator($field)}(
                $this->query->getRootAliases()[0].'.'.$field,
                (array) $value
            )
        );
    }

    public function getOperatorsMapping(): array
    {
        return $this->configuration['operators'];
    }

    protected function getOperator($field)
    {
        return $this->configuration['operators'][$field] ?? 'eq';
    }

    protected function join($tableName, $tableAlias, $method = 'innerJoin', $conditionType = null, $condition = null)
    {
        if (!$this->isJoined($tableName)) {
            $this->query->addSelect($tableAlias)->{$method}($tableName, $tableAlias, $conditionType, $condition);
        }
    }

    protected function isJoined($tableName): bool
    {
        if (empty($this->query->getDQLParts()['join'])) {
            return false;
        }

        foreach ($this->query->getDQLParts()['join'][$this->query->getRootAliases()[0]] as $tableAlreadyJoined) {
            if ($tableAlreadyJoined->getJoin() == $tableName) {
                return true;
            }
        }

        return false;
    }

    public function setOperators(array $operators = []): void
    {
        $this->configuration['operators'] = $operators;
    }
}
