<?php

namespace Kuroneko\ElasticSearch\Abstracts;

use Elasticsearch\Client;
use Elasticsearch\ClientBuilder;
use Kuroneko\ElasticSearch\Exceptions\ConfigIsInvalidException;
use Kuroneko\ElasticSearch\Exceptions\MethodIsInvalidException;
use Kuroneko\ElasticSearch\Interfaces\BaseElasticSearchInterface;
use Kuroneko\ElasticSearch\Traits\ElasticSearchQueryTrait;
use Kuroneko\ElasticSearch\Traits\PrintLogTrait;
use Kuroneko\ElasticSearch\Traits\WriteLogTrait;

/**
 * Class BaseElasticSearchAbstract
 * @package Kuroneko\Yii2ElasticSearch\Abstracts
 * @author Giang Nguyen
 */
abstract class BaseElasticSearchAbstract implements BaseElasticSearchInterface
{

    use ElasticSearchQueryTrait, PrintLogTrait;

    /**
     * @var array|bool|false|string|null
     */
    private $server;

    /**
     * @var Client
     */
    private $currentConnection;

    /**
     * @var
     */
    private $timeout;

    /**
     * BaseElasticSearchAbstract constructor.
     * @param $server
     * @param string $timeout
     */
    public function __construct($server, $timeout = '1s')
    {
        try {
            if (empty($server))
                throw new ConfigIsInvalidException('Please provide elastic server config');

            $this->server = $server;
            $this->timeout = $timeout;

            if (empty($this->index()) || empty($this->type()))
                throw new MethodIsInvalidException('Index or type is invalid, please check this.');

            if (!empty($this->server)) {
                $this->currentConnection = ClientBuilder::create()
                    ->setHosts([
                        $this->server
                    ])->build();
            }

            if ($this->currentConnection && $this->isConnected() && !$this->exists() && !empty($this->map())) {
                $this->mapping();
            }
        } catch (\Exception $exception) {
            $this->printException($exception);
        }
    }

    /**
     * @param $data
     * @return mixed
     */
    private function applyAfterExecute($data)
    {
        $this->resetQuery();
        return $data;
    }

    /**
     * @param $data
     * @return array
     */
    private function transformDataSearch($data)
    {
        try {
            $result = [];
            $total = !empty($data['hits']) && !empty($data['hits']['total']) && !empty($data['hits']['total']['value']) ? $data['hits']['total']['value'] : 0;
            if (!empty($data['hits']['hits'])) {
                $hits = $data['hits']['hits'];
                $step = 0;

                while ($step < count($hits)) {
                    if (!empty($hits[$step]['_source'])) {
                        $result[] = $hits[$step]['_source'];
                    }
                    $step++;
                }
            }

            return [
                'data' => $result,
                'total' => $total
            ];
        } catch (\Exception $exception) {
            $this->printException($exception);
            return [
                'data' => [],
                'total' => 0
            ];
        }
    }

    /**
     * Check all things needed for get data from elastic search
     * @return bool
     */
    private function checkPrerequisiteBeforeExecute()
    {
        return $this->isConnected() && $this->exists() && !empty($this->query) && !empty($this->index()) && !empty($this->type());
    }

    /**
     * @param int $length
     * @return string
     */
    private function generateId($length = 50)
    {
        $str = '1234567890qazwsxedcrfvtgbyhnujmiklopQAZWSXEDCRFVTGBYHNUJMIKLOP';
        $result = '';
        for ($i = 0; $i < $length; $i++) {
            $result .= $str[rand(0, ($length - 1))];
        }
        return $result;
    }

    /**
     * @return bool
     */
    public function isConnected()
    {
        if (empty($this->currentConnection)) return false;
        return empty($this->currentConnection) !== true || $this->currentConnection->ping() !== false;
    }

    /**
     * @return bool
     */
    public function exists()
    {
        if ($this->isConnected())
            return $this->getConnection()
                ->indices()
                ->exists(['index' => $this->index()]);
        else
            return false;
    }

    /**
     * @return Client|ClientBuilder
     */
    public function getConnection()
    {
        return $this->currentConnection;
    }

    /**
     * @return string
     */
    public function getTimeout()
    {
        return $this->timeout;
    }

