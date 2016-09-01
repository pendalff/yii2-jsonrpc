<?php

namespace pendalff\JsonRpc;

use yii\httpclient\Request;
use yii\httpclient\Response;
use yii\base\Exception as ExceptionBase;
/**
 * Class Exception
 * @package JsonRpc
 */
class Exception extends ExceptionBase
{
    const PARSE_ERROR = -32700;
    const INVALID_REQUEST = -32600;
    const METHOD_NOT_FOUND = -32601;
    const INVALID_PARAMS = -32602;
    const INTERNAL_ERROR = -32603;
    /**
     * @var null|mixed
     */
    private $data = null;
    /**
     * @var Request
     */
    private $request;
    /**
     * @var Response
     */
    private $response;

    public function __construct($message, $code, $data = null)
    {
        $this->data = $data;
        parent::__construct($message, $code);
    }

    /**
     * @return array
     */
    public function toArray()
    {
        $result = [
            'code' => $this->getCode(),
            'message' => $this->getMessage(),
        ];
        if ($this->data !== null) {
            $result['data'] = $this->data;
        }

        return $result;
    }

    /**
     * @return Request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @return Response
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param Response $response
     */
    public function setResponse($response)
    {
        $this->response = $response;
    }
}
