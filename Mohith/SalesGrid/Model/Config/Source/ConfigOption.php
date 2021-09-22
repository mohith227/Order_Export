<?php

namespace Mohith\SalesGrid\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;


class ConfigOption implements OptionSourceInterface
{
    /**
     * @return array[]
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'PhoneNumber', 'label' => __('PhoneNumber')],
            ['value' => 'Comments', 'label' => __('Comments')],
            ['value' => 'Sku', 'label' => __('Sku')]
        ];
    }
}
