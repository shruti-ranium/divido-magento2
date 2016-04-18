<?php

namespace Divido\DividoFinancing\Observer;

use Magento\Framework\Event\ObserverInterface;

class PaymentMethodIsActive implements ObserverInterface
{
    public function execute (\Magento\Framework\Event\Observer $observer)
    {
        xdebug_break();
    }
}
