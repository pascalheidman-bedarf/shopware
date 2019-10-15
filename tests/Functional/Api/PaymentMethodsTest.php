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

use Zend_Json;

/**
 * @covers \Shopware_Controllers_Api_PaymentMethods
 */
class PaymentMethodsTest extends AbstractApiTest
{
    public function getApiResource(): string
    {
        return 'PaymentMethods';
    }

    public function testRequestWithoutAuthenticationShouldReturnError()
    {
        $response = $this->getHttpClient(false)
            ->setUri($this->getApiUrl())
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

    public function testGetPaymentWithInvalidIdShouldReturnMessage()
    {
        $id = 99999999;
        $response = $this->getHttpClient()
            ->setUri($this->getApiUrl() . $id)
            ->request('GET');

        static::assertEquals('application/json', $response->getHeader('Content-Type'));
        static::assertEquals(null, $response->getHeader('Set-Cookie'));
        static::assertEquals(404, $response->getStatus());

        $result = $response->getBody();

        $result = Zend_Json::decode($result);

        static::assertArrayHasKey('success', $result);
        static::assertFalse($result['success']);

        static::assertArrayHasKey('message', $result);
    }

    public function testGetPaymentShouldBeSuccessful()
    {
        $client = $this->getHttpClient()->setUri($this->getApiUrl());
        $result = $client->request('GET');

        static::assertEquals('application/json', $result->getHeader('Content-Type'));
        static::assertEquals(null, $result->getHeader('Set-Cookie'));
        static::assertEquals(200, $result->getStatus());

        $result = $result->getBody();
        $result = Zend_Json::decode($result);

        static::assertArrayHasKey('success', $result);
        static::assertTrue($result['success']);

        static::assertArrayHasKey('data', $result);

        static::assertArrayHasKey('total', $result);
        static::assertIsInt($result['total']);

        $data = $result['data'];
        static::assertIsArray($data);
    }

    public function testPostPaymentShouldBeSuccessful()
    {
        $client = $this->getHttpClient()->setUri($this->getApiUrl());

        $requestData = [
            'name' => 'debit2',
            'description' => 'Lastschrift2',
            'position' => '6',
        ];
        $requestData = Zend_Json::encode($requestData);

        $client->setRawData($requestData, 'application/json; charset=UTF-8');
        $response = $client->request('POST');

        static::assertEquals(201, $response->getStatus());
        static::assertEquals('application/json', $response->getHeader('Content-Type'));
        static::assertNull(
            $response->getHeader('Set-Cookie'),
            'There should be no set-cookie header set.'
        );

        $result = $response->getBody();
        $result = Zend_Json::decode($result);

        static::assertArrayHasKey('success', $result);
        static::assertTrue($result['success']);

        $location = $response->getHeader('Location');
        $identifier = (int) array_pop(explode('/', $location));

        static::assertGreaterThan(0, $identifier);

        // Check ID
        $Payment = Shopware()->Models()->find('Shopware\Models\Payment\Payment', $identifier);
        static::assertGreaterThan(0, $Payment->getId());

        return $identifier;
    }

    /**
     * @depends testPostPaymentShouldBeSuccessful
     *
     * @param string $identifier
     */
    public function testGetPaymentWithIdShouldBeSuccessful($identifier)
    {
        $client = $this->getHttpClient()->setUri($this->getApiUrl() . $identifier);
        $result = $client->request('GET');

        static::assertEquals('application/json', $result->getHeader('Content-Type'));
        static::assertEquals(null, $result->getHeader('Set-Cookie'));
        static::assertEquals(200, $result->getStatus());

        $result = $result->getBody();
        $result = Zend_Json::decode($result);

        static::assertArrayHasKey('success', $result);
        static::assertTrue($result['success']);

        static::assertArrayHasKey('data', $result);

        $data = $result['data'];
        static::assertIsArray($data);
    }

    /**
     * @depends testPostPaymentShouldBeSuccessful
     *
     * @param int $id
     */
    public function testDeletePaymentWithIdShouldBeSuccessful($id)
    {
        $client = $this->getHttpClient()->setUri($this->getApiUrl() . $id);

        $response = $client->request('DELETE');

        static::assertEquals('application/json', $response->getHeader('Content-Type'));
        static::assertEquals(null, $response->getHeader('Set-Cookie'));
        static::assertEquals(200, $response->getStatus());

        $result = $response->getBody();
        $result = Zend_Json::decode($result);

        static::assertArrayHasKey('success', $result);
        static::assertTrue($result['success']);
    }

    public function testDeletePaymentWithInvalidIdShouldFailWithMessage()
    {
        $id = 9999999;
        $client = $this->getHttpClient()->setUri($this->getApiUrl() . $id);

        $response = $client->request('DELETE');

        static::assertEquals('application/json', $response->getHeader('Content-Type'));
        static::assertEquals(null, $response->getHeader('Set-Cookie'));
        static::assertEquals(404, $response->getStatus());

        $result = $response->getBody();
        $result = Zend_Json::decode($result);

        static::assertArrayHasKey('success', $result);
        static::assertFalse($result['success']);

        static::assertArrayHasKey('message', $result);
    }
}
