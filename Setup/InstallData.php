<?php

namespace Divido\DividoFinancing\Setup;

use Magento\Eav\Setup\EavSetupFactory;
use Magento\Framework\Setup\InstallDataInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

class InstallData implements InstallDataInterface
{
    private $eavSetupFactory;

    public function __construct(EavSetupFactory $eavSetupFactory)
    {
        $this->eavSetupFactory = $eavSetupFactory;
    }

    public function install(ModuleDataSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();

        $eavSetup = $this->eavSetupFactory->create(['setup' => $setup]);

        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'divido_plans_display');
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Product::ENTITY, 'divido_plans_list');
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Category::ENTITY, 'divido_plans_display');
        $eavSetup->removeAttribute(\Magento\Catalog\Model\Category::ENTITY, 'divido_plans_list');

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'divido_plans_display',
            [
                'group'        => 'Divido',
                'type'         => 'varchar',
                'label'        => 'Available financing plans',
                'input'        => 'select',
                'source'       => '\Divido\DividoFinancing\Model\Adminhtml\Source\ProductPlansDisplayed',
                'global'       => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'      => true,
                'required'     => false,
                'user_defined' => true,
                'default'      => 'product_plans_default',
            ]
        );

        $eavSetup->addAttribute(
            \Magento\Catalog\Model\Product::ENTITY,
            'divido_plans_list',
            [
                'group'        => 'Divido',
                'type'         => 'varchar',
                'backend'      => '\Magento\Eav\Model\Entity\Attribute\Backend\ArrayBackend',
                'label'        => 'Financing plans',
                'input'        => 'multiselect',
                'source'       => '\Divido\DividoFinancing\Model\Adminhtml\Source\ProductPlanSelection',
                'global'       => \Magento\Eav\Model\Entity\Attribute\ScopedAttributeInterface::SCOPE_GLOBAL,
                'visible'      => true,
                'required'     => false,
                'user_defined' => true,
                'default'      => '',
            ]
        );

        $setup->endSetup();
    }
}
