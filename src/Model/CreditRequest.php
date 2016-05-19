<?php

namespace Divido\DividoFinancing\Model;

use Divido\DividoFinancing\Api\CreditRequestInterface;

class CreditRequest implements CreditRequestInterface
{
    const
        VERSION              = 'M2-1.0.0',
        NEW_ORDER_STATUS     = 'processing',
        STATUS_ACCEPTED      = 'ACCEPTED',
        STATUS_ACTION_LENDER = 'ACTION-LENDER',
        STATUS_CANCELED      = 'CANCELED',
        STATUS_COMPLETED     = 'COMPLETED',
        STATUS_DECLINED      = 'DECLINED',
        STATUS_DEPOSIT_PAID  = 'DEPOSIT-PAID',
        STATUS_FULFILLED     = 'FULFILLED',
        STATUS_REFERRED      = 'REFERRED',
        STATUS_SIGNED        = 'SIGNED';

    private $historyMessages = [ 
        self::STATUS_ACCEPTED      => 'Credit request accepted',
        self::STATUS_ACTION_LENDER => 'Lender notified',
        self::STATUS_CANCELED      => 'Application canceled',
        self::STATUS_COMPLETED     => 'Application completed',
        self::STATUS_DECLINED      => 'Applicaiton declined by Underwriter',
        self::STATUS_DEPOSIT_PAID  => 'Deposit paid by customer',
        self::STATUS_FULFILLED     => 'Credit request fulfilled',
        self::STATUS_REFERRED      => 'Credit request referred by Underwriter, waiting for new status',
        self::STATUS_SIGNED        => 'Customer have signed all contracts',
    ];

    private $noGo = [
        self::STATUS_CANCELED,
        self::STATUS_DECLINED,
    ];

    private 
        $req,
        $quote,
        $order,
        $helper,
        $logger,
        $config,
        $lookupFactory,
        $quoteManagement;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Framework\App\Request\Http                $request,
        \Magento\Sales\Model\Order                         $order,
        \Magento\Quote\Model\Quote                         $quote,
        \Magento\Quote\Model\QuoteManagement               $quoteManagement,
        \Divido\DividoFinancing\Helper\Data                $helper,
        \Divido\DividoFinancing\Model\LookupFactory        $lookupFactory,
        \Psr\Log\LoggerInterface                           $logger
    ) {
        $this->req    = $request;
        $this->quote = $quote;
        $this->order = $order;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->config = $scopeConfig;
        $this->lookupFactory = $lookupFactory;
        $this->quoteManagement = $quoteManagement;
    }

    /**
     * Create a credit request as Divido, return a URL to complete the credit 
     * request.
     *
     * @api
     * @param string Quote ID
     * @return string Credit request URL
     */
    public function create () 
    {
        $response = [];

        $planId  = $this->req->getQuery('plan',    null);
        $deposit = $this->req->getQuery('deposit', null);
        $email   = $this->req->getQuery('email',   null);

        try {
            $creditRequestUrl = $this->helper->creditRequest($planId, $deposit, $email);
            $response['url']  = $creditRequestUrl;
        } catch (Exception $e) {
            $this->logger->addError($e);
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    /**
     * Update an order with results from credit request
     *
     * @api
     * @return string Update status
     */
    public function update ()
    {
        $content = $this->req->getContent();
        $data = json_decode($content);

        $quoteId = $data->metadata->quote_id;

        $lookup = $this->lookupFactory->create()->load($quoteId, 'quote_id');
        if (! $lookup->getId()) {
            $this->logger->addError('Divido: Bad request, could not find lookup. Req: ' . $content);
            exit('Cannot verify request ' . self::VERSION);
        }

        $salt = $lookup->getSalt();
        $hash = $this->helper->hashQuote($salt, $data->metadata->quote_id);
        if ($hash !== $data->metadata->quote_hash) {
            $this->logger->addError('Divido: Bad request, mismatch in hash. Req: ' . $content);
            exit('Cannot verify request' . self::VERSION);
        }

        $lookup->setData('application_id', $data->application);
        $lookup->save();

        $order = $this->order->loadByAttribute('quote_id', $quoteId);

        if (! $order->getId() && in_array($data->status, $this->noGo)) {
            if ($data->status == self::STATUS_DECLINED) {
                $lookup->setData('declined', 1);
            } elseif ($data->status == self::STATUS_CANCELED) {
                $lookup->setData('canceled', 1);
            }
            $lookup->save();

            return self::VERSION;
        }

        if ($order->getId() && $data->status == self::STATUS_DECLINED) {
            $order->addStatusHistoryComment($this->historyMessages[$data->status]);
            $order->cancel();
            $order->save();

            $lookup->setData('declined', 1);
            $lookup->save();
            return self::VERSION;
        }

        if (! $order->getId()) {
            $quote = $this->quote->loadActive($quoteId);
            if (! $quote->getCustomerId()) {
                $quote->setCheckoutMethod(\Magento\Quote\Model\QuoteManagement::METHOD_GUEST);
                $quote->save();
            }

            $orderId = $this->quoteManagement->placeOrder($quoteId);

            $order = $this->order->load($orderId);
        } else {
            $orderId = $order->getId();
        }

        $lookup->setData('order_id', $orderId);
        $lookup->save();

        xdebug_break();
        if ($data->status == self::STATUS_SIGNED) {
            $status = self::NEW_ORDER_STATUS;
            $status_override = $this->config->getValue('payment/divido_financing/order_status',
                \Magento\Store\Model\ScopeInterface::SCOPE_STORE);
            if ($status_override) {
                $status = $status_override;
            }
            $order->setStatus($status);
        }

        $comment = 'Divido: ' . $data->status;
        if (array_key_exists($data->status, $this->historyMessages)) {
            $comment = 'Divido: ' . $this->historyMessages[$data->status];
        }

        $order->addStatusHistoryComment($comment);
        $order->save();

        return self::VERSION;
    }
}
