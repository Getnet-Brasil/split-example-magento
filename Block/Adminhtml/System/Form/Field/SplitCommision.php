<?php
/**
 * Copyright © Getnet. All rights reserved.
 *
 * @author    Bruno Elisei <brunoelisei@o2ti.com>
 * See LICENSE for license details.
 */

namespace Getnet\SplitExampleMagento\Block\Adminhtml\System\Form\Field;

use Getnet\SplitExampleMagento\Block\Adminhtml\System\Form\Field\Column\CalculationTypeColumn;
use Getnet\SplitExampleMagento\Block\Adminhtml\System\Form\Field\Column\SubSellerIdColumn;
use Magento\Config\Block\System\Config\Form\Field\FieldArray\AbstractFieldArray;
use Magento\Framework\DataObject;
use Magento\Framework\Exception\LocalizedException;

/**
 * Class SplitCommision - Add List Commision to field.
 */
class SplitCommision extends AbstractFieldArray
{
    /**
     * @var SubSellerIdColumn
     */
    protected $subSellerIdRenderer;

    /**
     * @var CalculationTypeColumn
     */
    protected $calculationTypeRenderer;

    /**
     * Prepare rendering the new field by adding all the needed columns.
     */
    protected function _prepareToRender()
    {
        $this->addColumn('sub_seller_id', [
            'label'    => __('Sub Seller'),
            'renderer' => $this->getFieldSubSellerIdRenderer(),
        ]);

        $this->addColumn('commission_percentage', [
            'label' => __('Percentage he receives'),
            'class' => 'required-entry admin__control-text',
        ]);

        $this->addColumn('include_freight', [
            'label'    => __('Receiving Freight'),
            'renderer' => $this->getFieldCalculationTypeRenderer(),
            'class'    => 'required-entry',
        ]);

        $this->addColumn('include_interest', [
            'label'    => __('Receiving Interest'),
            'renderer' => $this->getFieldCalculationTypeRenderer(),
            'class'    => 'required-entry',
        ]);

        $this->_addAfter = false;
        $this->_addButtonLabel = __('Add');
    }

    /**
     * Prepare existing row data object.
     *
     * @param DataObject $row
     *
     * @throws LocalizedException
     */
    protected function _prepareArrayRow(DataObject $row): void
    {
        $options = [];

        $field = $row->getField();
        if ($field !== null) {
            $options[
                'option_'.$this->getFieldSubSellerIdRenderer()->calcOptionHash($field)
            ] = 'selected="selected"';
            $options[
                'option_'.$this->getFieldCalculationTypeRenderer()->calcOptionHash($field)
            ] = 'selected="selected"';
        }

        $row->setData('option_extra_attrs', $options);
    }

    /**
     * Create Block Sub Seller Id Renderer.
     *
     * @throws LocalizedException
     *
     * @return SubSellerIdColumn
     */
    private function getFieldSubSellerIdRenderer()
    {
        if (!$this->subSellerIdRenderer) {
            $this->subSellerIdRenderer = $this->getLayout()->createBlock(
                SubSellerIdColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->subSellerIdRenderer;
    }

    /**
     * Create Block Calculation Type Renderer.
     *
     * @throws LocalizedException
     *
     * @return CalculationTypeColumn
     */
    private function getFieldCalculationTypeRenderer()
    {
        if (!$this->calculationTypeRenderer) {
            $this->calculationTypeRenderer = $this->getLayout()->createBlock(
                CalculationTypeColumn::class,
                '',
                ['data' => ['is_render_to_js_template' => true]]
            );
        }

        return $this->calculationTypeRenderer;
    }
}
