<?php

namespace pendalff\JsonRpc;

/**
 * Interface ClientInterface
 * @package JsonRpc
 */
interface ClientInterface
{
    const FORMAT = 'json-rpc';

    const METHOD_POST = 'post';
    const METHOD_GET = 'get';

    const TYPE_REQUEST = 'request';
    const TYPE_NOTIFICATION = 'notification';

    /**
     *
     * @param $url
     * @return ClientInterface|static
     */
    public function setUrl($url);

    /**
     * @return string - endpoint URL
     */
    public function getUrl();

    /**
     * @param $viaHttpMethod
     * @return  ClientInterface|static
     */
    public function setHttpMethod($viaHttpMethod);

    /**
     * Send notification via http POST
     * @param $method
     * @param array $params
     * @return ClientInterface|static
     */
    public function notification($method, array $params = []);

    /**
     * Send request via http POST
     * @param $method
     * @param array $params
     * @param int|string|null $id
     * @return ClientInterface|static
     */
    public function request($method, array $params = [], $id = null);

    /**
     * @throws \RuntimeException
     * @return mixed
     */
    public function send();
}
