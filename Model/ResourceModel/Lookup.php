<?php

namespace Divido\DividoFinancing\Model\ResourceModel;

class Lookup extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb
{
    public function __construct(
        \Magento\Framework\Model\ResourceModel\Db\Context $context,
        $resourcePrefix = null
    ) {
        parent::__construct($context, $resourcePrefix);
    }

    protected function _construct()
    {
        $this->_init('divido_lookup', 'id');
    }

    public function load(\Magento\Framework\Model\AbstractModel $object, $value, $field = null)
    {
        if (!is_numeric($value) && $field ===null) {
            $field = 'quote_id';
        }

        return parent::load($object, $value, $field);
    }
}
