<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

declare(strict_types=1);

namespace Getnet\SplitExampleMagento\Block\Adminhtml\System\Form\Field\Column;

use Getnet\SubSellerMagento\Api\SubSellerRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\View\Element\Context;
use Magento\Framework\View\Element\Html\Select;

/**
 * Class SubSellerIdColumn - Create Field to Sub Seller Id Column.
 */
class SubSellerIdColumn extends Select
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
     * Constructor.
     *
     * @param Context                      $context
     * @param SubSellerRepositoryInterface $subSellerRepository
     * @param SearchCriteriaBuilder        $searchCriteria
     * @param FilterBuilder                $filterBuilder
     * @param array                        $data
     */
    public function __construct(
        Context $context,
        SubSellerRepositoryInterface $subSellerRepository,
        SearchCriteriaBuilder $searchCriteria,
        FilterBuilder $filterBuilder,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->subSellerRepository = $subSellerRepository;
        $this->searchCriteria = $searchCriteria;
        $this->filterBuilder = $filterBuilder;
    }

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
        $sellers = [];
        $sellers[] = [
            'value' => 'any',
            'label' => __('Any Sub Seller'),
        ];
        $searchCriteria = $this->searchCriteria->addFilters(
            [
                $this->filterBuilder->setField('status')->setValue(4)->setConditionType('neq')->create(),
            ]
        )->create();
        $subSellers = $this->subSellerRepository->getList($searchCriteria);

        foreach ($subSellers->getItems() as $subSeller) {
            $sellers[] = [
                'value' => $subSeller->getIdExt(),
                'label' => sprintf(
                    '%s - Code: %s - Email: %s',
                    $subSeller->getLegalName(),
                    $subSeller->getCode(),
                    $subSeller->getEmail()
                ),
            ];
        }

        return $sellers;
    }
}
