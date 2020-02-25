<?php


namespace Kuroneko\ElasticSearch\Traits;

use Kuroneko\ElasticSearch\Abstracts\BaseElasticSearchAbstract;

/**
 * Trait ElasticSearchQueryTrait
 * @package Kuroneko\ElasticSearch\Traits
 * @author Giang Nguyen
 * @mixin BaseElasticSearchAbstract
 */
trait ElasticSearchQueryTrait
{
    /**
     * @var array
     */
    private $query;

    /**
     * @var bool
     */
    private $andWhere = false;

    /**
     * Pre call function before execute a main method
     * @param $method
     * @param $arguments
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        if (method_exists($this, $method)) {
            if (in_array($method, $this->allowBeforeCall()))
                $this->initQuery();
            return call_user_func_array(array($this, $method), $arguments);
        }
    }

    /**
     * init the query array for elastic search
     * @return array
     */
    private function initQuery()
    {
        if (is_null($this->query)) {
            $this->query = [];
        }
        return $this->query;
    }

    /**
     * @return $this
     */
    public function find(): self
    {
        $query = $this->query;
        $query['index'] = $this->index();
        $this->query = $query;
        return $this;
    }

    /**
     * @return array
     */
    public function buildQuery(): array
    {
        return $this->query;
    }

    /**
     * ['field', '=', value']
     * ['field', 'in', [value, value,...]]
     * ['field', 'between', [value1, valued2]]
     *
     * @param array $condition
     * @return $this
     */
    public function where(array $condition): self
    {
        list($field, $operation, $value) = $condition;

        $operation = strtoupper($operation);
        switch ($operation) {
            case 'IN':
            {
                $this->resolveInCondition($field, $value);
                break;
            }
            case 'NOT_IN';
            {
                $this->resolveNotInCondition($field, $value);
                break;
            }
            case 'BETWEEN';
            {
                $this->resolveBetweenCondition($field, $value);
                break;
            }
            case 'LIKE';
            {
                $this->resolveLikeCondition($field, $value);
                break;
            }
            default:
            {
                $this->resolveDefaultCondition($field, $operation, $value);
                break;
            }
        }
        return $this;
    }


    /**
     * @param array $condition
     * @return $this
     */
    public function andWhere(array $condition): self
    {
        $this->andWhere = true;
        $this->where($condition);
        $this->andWhere = false;
        return $this;
    }

    /**
     * @param string $field
     * @param string $condition
     * @param $value
     */
    private function resolveDefaultCondition(string $field, string $condition, $value)
    {
        $query = $this->andWhere ? $this->query : $this->initQuery();
        if ($condition == '<>') {
            $query['body']['query']['bool']['must_not'][] = ['term' => [$field => $value]];
        } else if ($condition == '>') {
            $query['body']['query']['bool']['must'][] = ['range' => [$field => ['gt' => $value]]];
        } else if ($condition == '>=') {
            $query['body']['query']['bool']['must'][] = ['range' => [$field => ['gte' => $value]]];
        } else if ($condition == '<') {
            $query['body']['query']['bool']['must'][] = ['range' => [$field => ['lt' => $value]]];
        } else if ($condition == '<=') {
            $query['body']['query']['bool']['must'][] = ['range' => [$field => ['lte' => $value]]];
        } else {
            $query['body']['query']['bool']['must'][] = ['match' => [$field => $value]];
        }
        $this->query = $query;
    }

    /**
     * @param string $field
     * @param array $value
     */
    private function resolveInCondition(string $field, array $value)
    {
        $query = $this->andWhere ? $this->query : $this->initQuery();
        $query['body']['query']['bool']['filter'] = ['terms' => [$field => $value]];
        $this->query = $query;
    }

    /**
     * @param string $field
     * @param array $value
     */
    private function resolveNotInCondition(string $field, array $value)
    {
        $query = $this->andWhere ? $this->query : $this->initQuery();
        $query['body']['query']['bool']['must_not'] = ['terms' => [$field => $value]];
        $this->query = $query;
    }

    /**
     * @param $field
     * @param $value
     */
    private function resolveBetweenCondition($field, $value)
    {
        $query = $this->andWhere ? $this->query : $this->initQuery();
        $query['body']['query']['bool']['must'][] = ['range' => [$field => ['gte' => $value[0], 'lte' => $value[1]]]];
        $this->query = $query;
    }

    /**
     * @param $field
     * @param $value
     */
    private function resolveLikeCondition($field, $value)
    {
        $query = $this->andWhere ? $this->query : $this->initQuery();
        if (!empty($value)) {
            if (is_string($value)) {
                $tmp = [
                    'query' => $value,
                    'operator' => 'and',
                    'minimum_should_match' => '65%',
                ];
            } else {
                $tmp = [
                    'query' => $value['value'],
                    'operator' => 'and',
                    'analyzer' => $value['analyzer'],
                    'minimum_should_match' => '65%',
                ];
            }
            $query['body']['query']['bool']['must'][] = [
                'match' => [
                    $field => $tmp
                ]
            ];
        }
        $this->query = $query;
    }


    /**
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit): self
    {
        $this->query['size'] = $limit;
        return $this;
    }

    /**
     * @param $offset
     * @return $this
     */
    public function offset($offset): self
    {
        $this->query['from'] = $offset;
        return $this;
    }

    /**
     * @param array $order
     * @return $this
     */
    public function orderBy(array $order): self
    {
        $order = $this->resolveOrder($order);
        $query = $this->query;
        $query['body']['sort'][$order['field']] = $order['by'];
        $this->query = $query;
        return $this;
    }

    /**
     * @return array
     */
    private function allowBeforeCall()
    {
        return [
            'find',
            'offset',
            'limit',
            'where',
            'andWhere',
            'buildQuery'
        ];
    }

    /**
     * @return $this
     */
    private function resetQuery(): self
    {
        $this->query = [];
        return $this;
    }

    /**
     * @param $field
     * @return bool
     */
    private function checkTextField($field)
    {
        $isTextField = false;
        $arr = $this->map();
        foreach ($arr as $key => $item) {
            if (!empty($item['properties'])) {
                $this->a($item);
            } else {
                if ($field == $key && $item['type'] == 'text') {
                    $isTextField = true;
                    break;
                }
            }
        }
        return $isTextField;
    }

    /**
     * @param $order
     * @return array
     */
    private function resolveOrder($order)
    {
        if (is_string($order)) {
            $orderField = $order;
            $orderBy = 'asc';
        } else {
            $orderField = array_keys($order)[0];
            $orderBy = $order[$orderField];
        }
        if ($this->checkTextField($orderField)) {
            $orderField = $orderField . '.keyword';
        }
        return [
            'field' => $orderField,
            'by' => $orderBy
        ];
    }
}