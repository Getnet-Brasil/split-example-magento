<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\SplitExampleMagento\Block\Adminhtml\System\Form\Field\Column;

use Magento\Framework\View\Element\Html\Select;

/**
 * Class CalculationTypeColumn - Create Field to Calculation Type Column.
 */
class CalculationTypeColumn extends Select
{
    /**
     * @var SubSellerRepositoryInterface
     */
    protected $subSellerRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteria;

    /**
     * @var FilterBuilder
     */
    protected $filterBuilder;

    /**
     * Set "name" for <select> element.
     *
     * @param string $value
     *
     * @return void
     */
    public function setInputName($value)
    {
        return $this->setName($value);
    }

    /**
     * Set "id" for <select> element.
     *
     * @param string $value
     *
     * @return void
     */
    public function setInputId($value)
    {
        return $this->setId($value);
    }

    /**
     * Render block HTML.
     *
     * @return string
     */
    public function _toHtml(): string
    {
        if (!$this->getOptions()) {
            $this->setOptions($this->getSourceOptions());
        }

        return parent::_toHtml();
    }

    /**
     * Get Options.
     *
     * @return array
     */
    public function getSourceOptions(): array
    {
        $typeCalc = [
            [
                'value' => 'full',
                'label' => __('Receive the Full Amount'),
            ],
            [
                'value' => 'retain',
                'label' => __('Retain Full Value'),
            ],
        ];

        return $typeCalc;
    }
}
