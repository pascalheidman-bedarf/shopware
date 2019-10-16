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

use Shopware\Components\Api\Resource\Media;
use Zend_Json;

/**
 * @covers \Shopware_Controllers_Api_Media
 */
class MediaTest extends AbstractApiTest
{
    const UPLOAD_FILE_NAME = 'test-bild';
    const UPLOAD_OVERWRITTEN_FILE_NAME = 'a-different-file-name';

    public function getApiResource(): string
    {
        return 'media';
    }

    public function testGetMediaWithInvalidIdShouldReturnMessage()
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

    public function testGetMediaShouldBeSuccessful()
    {
        $client = $this->getHttpClient()->setUri($this->apiBaseUrl . '/media');
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

    public function testPostMediaWithoutImageShouldFailWithMessage()
    {
        $client = $this->getHttpClient()->setUri($this->apiBaseUrl . '/media');

        $requestData = [
            'album' => -1,
            'description' => 'flipflops',
        ];
        $requestData = Zend_Json::encode($requestData);

        $client->setRawData($requestData, 'application/json; charset=UTF-8');
        $response = $client->request('POST');

        static::assertEquals('application/json', $response->getHeader('Content-Type'));
        static::assertEquals(null, $response->getHeader('Set-Cookie'));
        static::assertEquals(400, $response->getStatus());

        $result = $response->getBody();
        $result = Zend_Json::decode($result);

        static::assertArrayHasKey('success', $result);
        static::assertFalse($result['success']);

        static::assertArrayHasKey('message', $result);
    }

    public function testPostMediaShouldBeSuccessful()
    {
        $client = $this->getHttpClient()->setUri($this->apiBaseUrl . '/media');

        $requestData = [
            'album' => -1,
            'file' => 'http://assets.shopware.com/sw_logo_white.png',
            'description' => 'flipflops',
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

        // Check userId
        $media = Shopware()->Models()->find('Shopware\Models\Media\Media', $identifier);
        static::assertGreaterThan(0, $media->getUserId());

        return $identifier;
    }

    /**
     * @depends testPostMediaShouldBeSuccessful
     */
    public function testGetMediaWithIdShouldBeSuccessful($identifier)
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
        static::assertArrayHasKey('attribute', $data);
    }

    /**
     * @depends testPostMediaShouldBeSuccessful
     */
    public function testDeleteMediaWithIdShouldBeSuccessful($id)
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

    public function testPostMediaWithFileUploadShouldBeSuccessful()
    {
        $client = $this->getHttpClient()->setUri($this->apiBaseUrl . '/media');

        $fileSource = __DIR__ . '/fixtures/' . self::UPLOAD_FILE_NAME . '.jpg';
        $requestData = [
            'album' => -1,
            'description' => 'flipflops',
        ];

        $client->setFileUpload($fileSource, 'file');
        $client->setParameterPost($requestData);
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

        return $identifier;
    }

    /**
     * @depends testPostMediaWithFileUploadShouldBeSuccessful
     */
    public function testGetMediaWithUploadedFileByIdShouldBeSuccessful($identifier)
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
        static::assertArrayHasKey('name', $data);
        static::assertEquals(0, strpos($data['name'], self::UPLOAD_FILE_NAME));
    }

    public function testPostMediaWithFileUploadAndOverwrittenNameShouldBeSuccessful()
    {
        $client = $this->getHttpClient()->setUri($this->apiBaseUrl . '/media');

        $fileSource = __DIR__ . '/fixtures/' . self::UPLOAD_FILE_NAME . '.jpg';
        $requestData = [
            'album' => -1,
            'description' => 'flipflops',
            'name' => self::UPLOAD_OVERWRITTEN_FILE_NAME,
        ];

        $client->setFileUpload($fileSource, 'file');
        $client->setParameterPost($requestData);
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

        return $identifier;
    }

    /**
     * @depends testPostMediaWithFileUploadAndOverwrittenNameShouldBeSuccessful
     */
    public function testGetMediaWithUploadedFileAndOverwrittenNameByIdShouldBeSuccessful($identifier)
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
        static::assertArrayHasKey('name', $data);
        static::assertEquals(0, strpos($data['name'], self::UPLOAD_OVERWRITTEN_FILE_NAME));
    }

    public function testDeleteMediaWithInvalidIdShouldFailWithMessage()
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

    public function testMediaUploadTraversal()
    {
        $file = '../../image.jpg';
        $media = new Media();

        return static::assertEquals('image.jpg', $media->getUniqueFileName('/tmp', $file));
    }
}