    /**
     * @return string
     */
    public function type()
    {
        return '_doc';
    }

    /**
     * @return array
     */
    public function settings()
    {
        return [];
    }

    /**
     * @param $applyAfterExecute
     * @return array
     */
    public function search($applyAfterExecute = true)
    {
        $data = [];
        try {
            if ($this->checkPrerequisiteBeforeExecute()) {
                if (empty($this->query['index']))
                    $this->query['index'] = $this->index();
                $data = $this->getConnection()->search($this->query);
            }
            return $applyAfterExecute ?
                $this->applyAfterExecute($this->transformDataSearch($data)) :
                $this->transformDataSearch($data);
        } catch (\Exception $exception) {
            $this->printException($exception);
            return $data;
        }
    }

    /**
     * @param bool $applyAfterExecute
     * @return array|mixed
     */
    public function one($applyAfterExecute = true)
    {
        try {
            $data = $this->search($applyAfterExecute);
            if (!empty($data) && !empty($data['data'][0])) {
                return $data['data'][0];
            } else {
                return [];
            }
        } catch (\Exception $exception) {
            $this->printException($exception);
            return [];
        }
    }

    /**
     * @param $id
     * @return array|mixed
     */
    public function findOne($id)
    {
        try {
            return $this->find()
                ->where(['id', '=', $id])->one();
        } catch (\Exception $exception) {
            $this->printException($exception);
            return [];
        }
    }

    /**
     * @param $applyAfterExecute
     * @return mixed
     */
    public function count($applyAfterExecute = true)
    {
        $count = 0;
        try {
            $query = $this->query;
            if ($this->checkPrerequisiteBeforeExecute()) {
                $query['index'] = $this->index();

                if (isset($query['size']))
                    unset($query['size']);

                if (isset($query['from']))
                    unset($query['from']);

                if (isset($query['body']['sort']))
                    unset($query['body']['sort']);

                $count = $this->getConnection()->count($query);
                if (isset($count['count']))
                    $count = $count['count'];

            }
            return $applyAfterExecute ?
                $this->applyAfterExecute($count) :
                $count;
        } catch (\Exception $exception) {
            $this->printException($exception);
            return $count;
        }
    }

    /**
     * @param $applyAfterExecute
     * @return bool
     */
    public function delete($applyAfterExecute = true)
    {
        try {
            if ($this->isConnected()) {
                $query = $this->query;
                $result = $this->getConnection()->deleteByQuery($query);
                $result = !empty($result) && isset($result['deleted']) && $result['deleted'] != 0;
                return $applyAfterExecute ? $this->applyAfterExecute($result) : $result;
            } else {
                return false;
            }
        } catch (\Exception $exception) {
            $this->printException($exception);
            return false;
        }
    }

    /**
     * @param $data
     * @param $refresh
     * @return bool
     */
    public function insert($data, $refresh = true)
    {
        try {
            if ($this->isConnected()) {
                $params = [
                    'index' => $this->index(),
                    'type' => $this->type(),
                    'id' => $this->generateId(),
                    'timeout' => $this->getTimeout(),
                    'body' => $data
                ];

                if ($refresh === true) {
                    $params['refresh'] = $refresh;
                }
                $result = $this->getConnection()->index($params);
                return !empty($result) && !empty($result['_shards']) && isset($result['_shards']['successful']) && $result['_shards']['successful'] == 1;

            } else {
                return false;
            }
        } catch (\Exception $exception) {
            $this->printException($exception);
            return false;
        }
    }

    /**
     * @return bool
     */
    public function mapping()
    {
        try {
            if ($this->isConnected()) {
                $params = [
                    'index' => $this->index(),
                    'body' => [
                        'mappings' => [
                            '_source' => [
                                'enabled' => true
                            ],
                            'properties' => $this->map()
                        ]
                    ]
                ];
                if (!empty($this->settings())) {
                    $params['body']['settings'] = $this->settings();
                }
                $this->getConnection()->indices()->create($params);
            } else {
                return false;
            }
        } catch (\Exception $exception) {
            $this->printException($exception);
            return false;
        }
    }

    /**
     * @return string
     */
    public abstract function index(): string;

    /**
     * @return array
     */
    public abstract function map(): array;
}