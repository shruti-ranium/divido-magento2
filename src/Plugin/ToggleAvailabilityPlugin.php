<?php

namespace Divido\DividoFinancing\Plugin;

use Magento\Payment\Model\MethodInterface;
use Magento\Payment\Model\Checks\SpecificationInterface;
use Magento\Quote\Model\Quote;

class ToggleAvailabilityPlugin
{

    protected $helper;

    public function __construct (
        \Divido\DividoFinancing\Helper\Data $helper
    ) {
        $this->helper = $helper;
    }

    public function aroundIsApplicable(
        SpecificationInterface $specification,
        \Closure $proceed,
        MethodInterface $paymentMethod,
        Quote $quote
    ) {
        $originallyIsApplicable = $proceed($paymentMethod, $quote);
        if (!$originallyIsApplicable) {
            return false;
        }

        if ($paymentMethod->getCode() !== 'divido_financing') {
            return true;
        }

        $plans = $this->helper->getQuotePlans($quote);

        if ($plans) {
            return true;
        }

        return false;
    }
}
