<?php

namespace Kuroneko\ElasticSearch\Interfaces;

interface BaseElasticSearchInterface
{
    /**
     * @return mixed
     */
    public function isConnected();

    /**
     * @return mixed
     */
    public function exists();

    /**
     * @return mixed
     */
    public function getConnection();

    /**
     * @return mixed
     */
    public function getTimeout();

    /**
     * @return mixed
     */
    public function type();

    /**
     * @return mixed
     */
    public function settings();

    /**
     * @param bool $applyAfterExecute
     * @return mixed
     */
    public function search($applyAfterExecute = true);

    /**
     * @param bool $applyAfterExecute
     * @return mixed
     */
    public function one($applyAfterExecute = true);

    /**
     * @param $id
     * @return mixed
     */
    public function findOne($id);

    /**
     * @param bool $applyAfterExecute
     * @return mixed
     */
    public function count($applyAfterExecute = true);

    /**
     * @param bool $applyAfterExecute
     * @return mixed
     */
    public function delete($applyAfterExecute = true);

    /**
     * @param $data
     * @param bool $refresh
     * @return mixed
     */
    public function insert($data, $refresh = true);

    /**
     * @return mixed
     */
    public function mapping();

    /**
     * @return string
     */
    public function index(): string;

    /**
     * @return array
     */
    public function map(): array;
}