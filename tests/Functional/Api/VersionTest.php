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

use Shopware\Kernel;
use Zend_Json;

/**
 * @covers \Shopware_Controllers_Api_Version
 */
class VersionTest extends AbstractApiTest
{
    public function getApiResource(): string
    {
        return 'version';
    }

    public function testGetVersionShouldBeSuccessful()
    {
        $kernel = new Kernel('testing', true);
        $release = $kernel->getRelease();

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
        $data = $result['data'];
        static::assertIsArray($data);

        static::assertEquals($release['version'], $data['version']);
        static::assertEquals($release['revision'], $data['revision']);
    }
}
