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

namespace SwagPaymentPayPalUnified\Tests\Functional\Subscriber;

use Enlight_Template_Manager;
use SwagPaymentPayPalUnified\Subscriber\Frontend;
use SwagPaymentPayPalUnified\Tests\Functional\DatabaseTestCaseTrait;
use SwagPaymentPayPalUnified\Tests\Functional\SettingsHelperTrait;
use SwagPaymentPayPalUnified\Tests\Mocks\DummyController;
use SwagPaymentPayPalUnified\Tests\Mocks\ViewMock;

class FrontendSubscriberTest extends \PHPUnit_Framework_TestCase
{
    use DatabaseTestCaseTrait;
    use SettingsHelperTrait;

    public function test_can_be_created()
    {
        $subscriber = new Frontend(__DIR__, Shopware()->Container()->get('paypal_unified.settings_service'));
        $this->assertNotNull($subscriber);
    }

    public function test_getSubscribedEvents_has_correct_events()
    {
        $events = Frontend::getSubscribedEvents();
        $this->assertCount(4, $events);
        $this->assertEquals('onCollectJavascript', $events['Theme_Compiler_Collect_Plugin_Javascript']);
        $this->assertEquals('onPostDispatchSecure', $events['Enlight_Controller_Action_PostDispatchSecure_Frontend']);
        $this->assertEquals('onPostDispatchSecure', $events['Enlight_Controller_Action_PostDispatchSecure_Widgets']);
        $this->assertEquals('onCollectTemplateDir', $events['Theme_Inheritance_Template_Directories_Collected']);
    }

    public function test_onCollectJavascript()
    {
        $subscriber = new Frontend(
            Shopware()->Container()->getParameter('paypal_unified.plugin_dir'),
            Shopware()->Container()->get('paypal_unified.settings_service')
        );
        $javascripts = $subscriber->onCollectJavascript();

        foreach ($javascripts as $script) {
            $this->assertFileExists($script);
        }

        $this->assertCount(9, $javascripts);
    }

    public function test_onPostDistpatchSecure_without_any_setttings()
    {
        $subscriber = new Frontend(
            Shopware()->Container()->getParameter('paypal_unified.plugin_dir'),
            Shopware()->Container()->get('paypal_unified.settings_service')
        );

        $view = new ViewMock(new Enlight_Template_Manager());
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $enlightEventArgs = new \Enlight_Controller_ActionEventArgs([
            'subject' => new DummyController($request, $view),
        ]);

        $result = $subscriber->onPostDispatchSecure($enlightEventArgs);

        $this->assertNull($result);
    }

    public function test_onPostDispatchSecure_assigns_variables_to_view()
    {
        $subscriber = new Frontend(
            Shopware()->Container()->getParameter('paypal_unified.plugin_dir'),
            Shopware()->Container()->get('paypal_unified.settings_service')
        );
        $this->createTestSettings();

        $view = new ViewMock(new Enlight_Template_Manager());
        $request = new \Enlight_Controller_Request_RequestTestCase();
        $enlightEventArgs = new \Enlight_Controller_ActionEventArgs([
            'subject' => new DummyController($request, $view),
        ]);

        $subscriber->onPostDispatchSecure($enlightEventArgs);

        $this->assertTrue((bool) $view->getAssign('paypalUnifiedShowLogo'));
        $this->assertTrue((bool) $view->getAssign('paypalUnifiedShowInstallmentsLogo'));
        $this->assertTrue((bool) $view->getAssign('paypalUnifiedAdvertiseReturns'));
    }

    public function test_onCollectTemplateDir()
    {
        $subscriber = new Frontend(
            Shopware()->Container()->getParameter('paypal_unified.plugin_dir'),
            Shopware()->Container()->get('paypal_unified.settings_service')
        );
        $returnValue = [];

        $enlightEventArgs = new \Enlight_Controller_ActionEventArgs([
        ]);

        $enlightEventArgs->setReturn($returnValue);

        $subscriber->onCollectTemplateDir($enlightEventArgs);
        $returnValue = $enlightEventArgs->getReturn();

        $this->assertDirectoryExists($returnValue[0]);
    }

    private function createTestSettings()
    {
        $this->insertGeneralSettingsFromArray([
            'shopId' => 1,
            'clientId' => 'test',
            'clientSecret' => 'test',
            'sandbox' => true,
            'showSidebarLogo' => true,
            'logoImage' => 'TEST',
            'active' => true,
            'advertiseReturns' => true,
        ]);

        $this->insertInstallmentsSettingsFromArray([
            'active' => true,
            'showLogo' => true,
        ]);
    }
}
