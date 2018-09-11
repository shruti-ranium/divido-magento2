<?php

namespace Divido\DividoFinancing\Block\Product\View;

class Widget extends \Magento\Catalog\Block\Product\AbstractProduct
{
    private $helper;
    private $catHelper;

    public function __construct(
        \Divido\DividoFinancing\Helper\Data $helper,
        \Magento\Catalog\Block\Product\Context $context,
        array $data = []
    ) {
    
        $this->helper    = $helper;
        $this->catHelper = $context->getCatalogHelper();

        parent::__construct($context, $data);
    }

    public function getProductPlans()
    {
        $plans = $this->helper->getLocalPlans($this->getProduct()->getId());

        $plans = array_map(function ($plan) {
            return $plan->id;
        }, $plans);

        $plans = implode(',', $plans);

        return $plans;
    }

    public function getAmount()
    {
        $product = $this->getProduct();
        $price = $product->getFinalPrice();
        $priceIncVat = $this->catHelper->getTaxPrice($product, $price, true);

        return $priceIncVat;
    }
       
    public function getPreOrSuffix($choice)
    {
        $output = '';

        if($choice=="prefix"){
            $ix = $this->helper->getPrefix();
        }else{
            $ix = $this->helper->getSuffix();
        }
        if($ix !=''){
            $output="data-divido-".$choice."='".$ix."'";
        }
        return $output;
    }

}
