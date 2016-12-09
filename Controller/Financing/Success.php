<?php

namespace Divido\DividoFinancing\Controller\Financing;

class Success extends \Magento\Framework\App\Action\Action
{
    private $checkoutSession, $order;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Sales\Model\Order $order
    ) {
    
        $this->checkoutSession = $checkoutSession;
        $this->order = $order;

        parent::__construct($context);
    }

    public function execute()
    {
        $quoteId = $this->getRequest()->getParam('quote_id');
        $order   = $this->order->loadByAttribute('quote_id', $quoteId);

        $this->checkoutSession->setLastQuoteId($quoteId);
        $this->checkoutSession->setLastSuccessQuoteId($quoteId);
        $this->checkoutSession->setLastOrderId($order->getId());
        $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        $this->checkoutSession->setLastOrderStatus($order->getStatus());

        $this->_redirect('checkout/onepage/success');
    }
}
