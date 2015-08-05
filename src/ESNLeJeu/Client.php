<?php

namespace Jhiino\ESNLeJeu;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use Jhiino\ESNLeJeu\Config\ConfigAwareInterface;
use Jhiino\ESNLeJeu\Helper\Node;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\DomCrawler\Crawler;

class Client implements ConfigAwareInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    /**
     * @var string
     */
    const BASE_URI = 'http://www.esn-lejeu.com';

    /**
     * @var string
     */
    const CONNECTION_URI = '/login.php';

    /**
     * @var GuzzleClient
     */
    protected $httpClient;

    /**
     * @var string
     */
    protected $username;

    /**
     * @var string
     */
    protected $password;

    /**
     * @var string : nom de la société
     */
    public $esnName;

    /**
     * @return GuzzleClient
     * @throws Exception
     */
    public function getConnection()
    {
        if (null === $this->httpClient) {
            $this->initConnection();
            $this->connectUser();
        }

        return $this->httpClient;
    }

    /**
     *
     */
    protected function initConnection()
    {
        if (null != $this->httpClient) {
            return;
        }

        $this->httpClient = new GuzzleClient([
            'base_uri' => self::BASE_URI,
            'cookies'  => true,
            'headers'  => [
                'Upgrade-Insecure-Requests' => '1',
                'User-Agent'                => 'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:39.0) Gecko/20100101 Firefox/39.0',
            ],
        ]);
    }

    /**
     * @throws Exception
     */
    protected function connectUser()
    {
        $post    = [
            'username' => $this->username,
            'password' => $this->password,
            'login'    => ''
        ];
        $body    = $this->post(self::CONNECTION_URI, $post);
        $crawler = new Crawler($body);

        $node = Node::nodeExists($crawler, '#intro > .navfil');

        if ($node) {
            $textToFind = sprintf('les finances de %s', $this->esnName);
            $parsedText = trim($node->html());

            if (0 !== stripos($parsedText, $textToFind)) {
                $this->logger->info('Connexion OK');

                return true;
            }
        }

        throw new Exception('Connection failed.');
    }

    /**
     * @param       $uri
     * @param array $postData
     *
     * @return string
     */
    public function post($uri, array $postData = [])
    {
        $response = $this->getConnection()->post($uri, ['form_params' => $postData]);

        return $response->getBody()->getContents();
    }

    /**
     * @param $uri
     *
     * @return string
     */
    public function get($uri)
    {
        $response = $this->getConnection()->get($uri);

        return $response->getBody()->getContents();
    }

    /**
     * @param array $parameters
     *
     * @return $this
     */
    public function applyConfig(array $parameters = [])
    {
        $parameters = array_merge($this->getDefaultConfiguration(), $parameters[$this->getConfigKey()]);

        $this->esnName  = $parameters['esn'];
        $this->username = $parameters['login'];
        $this->password = $parameters['password'];

        return $this;
    }

    /**
     * @return string
     */
    public function getConfigKey()
    {
        return 'account';
    }

    /**
     * @return array
     */
    public function getDefaultConfiguration()
    {
        return [
            'login'    => null,
            'password' => null,
            'esn'      => null,
            'email'    => null,
        ];
    }
}