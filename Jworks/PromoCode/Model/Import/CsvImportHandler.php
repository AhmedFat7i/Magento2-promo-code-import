<?php
/**
 * @category    Jworks
 * @package     Jworks_PromoCode
 * @author Jitheesh V O <jitheeshvo@gmail.com>
 * @copyright Copyright (c) 2017 Jworks Digital ()
 */

namespace Jworks\PromoCode\Model\Import;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\File\Csv;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * URL rewrite CSV Import Handler
 */
class CsvImportHandler
{
    /**
     * DB connection
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $_connection;

    /**
     * Customer entity DB table name.
     * @var string
     */
    protected $_entityTable;
    /**
     * @var \Magento\SalesRule\Model\Rule
     */
    protected $_rule;

    /**
     * @param Csv                                $csvProcessor
     * @param ObjectManagerInterface             $_objectManager
     * @param RequestInterface                   $_request
     * @param ResourceConnection                 $resource
     * @param Registry                           $_coreRegistry
     * @param DateTime                           $date
     * @param \Magento\Framework\Stdlib\DateTime $dateTime
     */
    public function __construct(
        protected \Magento\Framework\File\Csv $csvProcessor,
        protected \Magento\Framework\ObjectManagerInterface $_objectManager,
        protected \Magento\Framework\App\RequestInterface $_request,
        ResourceConnection $resource,
        protected \Magento\Framework\Registry $_coreRegistry,
        protected \Magento\Framework\Stdlib\DateTime\DateTime $date,
        protected \Magento\Framework\Stdlib\DateTime $dateTime
    ) {
        $this->_connection = $data['connection'] ?? $resource->getConnection();
        $this->_entityTable = 'salesrule_coupon';

    }

    /**
     * Initiate rule
     * @return void
     */
    protected function _initRule()
    {
        $this->_coreRegistry->register(
            \Magento\SalesRule\Model\RegistryConstants::CURRENT_SALES_RULE,
            $this->_objectManager->create('Magento\SalesRule\Model\Rule')
        );
        $id = (int)$this->_request->getParam('id');

        if (!$id && $this->_request->getParam('rule_id')) {
            $id = (int)$this->_request->getParam('rule_id');
        }

        if ($id) {
            $this->_rule = $this->_coreRegistry->registry(\Magento\SalesRule\Model\RegistryConstants::CURRENT_SALES_RULE)->load($id);

        }
    }

    /**
     * Import Tax Rates from CSV file
     * @param array $file file info retrieved from $_FILES array
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function importFromCsvFile($file)
    {
        if (!isset($file['tmp_name'])) {
            throw new \Magento\Framework\Exception\LocalizedException(__('Invalid file upload attempt.'));
        }
        $this->_initRule();
        $rawData = $this->csvProcessor->getData($file['tmp_name']);

        foreach ($rawData as $rowIndex => $dataRow) {
            $this->_generateCoupon(current($dataRow));
        }
    }

    protected function _generateCoupon($coupon)
    {
        $this->_connection->insertOnDuplicate(
            $this->_entityTable,
            array(
                'rule_id' => $this->_rule->getId(),
                'code' => $coupon,
                'usage_limit' => 0,
                'created_at' => $this->dateTime->formatDate($this->date->gmtTimestamp()),
                'usage_per_customer' => $this->_rule->getUsesPerCustomer(),
                'times_used' => 0,
                'type' => 1,
            ),
            array()
        );
    }

}
