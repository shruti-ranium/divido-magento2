<?php

namespace Divido\DividoFinancing\Model;

class Lookup extends \Magento\Framework\Model\AbstractModel
{
    public function _construct()
    {
        $this->_init('Divido\DividoFinancing\Model\ResourceModel\Lookup');
    }
}
