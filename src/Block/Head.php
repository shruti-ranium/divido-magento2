<?php

namespace Divido\DividoFinancing\Block;

class Head extends \Magento\Framework\View\Element\Template
{
    public function __construct (
        \Magento\Framework\View\Element\Template\Context $context,
        \Divido\DividoFinancing\Helper\Data $helper,
        \Psr\Log\LoggerInterface            $logger
    )
    {
        $this->helper = $helper;
        $this->logger = $logger;
        parent::__construct($context);
    }


	public function getScriptUrl ()
    {
        return $this->helper->getScriptUrl();
    }
}
