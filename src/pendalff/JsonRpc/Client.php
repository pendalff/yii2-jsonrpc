<?php

namespace pendalff\JsonRpc;

use Yii;
use InvalidArgumentException;
use yii\helpers\ArrayHelper;
use yii\base\Configurable;
use yii\base\Component;
use yii\httpclient\Client as HttpClient;
use yii\httpclient\Request;

/**
 * Class Client
 * @link http://www.jsonrpc.org/specification
 * @package Jsonrpc
 */
class Client extends Component implements ClientInterface, Configurable
{
    /**
     * @var Request[] - requests for send
     */
    protected $requests = [];
    /**
     * @var HttpClient
     */
    protected $httpClient;
    /**
     * @var array
     */
    protected $availableViaMethods = [
        self::METHOD_POST,
        self::METHOD_GET
    ];
    /**
     * @var string
     */
    protected $viaHttpMethod = self::METHOD_POST;
    /**
     * @var string
     */
    protected $url;

    /**
     * Client constructor.
     * @param HttpClient $httpClient
     * @param array $config
     */
    public function __construct(HttpClient $httpClient, array $config = [])
    {
        if (!isset($config['url'])) {
            throw new InvalidArgumentException('JsonRpc\\Client need default endpoint URL');
        }

        if ($httpConfig = ArrayHelper::getValue($config, 'http', [])) {
            unset($config['http']);
        }
        parent::__construct($config);

        $this->httpClient = $httpClient;
        $httpConfig = ArrayHelper::merge($this->getDefaultHttpConfig(), $httpConfig);
        Yii::configure($this->httpClient, $httpConfig);
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @param $url
     * @return $this
     */
    public function setUrl($url)
    {
        $this->url = $url;
        $this->httpClient->requestConfig['url'] = $url;

        return $this;
    }

    /**
     * @param string $method
     * @return $this
     */
    public function setHttpMethod($method)
    {
        if (!in_array($method, $this->availableViaMethods)) {
            throw new InvalidArgumentException(strtr('Unrecognized http method ":method".', [':method' => $method]));
        }
        $this->viaHttpMethod = $method;

        return $this;
    }

    /**
     * @param $method
     * @param array $params
     * @return Client
     */
    public function notification($method, array $params = [])
    {
        return $this->addRequest($this->viaHttpMethod, $method, $params, null, self::TYPE_NOTIFICATION);
    }

    /**
     * @param $method
     * @param array $params
     * @param null|int|string $id
     * @return Client
     */
    public function request($method, array $params = [], $id = null)
    {
        return $this->addRequest($this->viaHttpMethod, $method, $params, $id);
    }

    /**
     * Shortcut method for send requset via POST
     * @param $method
     * @param array $params
     * @param null|int|string $id
     * @return $this
     */
    public function postRequest($method, array $params = [], $id = null)
    {
        return $this->addRequest(self::METHOD_POST, $method, $params, $id);
    }

    /**
     * shortcut method for send requset via GET
     * @param $method
     * @param array $params
     * @param null|int|string $id
     * @return $this
     */
    public function getRequest($method, array $params = [], $id = null)
    {
        return $this->addRequest(self::METHOD_GET, $method, $params, $id);
    }

    /**
     * @throws \RuntimeException
     */
    public function send()
    {
        if (empty($this->requests)) {
            throw new \RuntimeException('No requests have been set.');
        }

        $data = [];
        /** @var \yii\httpclient\Response $response */
        foreach ($this->httpClient->batchSend($this->requests) as $key => $response) {
            try {
                $data[$key] = $response->getData();
            } catch (Exception $e) {
                $e->setRequest($this->requests[$key]);
                $e->setResponse($response);
                throw $e;
            }
        }
        $this->requests = [];

        return count($data) > 1 ? $data : array_shift($data);
    }

    private function addRequest($viaHttpMethod, $method, $params, $id = null, $type = self::TYPE_REQUEST)
    {
        $requestData = [
            'jsonrpc' => '2.0',
            'method' => $method,
            'params' => $params,
            'id' => $type == self::TYPE_NOTIFICATION ? null : ($id ?: $this->createId()),
        ];
        $requestData = array_filter($requestData, function ($value) {
            return $value !== null;
        });

        $this->requests[] = $request = $this->httpClient->createRequest()
            ->setMethod($viaHttpMethod)
            ->setFormat(self::FORMAT)
            ->setData($requestData);

        return $this;
    }

    /**
     * Create a unique ID for each request.
     * @see https://tools.ietf.org/html/rfc4122 A Universally Unique IDentifier (UUID) URN Namespace
     * @return string
     */
    private function createId()
    {
        $data = openssl_random_pseudo_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // set version to 0010
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // set bits 6-7 to 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * @return array
     */
    protected function getDefaultHttpConfig()
    {
        return [
            'baseUrl' => $this->url,
            'formatters' => [
                ClientInterface::FORMAT => new Formatter([
                    'encodeOptions' => JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                ]),
            ],
            'parsers' => [
                ClientInterface::FORMAT => new Parser(),
            ],
            'requestConfig' => [
                'url' => $this->url,
                'format' => ClientInterface::FORMAT
            ],
            'responseConfig' => [
                'format' => ClientInterface::FORMAT
            ],
        ];
    }
}
