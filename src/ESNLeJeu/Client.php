<?php

namespace Jhiino\ESNLeJeu;

use Exception;
use Guzzle\Plugin\Cookie\CookieJar\ArrayCookieJar;
use Guzzle\Plugin\Cookie\CookiePlugin;
use Guzzle\Service\Client as GuzzleClient;
use Jhiino\ESNLeJeu\Config\ConfigAwareInterface;
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

        $this->httpClient = new GuzzleClient(self::BASE_URI);
        $cookieJar        = new ArrayCookieJar();
        $cookiePlugin     = new CookiePlugin($cookieJar);

        $this->httpClient->addSubscriber($cookiePlugin);
        $this->httpClient->setUserAgent('Mozilla/5.0 (Windows NT 6.1; WOW64; rv:31.0) Gecko/20130401 Firefox/31.0');
    }

    /**
     * @throws Exception
     */
    protected function connectUser()
    {
        // Essai de connexion
        $post         = [
            'username' => $this->username,
            'password' => $this->password,
            'login'    => ''
        ];
        $loginRequest = $this->httpClient->post(self::CONNECTION_URI, [], $post);

        // Vérification de la connexion
        $responseLogin = $loginRequest->send();
        $body          = $responseLogin->getBody(true);
        $crawler       = new Crawler($body);

        if (null == $crawler->filter('#intro')->filter('h1:contains("les finances de ' . $this->esnName . '")')->getNode(0)) {
            throw new Exception('Connection failed.');
        }

        $this->logger->info('Connexion OK');
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