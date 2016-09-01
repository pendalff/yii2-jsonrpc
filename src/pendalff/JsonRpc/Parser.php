<?php

namespace pendalff\JsonRpc;

use yii\base\InvalidParamException;
use yii\httpclient\JsonParser;
use yii\httpclient\Response;
use yii\helpers\Json;

/**
 * Class Parser
 * @package JsonRpc
 */
class Parser extends JsonParser
{
    /**
     * @param Response $response
     * @return mixed
     * @throws Exception
     */
    public function parse(Response $response)
    {
        $data = null;

        try {
            $data = Json::decode($response->getContent(), false);
        } catch (InvalidParamException $e) {
            throw new Exception('JSON cannot be decoded', Exception::INTERNAL_ERROR);
        }

        if (property_exists($data, 'error')) {
            throw new Exception($data->error->message ?: $data->error->details, $data->error->code, $data->error->data);
        } elseif (property_exists($data, 'result')) {
            return $data->result;
        } else {
            throw new Exception('Invalid JSON-RPC response', Exception::INTERNAL_ERROR);
        }
    }
}
