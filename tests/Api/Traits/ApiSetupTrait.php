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

namespace Shopware\Tests\Api\Traits;

use Zend_Http_Client;
use Zend_Http_Client_Adapter_Curl;
use Zend_Http_Client_Adapter_Exception;

trait ApiSetupTrait
{
    public $apiBaseUrl = '';

    protected $oldPasswort;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @before
     */
    public function prepareApiUserBefore(): void
    {
        $helper = Shopware();

        $hostname = $helper->Shop()->getHost();
        if (empty($hostname)) {
            static::markTestSkipped(
                'Hostname is not available.'
            );
        }

        $this->apiBaseUrl = 'http://' . $hostname . $helper->Shop()->getBasePath() . '/api';

        $db = $helper->Db();
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
     * @throws Zend_Http_Client_Adapter_Exception
     * @throws \Zend_Http_Client_Exception
     *
     * @return Zend_Http_Client
     */
    public function getHttpClient(bool $auth = true)
    {
        $username = 'demo';
        $password = sha1('demo');

        $adapter = new Zend_Http_Client_Adapter_Curl();
        if ($auth) {
            $adapter->setConfig([
                'curloptions' => [
                    CURLOPT_HTTPAUTH => CURLAUTH_DIGEST,
                    CURLOPT_USERPWD => "$username:$password",
                ],
            ]);
        }

        $client = new Zend_Http_Client();
        $client->setAdapter($adapter);

        return $client;
    }
}
