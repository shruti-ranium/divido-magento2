<?php

namespace Divido\DividoFinancing\Model;

use Divido\DividoFinancing\Api\CreditRequestInterface;

class CreditRequest implements CreditRequestInterface
{
    const
        STATUS_ACCEPTED     = 'ACCEPTED',
        STATUS_CANCELED     = 'CANCELED',
        STATUS_COMPLETED    = 'COMPLETED',
        STATUS_DEFERRED     = 'DEFERRED',
        STATUS_DECLINED     = 'DECLINED',
        STATUS_DEPOSIT_PAID = 'DEPOSIT-PAID',
        STATUS_FULFILLED    = 'FULFILLED',
        STATUS_REFERRED     = 'REFERRED',
        STATUS_SIGNED       = 'SIGNED';


    private $history_messages = array(
        self::STATUS_ACCEPTED     => 'Credit request accepted',
        self::STATUS_CANCELED     => 'Application canceled',
        self::STATUS_COMPLETED    => 'Application completed',
        self::STATUS_DEFERRED     => 'Application deferred by Underwriter, waiting for new status',
        self::STATUS_DECLINED     => 'Applicaiton declined by Underwriter',
        self::STATUS_DEPOSIT_PAID => 'Deposit paid by customer',
        self::STATUS_FULFILLED    => 'Credit request fulfilled',
        self::STATUS_REFERRED     => 'Credit request referred by Underwriter, waiting for new status',
        self::STATUS_SIGNED       => 'Customer have signed all contracts',
    );

    private 
        $helper,
        $logger,
        $req,
        $lookupFactory,
        $quote,
        $quoteManagement,
        $order;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Magento\Sales\Model\Order $order,
        \Magento\Quote\Model\Quote $quote,
        \Magento\Quote\Model\QuoteManagement $quoteManagement,
        \Divido\DividoFinancing\Helper\Data $helper,
        \Divido\DividoFinancing\Model\LookupFactory $lookupFactory,
        \Psr\Log\LoggerInterface            $logger
    ) {
        $this->req    = $request;
        $this->helper = $helper;
        $this->logger = $logger;
        $this->quoteManagement = $quoteManagement;
        $this->quote = $quote;
        $this->order = $order;
        $this->lookupFactory = $lookupFactory;
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
            exit('Cannot verify request');
        }

        $salt = $lookup->getSalt();
        $hash = $this->helper->hashQuote($salt, $data->metadata->quote_id);
        if ($hash !== $data->metadata->quote_hash) {
            $this->logger->addError('Divido: Bad request, mismatch in hash. Req: ' . $content);
            exit('Cannot verify request');
        }

        $order = $this->order->loadByAttribute('quote_id', $quoteId);

        if (! $order->getId()) {
            $quote = $this->quote->loadActive($quoteId);
            if (! $quote->getCustomerId()) {
                $quote->setCheckoutMethod(\Magento\Quote\Model\QuoteManagement::METHOD_GUEST);
                $quote->save();
            }

            $orderId = $this->quoteManagement->placeOrder($quoteId);

            $order = $this->order->load($orderId);
        }

        $comment = 'Divido: Unknown status';
        if (array_key_exists($data->status, $this->history_messages)) {
            $comment = 'Divido: ' . $this->history_messages[$data->status];
        }

        $order->addStatusHistoryComment($comment);
        $order->save();

        return 'm2-1.0';
    }
}
