<?php
/**
 * Shopware 5
 * Copyright (c) shopware AG
 *
 * According to our dual licensing model, this program can be used either
 * under the terms of the GNU Affero General Public License, version 3,
 * or under a proprietary license.
 *
 * The texts of the GNU Affero General Public License with an additional
 * permission and of our proprietary license can be found at and
 * in the LICENSE file you have received along with this program.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * "Shopware" is a registered trademark of shopware AG.
 * The licensing of the program under the AGPLv3 does not imply a
 * trademark license. Therefore any rights, title and interest in
 * our trademarks remain entirely with us.
 */

namespace Shopware\Tests\Functional\Api;

use PHPUnit\Framework\TestCase;
use Zend_Http_Client;
use Zend_Http_Client_Adapter_Curl;
use Zend_Http_Client_Adapter_Exception;
use Zend_Http_Client_Exception;
use Zend_Json;

abstract class AbstractApiTest extends TestCase
{
    public $apiBaseUrl = '';

    protected $oldPasswort;

    abstract public function getApiResource(): string;

    final public function getApiUrl(): string
    {
        return $this->apiBaseUrl . '/' . $this->getApiResource() . '/';
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @before
     */
    public function prepareApiUserBefore(): void
    {
        $shop = Shopware()->Shop();

        $hostname = $shop->getHost();
        if (empty($hostname)) {
            static::markTestSkipped(
                'Hostname is not available.'
            );
        }

        $this->apiBaseUrl = ($shop->getSecure() ? 'https://' : 'http://') . $hostname . $shop->getBasePath() . '/api';

        $db = Shopware()->Db();
        $this->oldPasswort = $db->fetchOne('SELECT apiKey FROM s_core_auth WHERE username LIKE "demo"');
        $db->query('UPDATE s_core_auth SET apiKey = ? WHERE username LIKE "demo"', [sha1('demo')]);
    }

    /**
     * Resets the demo api user
     *
     * @after
     */
    public function restoreApiUserAfter(): void
    {
        Shopware()->Db()->query('UPDATE s_core_auth SET apiKey = ? WHERE username LIKE "demo"', [$this->oldPasswort]);
    }

    /**
     * @throws Zend_Http_Client_Exception
     * @throws Zend_Http_Client_Adapter_Exception
     *
     * @return Zend_Http_Client
     */
    public function getHttpClient(bool $auth = true)
    {
        $username = 'demo';
        $password = sha1('demo');

        $adapter = new Zend_Http_Client_Adapter_Curl();

        // travis CI can't handle localhost via ipv6...
        $curlOptions = [
            CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4,
        ];

        if ($auth) {
            $curlOptions += [
                CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
                CURLOPT_USERPWD => "$username:$password",
            ];
        }
        $adapter->setConfig([
            'curloptions' => $curlOptions,
        ]);

        $client = new Zend_Http_Client();
        $client->setAdapter($adapter);

        return $client;
    }

    public function testRequestWithoutAuthenticationShouldReturnError()
    {
        $response = $this->getHttpClient(false)
            ->setUri($this->apiBaseUrl . '/' . $this->getApiResource() . '/')
            ->request('GET');

        static::assertEquals('application/json', $response->getHeader('Content-Type'));
        static::assertEquals(null, $response->getHeader('Set-Cookie'));
        static::assertEquals(401, $response->getStatus());

        $result = $response->getBody();

        $result = Zend_Json::decode($result);

        static::assertArrayHasKey('success', $result);
        static::assertFalse($result['success']);

        static::assertArrayHasKey('message', $result);
    }
}
