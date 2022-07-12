<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\SplitExampleMagento\Ui\Component\Form\Product;

use Getnet\SubSellerMagento\Api\SubSellerRepositoryInterface;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Data\OptionSourceInterface;

class GetnetSubSellerIdOptions implements OptionSourceInterface
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
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var array
     */
    protected $subSellerIdTree;

    /**
     * Constructor.
     *
     * @param SubSellerRepositoryInterface $subSellerRepository
     * @param SearchCriteriaBuilder        $searchCriteria
     * @param FilterBuilder                $filterBuilder
     * @param RequestInterface             $request
     */
    public function __construct(
        SubSellerRepositoryInterface $subSellerRepository,
        SearchCriteriaBuilder $searchCriteria,
        FilterBuilder $filterBuilder,
        RequestInterface $request
    ) {
        $this->subSellerRepository = $subSellerRepository;
        $this->searchCriteria = $searchCriteria;
        $this->filterBuilder = $filterBuilder;
        $this->request = $request;
    }

    /**
     * Get Options.
     *
     * @return array
     */
    public function toOptionArray(): array
    {
        return $this->getSubSellerIdTree();
    }

    /**
     * Get Options.
     *
     * @return array
     */
    public function getSubSellerIdTree(): array
    {
        if ($this->subSellerIdTree === null) {
            $sellers = [];
            $searchCriteria = $this->searchCriteria->addFilters(
                [
                    $this->filterBuilder->setField('id_ext')->setValue(null)->setConditionType('neq')->create(),
                ]
            )->create();
            $subSellers = $this->subSellerRepository->getList($searchCriteria);

            foreach ($subSellers->getItems() as $subSeller) {
                $sellers[$subSeller->getIdExt()] = [
                    'value' => $subSeller->getIdExt(),
                    'label' => sprintf(
                        '%s - Código Interno: %s - Email: %s',
                        $subSeller->getLegalName(),
                        $subSeller->getCode(),
                        $subSeller->getEmail()
                    ),
                ];
            }
            $this->subSellerIdTree = $sellers;
        }

        return $this->subSellerIdTree;
    }
}
