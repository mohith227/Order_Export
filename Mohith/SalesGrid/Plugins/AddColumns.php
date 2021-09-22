<?php

namespace Mohith\SalesGrid\Plugins;

use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\View\Element\UiComponent\DataProvider\CollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Grid\CollectionFactory as SalesOrderGridCollection;
use Mohith\SalesGrid\Model\Config\SystemConfig;
use Psr\Log\LoggerInterface;

class AddColumns
{
    /**
     * @var MessageManager
     */
    private $messageManager;
    /**
     * @var SalesOrderGridCollection
     */
    private $collection;

    protected $config;
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param MessageManager $messageManager
     * @param SalesOrderGridCollection $collection
     * @param SystemConfig $config
     * @param LoggerInterface $logger
     */
    public function __construct(
        MessageManager           $messageManager,
        SalesOrderGridCollection $collection,
        SystemConfig             $config,
        LoggerInterface          $logger
    )
    {
        $this->messageManager = $messageManager;
        $this->collection = $collection;
        $this->config = $config;
        $this->logger = $logger;
    }

    /**
     * @param CollectionFactory $subject
     * @param \Closure $proceed
     * @param $requestName
     * @return mixed
     */
    public function aroundGetReport(
        CollectionFactory $subject,
        \Closure          $proceed,
                          $requestName
    )
    {
        try {
            if ($this->config->getIsActive()) {
                $result = $proceed($requestName);
                if ($requestName == 'sales_order_grid_data_source') {
                    $salesCollection = $this->collection->create();
                    if ($result instanceof $salesCollection
                    ) {
                        $customValues = explode(",", $this->config->getCustomOptions());
                        $select = $salesCollection->getSelect();
                        if (isset($customValues)) {
                            if (in_array("PhoneNumber", $customValues)) {
                                $select->join(
                                    ["soa" => "sales_order_address"],
                                    'main_table.entity_id = soa.parent_id AND soa.address_type="billing"',
                                    array('telephone', 'region')
                                );
                            }
                            if (in_array("Comments", $customValues)) {
                                $select->joinLeft(
                                    ["sosh" => "sales_order_status_history"],
                                    'main_table.entity_id = sosh.parent_id',
                                    ['comment' => 'GROUP_CONCAT(DISTINCT sosh.comment)'
                                    ]
                                );
                            }
                            if (in_array("Sku", $customValues)) {
                                $select->join(
                                    ["soi" => "sales_order_item"],
                                    'main_table.entity_id = soi.order_id',
                                    ['sku' => 'GROUP_CONCAT(DISTINCT soi.sku)'
                                    ]
                                );
                            }
                            $select->group("main_table.entity_id");
                        }
                    }
                    return $salesCollection->addFilterToMap('created_at', 'main_table.created_at')->addFilterToMap('status', 'main_table.status');
                } else {

                    return $result;
                }
            }
        } catch (\Exception $e) {
            $this->logger->critical($e->getMessage());
        }

    }
}
