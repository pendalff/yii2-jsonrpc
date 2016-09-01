<?php

namespace pendalff\JsonRpc;

use yii\httpclient\JsonFormatter;
use yii\httpclient\UrlEncodedFormatter;
use yii\httpclient\Request;
use yii\helpers\Json;

/**
 * Class Formatter
 * @link http://www.jsonrpc.org/historical/json-rpc-over-http.html#get
 * @package JsonRpc
 */
class Formatter extends JsonFormatter
{
    /**
     * Accept HTTP header
     * @var string
     */
    public $accept = 'application/json-rpc, application/json, */*';
    /**
     * Url encode type for GET requests
     * @var int PHP_QUERY_RFC3986|PHP_QUERY_RFC1738
     */
    public $encodingType = PHP_QUERY_RFC3986;
    /**
     * Need send jsonrpc param via GET
     * @var bool
     */
    public $sendVersion = false;
    /**
     * Escape id param symbol (for strings only!)
     * @var bool
     */
    public $escapeId = '"';

    /**
     * @param Request $request
     * @return Request
     */
    public function format(Request $request)
    {
        if ($request->getMethod() == ClientInterface::METHOD_GET) {
            $data = $request->getData();
            $data['params'] = base64_encode(Json::encode($data['params'], $this->encodeOptions));
            if (!$this->sendVersion) {
                unset($data['jsonrpc']);
            }
            if ($this->escapeId && is_string($data['id'])) {
                $data['id'] = $this->escapeId . $data['id'] . $this->escapeId;
            }
            $request->setData($data);
            $formatter = new UrlEncodedFormatter([
                'encodingType' => $this->encodingType
            ]);
            $request = $formatter->format($request);
        } else {
            $request->setContent(Json::encode($request->getData(), $this->encodeOptions));
        }

        $request->getHeaders()->set('Content-Type', 'application/json-rpc; charset=UTF-8');
        $request->getHeaders()->set('Accept', $this->accept);

        return $request;
    }
}
