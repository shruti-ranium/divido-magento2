<?php
/**
 * Copyright © 2016 Divido. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Divido\DividoFinancing\Model\Adminhtml\Source;

/**
 * Class PlansDisplayed
 */
class ProductPlansDisplayed extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    public function getAllOptions ()
    {
        return [
            [
                'value' => 'product_plans_default',
                'label' => 'Display default plans',
            ],
            [
                'value' => 'product_plans_selected',
                'label' => 'Selected plans',
            ],
        ];
    }
}
