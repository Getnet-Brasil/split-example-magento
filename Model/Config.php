<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\SplitExampleMagento\Model;

use Magento\Framework\Serialize\Serializer\Json;
use Magento\Store\Model\Store;

/**
 * Class to set flags for sub seller display setting.
 *
 * @SuppressWarnings(PHPMD.ExcessivePublicCount)
 */
class Config
{
    /**
     * List Commissions.
     */
    public const XML_PATH_GETNET_SPLIT_COMMISSIONS = 'getnet_split/general/split_commisions/commisions';

    /**
     * Guarantor Name.
     */
    public const XML_PATH_GETNET_SPLIT_GUARANTOR_NAME = 'getnet_split/general/addtional_boleto/guarantor_name';

    /**
     * Guarantor Document.
     */
    public const XML_PATH_GETNET_SPLIT_GUARANTOR_DOCUMENT = 'getnet_split/general/addtional_boleto/guarantor_document';

    /**
     * Type Release.
     */
    public const XML_PATH_GETNET_SPLIT_TYPE_RELEASE = 'getnet_split/general/payment_release/type_release';

    /**
     * Cron BY.
     */
    public const XML_PATH_GETNET_SPLIT_CRON_BY = 'getnet_split/general/payment_release/cron_by';

    /**
     * Release Days.
     */
    public const XML_PATH_GETNET_SPLIT_CRON_DAYS = 'getnet_split/general/payment_release/release_day';

    /**
     * Core store config.
     *
     * @var \Magento\Framework\App\Config\ScopeConfigInterface
     */
    protected $_scopeConfig;

    /**
     * Core Json.
     *
     * @var Json
     */
    protected $json;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param Json                                               $json
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        Json $json
    ) {
        $this->json = $json;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Get Split Commissions.
     *
     * @param null|string|bool|int|Store $store
     *
     * @return array
     */
    public function getSplitCommissions($store = null)
    {
        $listCommissions = $this->_scopeConfig->getValue(
            self::XML_PATH_GETNET_SPLIT_COMMISSIONS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );

        if (is_array($listCommissions)) {
            return $listCommissions;
        }

        return $this->json->unserialize($listCommissions);
    }

    /**
     * Get Guarantor Name.
     *
     * @param null|string|bool|int|Store $store
     *
     * @return string|null
     */
    public function getGuarantorName($store = null): ?string
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_GETNET_SPLIT_GUARANTOR_NAME,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get Guarantor Document.
     *
     * @param null|string|bool|int|Store $store
     *
     * @return string|null
     */
    public function getGuarantorDocument($store = null): ?string
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_GETNET_SPLIT_GUARANTOR_DOCUMENT,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get Type Release.
     *
     * @param null|string|bool|int|Store $store
     *
     * @return string|null
     */
    public function getTypeRelease($store = null): ?string
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_GETNET_SPLIT_TYPE_RELEASE,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get Cron By.
     *
     * @param null|string|bool|int|Store $store
     *
     * @return string|null
     */
    public function getCronBy($store = null): ?string
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_GETNET_SPLIT_CRON_BY,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }

    /**
     * Get Release Day.
     *
     * @param null|string|bool|int|Store $store
     *
     * @return string|null
     */
    public function getCronDays($store = null): ?string
    {
        return $this->_scopeConfig->getValue(
            self::XML_PATH_GETNET_SPLIT_CRON_DAYS,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE,
            $store
        );
    }
}
