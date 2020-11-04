<?php

namespace Osio\GuestToCustomerOrders\Setup;

use Osio\GuestToCustomerOrders\Helper\Data;
use Exception;
use Magento\Customer\Model\Customer;
use Magento\Eav\Model\Config;
use Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface;
use Magento\Eav\Setup\EavSetupFactory;
use Magento\Eav\Setup\EavSetup;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    private EavSetupFactory $eavSetupFactory;

    private Config $eavConfig;

    private EavSetup $eavSetup;

    private Data $helper;

    /** @var string */
    const ATTRIBUTES = 'gtco/attributes';

    public function __construct(
        EavSetupFactory $eavSetupFactory,
        Config $eavConfig,
        Data $helper
    ) {
        $this->eavSetupFactory = $eavSetupFactory;
        $this->eavConfig = $eavConfig;
        $this->helper = $helper;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();
        $this->eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);
        $this->setAttributes();
        $installer->endSetup();
    }

    private function setAttributes()
    {
        foreach ($this->helper->getConfig(self::ATTRIBUTES) as $attribute => $default) {
            $this->addBooleanAttributes($attribute, $default);
        }
    }

    private function addBooleanAttributes(string $attribute, string $default)
    {
        try {
            $this->eavSetup->addAttribute(
                Customer::ENTITY,
                $attribute,
                [
                    'system' => 0,
                    'global' => ScopedAttributeInterface::SCOPE_WEBSITE,
                    'visible' => false,
                    'type' => 'int',
                    'input' => 'boolean',
                    'label' => $attribute,
                    'source' => 'Magento\Eav\Model\Entity\Attribute\Source\Boolean',
                    'is_user_defined' => 1,
                    'required' => false,
                    'default' => $default
                ]
            );
        } catch (Exception $e) {
            $this->helper->log($e);
        }
    }
}
