<?php

namespace Divido\DividoFinancing\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    private $eavSetupFactory;

    public function __construct (EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;

    }

    public function install (ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'divido_plans_display',
            [
                'group'        => 'Divido',
                'type'         => 'varchar',
                'frontend'     => '',
                'label'        => 'Available financing plans',
                'input'        => 'select',
                'class'        => '',
                'source'       => '\Divido\DividoFinancing\Model\Adminhtml\Source\ProductPlansDisplayed',
                'global'       => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible'      => true,
                'required'     => false,
                'user_defined' => false,
                'default'      => 'plans_default',
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'divido_plans_list',
            [
                'group'        => 'Divido',
                'type'         => 'varchar',
                'backend'      => '\Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                'frontend'     => '',
                'label'        => 'Financing plans',
                'input'        => 'multiselect',
                'class'        => '',
                'source'       => '\Divido\DividoFinancing\Model\Adminhtml\Source\ProductPlanSelection',
                'global'       => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_STORE,
                'visible'      => true,
                'required'     => false,
                'user_defined' => false,
                'default'      => '',
            ]
        );

        $setup->endSetup();
    }
}
