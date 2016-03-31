<?php

namespace Divido\DividoFinancing\Model;

use Divido\DividoFinancing\Api\CreditRequestInterface;

class CreditRequest implements CreditRequestInterface
{
    public function __construct(
        \Divido\DividoFinancing\Helper\Data $helper,
        \Psr\Log\LoggerInterface            $logger
    ) {
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
    public function create () {

        $response = [];

        xdebug_Break();
        try {
            $creditRequestUrl = $this->helper->creditRequest();
            $response['url']  = $creditRequestUrl;
        } catch (Exception $e) {
            $this->logger->addError($e);
            $response['error'] = $e->getMessage();
        }

        return json_encode($response);
    }
}
