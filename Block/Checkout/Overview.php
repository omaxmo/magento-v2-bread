<?php
/**
 * Handles Checkout Overview Block
 *
 * @copyright   Bread   2016
 * @author      Joel    @Mediotype
 * @author      Miranda @Mediotype
 */
namespace Bread\BreadCheckout\Block\Checkout;

class Overview extends \Bread\BreadCheckout\Block\Product\View
{
    /** @var \Bread\BreadCheckout\Helper\Quote */
    public $quoteHelper;

    /** @var \Bread\BreadCheckout\Helper\Customer */
    public $customerHelper;

    /** @var \Magento\Framework\Json\Helper\Data */
    public $jsonHelper;

    /**
     * Overview constructor.
     * @param \Magento\Catalog\Block\Product\Context $context
     * @param \Magento\Framework\Json\Helper\Data $jsonHelper
     * @param \Bread\BreadCheckout\Helper\Catalog $catalogHelper
     * @param \Bread\BreadCheckout\Helper\Customer $customerHelper
     * @param \Bread\BreadCheckout\Helper\Data $dataHelper
     * @param \Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory $configurableProductFactory
     * @param \Magento\ConfigurableProduct\Block\Product\View\Type\ConfigurableFactory $configurableBlockFactory
     * @param \Bread\BreadCheckout\Helper\Quote $quoteHelper
     * @param \Magento\Framework\Stdlib\ArrayUtils $arrayUtils
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\ConfigurableProduct\Helper\Data $configurableHelper
     * @param \Magento\Catalog\Helper\Product $catalogProductHelper
     * @param \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param \Magento\ConfigurableProduct\Model\ConfigurableAttributeData $configurableAttributeData
     * @param array $data
     */
    public function __construct(
        \Magento\Catalog\Block\Product\Context $context,
        \Magento\Framework\Json\Helper\Data $jsonHelper,
        \Bread\BreadCheckout\Helper\Catalog $catalogHelper,
        \Bread\BreadCheckout\Helper\Customer $customerHelper,
        \Bread\BreadCheckout\Helper\Data $dataHelper,
        \Magento\ConfigurableProduct\Model\Product\Type\ConfigurableFactory $configurableProductFactory,
        \Magento\ConfigurableProduct\Block\Product\View\Type\ConfigurableFactory $configurableBlockFactory,
        \Bread\BreadCheckout\Helper\Quote $quoteHelper,
        \Magento\Framework\Stdlib\ArrayUtils $arrayUtils,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\ConfigurableProduct\Helper\Data $configurableHelper,
        \Magento\Catalog\Helper\Product $catalogProductHelper,
        \Magento\Customer\Helper\Session\CurrentCustomer $currentCustomer,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        \Magento\ConfigurableProduct\Model\ConfigurableAttributeData $configurableAttributeData,
        array $data = []
    ) {
        $this->quoteHelper = $quoteHelper;
        $this->customerHelper = $customerHelper;
        $this->jsonHelper = $jsonHelper;

        parent::__construct(
            $context,
            $jsonHelper,
            $catalogHelper,
            $customerHelper,
            $dataHelper,
            $configurableProductFactory,
            $configurableBlockFactory,
            $arrayUtils,
            $jsonEncoder,
            $configurableHelper,
            $catalogProductHelper,
            $currentCustomer,
            $priceCurrency,
            $configurableAttributeData,
            $data
        );
    }

    /**
     * Set Block Extra In Construct Flow
     */
    protected function _construct($bypass = false)
    {
        parent::_construct(true);
        if (!$bypass) {
            $this->setAdditionalData([]);
        }
    }

    /**
     * Get Product Data From Quote Items
     *
     * @return string
     */
    public function getProductDataJson()
    {
        $itemsData      = $this->quoteHelper->getCartOverviewItemsData();
        return $this->jsonEncode($itemsData);
    }

    /**
     * Checks Settings For Show On Checkout Overview Page During Output
     *
     * @return string
     */
    protected function _toHtml()
    {
        if ($this->quoteHelper->isEnabledOnCOP()) {
            return parent::_toHtml();
        }

        return '';
    }

    /**
     * Return Block View Product Code
     *
     * @return string
     */
    public function getBlockCode()
    {
        return (string) $this->quoteHelper->getBlockCodeCheckoutOverview();
    }

    /**
     * Check if checkout through Bread interaction is allowed
     *
     * @return mixed
     */
    public function getAllowCheckout()
    {
        return ($this->quoteHelper->getAllowCheckoutCP()) ? 'true' : 'false';
    }

    /**
     * Get Extra Button Design CSS
     *
     * @return mixed
     */
    public function getButtonDesign()
    {
        $design = $this->dataHelper->escapeCustomCSS($this->catalogHelper->getCartButtonDesign());
        return $design ? $design : parent::getButtonDesign();
    }

    /**
     * Validate allowed products wrapper for block class
     *
     * @return bool
     */
    public function validateAllowedProductTypes()
    {
        return $this->quoteHelper->validateAllowedProductTypes();
    }

    /**
     * Custom product type error message
     *
     * @return string
     */
    public function productTypeErrorMessage()
    {
        return $this->_escaper->escapeHtml($this->catalogHelper->getProductTypeMessage());
    }
}
