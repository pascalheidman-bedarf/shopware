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

class Shopware_Tests_Controllers_Backend_NewsletterTest extends Enlight_Components_Test_Plugin_TestCase
{
    public function setUp()
    {
        parent::setUp();
        Shopware()->Plugins()->Backend()->Auth()->setNoAuth();
        Shopware()->Plugins()->Backend()->Auth()->setNoAcl();
    }

    /**
     * @ticket SW-4747
     */
    public function testNewsletterLock()
    {
        $this->Front()->setParam('noViewRenderer', false);
        Shopware()->Config()->MailCampaignsPerCall = 1;

        $this->dispatch('/backend/newsletter/cron');
        static::assertRegExp('#[0-9]+ Recipients fetched#', $this->Response()->getBody());
        $this->reset();

        $this->dispatch('/backend/newsletter/cron');
        static::assertRegExp('#Wait [0-9]+ seconds ...#', $this->Response()->getBody());
        $this->reset();
    }

    /**
     * @ticket SW-23211
     */
    public function testNewsletterGroup()
    {
        $this->dispatch('/backend/NewsletterManager/getGroups');

        static::assertTrue($this->View()->getAssign('success'));
    }

    /**
     * Returns the test dataset
     *
     * @return PHPUnit_Extensions_Database_DataSet_IDataSet
     */
    protected function getDataSet()
    {
        return $this->createXMLDataSet(__DIR__ . '/testdata/Lock.xml');
    }
}
