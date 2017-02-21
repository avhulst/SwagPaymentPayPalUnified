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

namespace SwagPaymentPayPalUnified;

use Shopware\Components\Plugin;
use Shopware\Components\Plugin\Context\ActivateContext;
use Shopware\Components\Plugin\Context\DeactivateContext;
use Shopware\Components\Plugin\Context\InstallContext;
use Shopware\Components\Plugin\Context\UninstallContext;
use SwagPaymentPayPalUnified\Components\PaymentMethodProvider;
use SwagPaymentPayPalUnified\Setup\Installer;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class SwagPaymentPayPalUnified extends Plugin
{
    /**
     * @param ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        $container->setParameter('paypal_unified.plugin_dir', $this->getPath());
        parent::build($container);
    }

    /**
     * {@inheritdoc}
     */
    public function install(InstallContext $context)
    {
        $installer = new Installer(
            $this->container->get('models'),
            $this->container->get('dbal_connection'),
            $this->container->get('shopware_attribute.crud_service'),
            $this->getPath()
        );

        $installer->install($context);
        parent::install($context);
    }

    /**
     * {@inheritdoc}
     */
    public function uninstall(UninstallContext $context)
    {
        /** @var PaymentMethodProvider $paymentMethodProvider */
        $paymentMethodProvider = new PaymentMethodProvider($this->container->get('models'));
        $paymentMethodProvider->setPaymentMethodActiveFlag(false);

        parent::uninstall($context);
    }

    /**
     * {@inheritdoc}
     */
    public function activate(ActivateContext $context)
    {
        /** @var PaymentMethodProvider $paymentMethodProvider */
        $paymentMethodProvider = new PaymentMethodProvider($this->container->get('models'));
        $paymentMethodProvider->setPaymentMethodActiveFlag(true);

        $context->scheduleClearCache(['theme']);

        parent::activate($context);
    }

    /**
     * {@inheritdoc}
     */
    public function deactivate(DeactivateContext $context)
    {
        /** @var PaymentMethodProvider $paymentMethodProvider */
        $paymentMethodProvider = new PaymentMethodProvider($this->container->get('models'));
        $paymentMethodProvider->setPaymentMethodActiveFlag(false);

        $context->scheduleClearCache(['theme']);

        parent::deactivate($context);
    }
}
