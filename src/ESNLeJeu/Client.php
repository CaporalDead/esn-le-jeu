<?php

namespace Jhiino\ESNLeJeu;

use Exception;
use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Cookie\CookieJar;
use Jhiino\ESNLeJeu\Config\ConfigAwareInterface;
use Jhiino\ESNLeJeu\Entity\Scheduler;
use Jhiino\ESNLeJeu\Helper\Filter;
use Jhiino\ESNLeJeu\Helper\Node;
use Jhiino\ESNLeJeu\Helper\UserAgent;
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
     * @var string
     */
    const FILTER_COLORCHANGE = 'span.colorchange';

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
     * @var int
     */
    protected $maxTry = 50;

    /**
     * @var string
     */
    public $userAgent;

    /**
     * @var CookieJar
     */
    public $cookie;

    /**
     * @return GuzzleClient
     * @throws Exception
     */
    public function getConnection()
    {
        if (null === $this->httpClient) {
            $currentTry = 1;

            while ($currentTry <= $this->maxTry) {
                try {
                    $this->initConnection();
                    $this->connectUser();

                    break;
                } catch (Exception $e) {
                    $currentTry++;
                    $this->logger->warning($e->getMessage());

                    usleep(rand(1000000, 2500000));
                }
            }
        }

        return $this->httpClient;
    }

    /**
     *
     */
    protected function initConnection()
    {
        $this->userAgent = UserAgent::random();

        $this->httpClient = new GuzzleClient([
            'base_uri' => self::BASE_URI,
            'cookies'  => true,
            'headers'  => [
                'Upgrade-Insecure-Requests' => '1',
                'User-Agent'                => $this->userAgent,
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
        $node    = Node::nodeExists($crawler, '#intro > .navfil');

        if ($node) {
            $textToFind = sprintf('les finances de %s', $this->esnName);
            $parsedText = trim($node->html());

            if (0 !== stripos($parsedText, $textToFind)) {
                $this->logger->info('Connexion OK');

                return true;
            }
        }

        throw new Exception(sprintf('Connection failed with User-Agent [%s].', $this->userAgent));
    }

    /**
     * Traitement sur les données avant post ou get
     *
     * @param array $array
     *
     * @return array
     */
    protected function prepareData(array $array = [])
    {
        foreach ($array as $key => $value) {
            switch ($key) {
                // Décoder le colorchange
                case 'c':
                case 'colorchange':
                    $array[$key] = urldecode($value);
                    break;
            }
        }

        return $array;
    }

    /**
     * @param       $uri
     * @param array $postData
     *
     * @return string
     */
    public function post($uri, array $postData = [])
    {
        $options = [
            'headers'     => [
                'Host'             => "www.esn-lejeu.com",
                'User-Agent'       => $this->userAgent,
                'Accept'           => "*/*",
                'Accept-Language'  => "fr,fr-FR;q=0.8,en-US;q=0.5,en;q=0.3",
                'Accept-Encoding'  => "gzip, deflate",
                'Content-Type'     => "application/x-www-form-urlencoded; charset=UTF-8",
                'X-Requested-With' => "XMLHttpRequest",
                'Connection'       => "keep-alive",
                'Pragma'           => "no-cache",
                'Cache-Control'    => "no-cache"
            ],
            'form_params' => $this->prepareData($postData)
        ];

        $currentTry = 1;

        while ($currentTry <= $this->maxTry) {
            try {
                $response = $this->getConnection()->post($uri, $options);

                break;
            } catch (Exception $e) {
                $currentTry++;
                $this->logger->warning($e->getMessage());
            }

            Scheduler::getInstance()->waitForNextQuery();
        }

        Scheduler::getInstance()->waitForNextQuery();

        return $response->getBody()->getContents();
    }

    /**
     * @param string $uri
     * @param array  $params
     *
     * @return string
     */
    public function get($uri, array $params = [])
    {
        $options = [
            'headers' => [
                'Host'             => "www.esn-lejeu.com",
                'User-Agent'       => $this->userAgent,
                'Accept'           => "text/html, */*; q=0.01",
                'Accept-Language'  => "fr,fr-FR;q=0.8,en-US;q=0.5,en;q=0.3",
                'Accept-Encoding'  => "gzip, deflate",
                'X-Requested-With' => "XMLHttpRequest",
                'Connection'       => "keep-alive"
            ],
            'query'   => $this->prepareData($params)
        ];

        $currentTry = 1;

        while ($currentTry <= $this->maxTry) {
            try {
                $response = $this->getConnection()->get($uri, $options);

                break;
            } catch (Exception $e) {
                $currentTry++;
                $this->logger->warning($e->getMessage());
            }

            Scheduler::getInstance()->waitForNextQuery();
        }

        Scheduler::getInstance()->waitForNextQuery();

        return $response->getBody()->getContents();
    }

    /**
     * @param string     $uri
     * @param array      $get
     * @param bool|false $debug
     *
     * @return string
     * @throws Exception
     */
    public function getColorChange($uri = '', array $get = [], $debug = false)
    {
        $html    = $this->get($uri, $get, $debug);
        $crawler = new Crawler($html);
        $node    = Node::nodeExists($crawler, self::FILTER_COLORCHANGE);

        if ($node) {
            return Filter::getString($node->html());
        }

        throw new Exception('ColorChange not found !');
    }

    /**
     * @param Crawler $crawler
     *
     * @return string
     * @throws Exception
     */
    public function getColorChangeFromCrawler(Crawler $crawler)
    {
        $node = Node::nodeExists($crawler, self::FILTER_COLORCHANGE);

        if ($node) {
            return Filter::getString($node->html());
        }

        throw new Exception('ColorChange not found !');
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