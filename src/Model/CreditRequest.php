<?php

namespace Divido\DividoFinancing\Model;

use Divido\DividoFinancing\Api\CreditRequestInterface;

class CreditRequest implements CreditRequestInterface
{
    protected $helper, $logger, $req;

    public function __construct(
        \Magento\Framework\App\Request\Http $request,
        \Divido\DividoFinancing\Helper\Data $helper,
        \Psr\Log\LoggerInterface            $logger
    ) {
        $this->req    = $request;
        $this->helper = $helper;
        $this->logger = $logger;
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
        return  'ok';
    }
}
