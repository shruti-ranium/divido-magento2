<?php
/**
 * Copyright © 2016 Divido. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Divido\DividoFinancing\Model\Adminhtml\Source;

/**
 * Class PaymentAction
 */
class ProductSelection implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * {@inheritdoc}
     */
    public function toOptionArray()
    {
        return [
            [
                'value' => 'products_all',
                'label' => __('All products'),
            ],
            [
                'value' => 'products_selected',
                'label' => __('Only selected products'),
            ],
            [
                'value' => 'products_price_threshold',
                'label' => __('Only products over price threshold'),
            ],
        ];
    }
}
