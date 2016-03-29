<?php

namespace Divido\DividoFinancing\Api;

interface CreditRequestInterface
{
    /**
     * Create a credit request as Divido, return a URL to complete the credit 
     * request.
     *
     * @api
     * @param string Quote ID
     * @return string Credit request URL
     */
    public function create ($quoteId);
}
