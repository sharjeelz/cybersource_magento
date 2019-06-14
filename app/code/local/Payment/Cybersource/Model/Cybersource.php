<?php /**
 * Cybersource Model
 *
 * @category Payment
 * @package Payment_Cybersource
 * @author Andrew Moskal <andrew.moskal@softaddicts.com>
 * @license http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 */ class Payment_Cybersource_Model_Cybersource extends Mage_Payment_Model_Method_Abstract {
    const CYBERSOURCE_PAYMENT_TEST_URL = 'https://testsecureacceptance.cybersource.com/pay';
    const CYBERSOURCE_PAYMENT_LIVE_URL = 'https://secureacceptance.cybersource.com/pay';
    
    protected $_code = 'cybersource';
    protected $_formBlockType = 'cybersource/form';
    protected $_infoBlockType = 'cybersource/info';
    protected $_isGateway = false;
    protected $_canAuthorize = false;
    protected $_canCapture = true;
    protected $_canCapturePartial = false;
    protected $_canRefund = false;
    protected $_canVoid = false;
    protected $_canUseInternal = false;
    protected $_canUseCheckout = true;
    protected $_canUseForMultishipping = false;
    public function validate() {
        parent::validate();
        return $this;
    }
	public function guid(){
			return $time = str_replace(".", '', microtime(true));
			return $time.mt_rand(1000000, 9999999);
	}
    public function capture(Varien_Object $payment,$amount = null) {
        $payment->setStatus(self::STATUS_APPROVED)->setLastTransId($this->getTransactionId());
        return $this;
    }
    public function getCybersourceUrl() {
        return (1 === (int)$this->getConfigData('mode'))?self::CYBERSOURCE_PAYMENT_LIVE_URL:self::CYBERSOURCE_PAYMENT_TEST_URL;
    }
    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('cybersource/process/redirect',array('_secure' => true));
    }
    protected function getSuccessUrl() {
        return Mage::getUrl('cybersource/process/success', array('_secure' => true));
    }
    protected function getCancelUrl() {
        return Mage::getUrl('cybersource/process/cancel', array('_secure' => true));
    }
    
    protected function getFailureUrl() {
        return Mage::getUrl('cybersource/process/failure', array('_secure' => true));
    }
    protected function getResultUrl() {
        return Mage::getUrl('cybersource/process/result', array('_secure' => true));
    }
    public function getCustomer() {
        if (empty($this->_customer)) {
            $this->_customer = Mage::getSingleton('customer/session')->getCustomer();
        }
        return $this->_customer;
    }
    public function getCheckout() {
        if (empty($this->_checkout)) {
            $this->_checkout = Mage::getSingleton('checkout/session');
        }
        return $this->_checkout;
    }
    public function getQuote() {
        if (empty($this->_quote)) {
            $this->_quote = $this->getCheckout()->getQuote();
        }
        return $this->_quote;
    }
    public function getOrder() {
        if (empty($this->_order)) {
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($this->getCheckout()->getLastRealOrderId());
            $this->_order = $order;
        }
        return $this->_order;
    }
    public function getEmail() {
        $email = $this->getOrder()->getCustomerEmail();
        if (!$email) {
            $email = $this->getQuote()->getBillingAddress()->getEmail();
        }
        return $email;
    }
    public function getOrderAmount() {
        return sprintf('%.2f', $this->getOrder()->getGrandTotal());
    }
    public function getOrderCurrency() {
        $currency = $this->getOrder()->getOrderCurrency();
        return is_object($currency)? $currency->getCurrencyCode():null;
    }
    public function getHashSign($fields) {
        return Mage::helper('cybersource')->getHashSign($fields);
    }
    public function getFormFields() {
        $order = $this->getOrder();
        $fields = array();
        $fields['profile_id'] = $this->getConfigData('profile_id');
        $fields['access_key'] = $this->getConfigData('access_key');
        $fields['transaction_uuid'] = Mage::helper('core')->uniqHash();
        $fields['signed_field_names'] = '';
        $fields['unsigned_field_names'] = '';
        $fields['signed_date_time'] = gmdate("Y-m-d\TH:i:s\Z");
        $fields['locale'] = 'en';
        $fields['transaction_type'] = 'sale';
        $fields['reference_number'] = $order->getRealOrderId();
        $fields['amount'] = $this->getOrderAmount();
        $fields['currency'] = $this->getOrderCurrency();
        $billingAddress = $order->getBillingAddress();
        $fields['bill_to_address_city'] = $billingAddress->getCity();
        $fields['bill_to_address_country'] = $billingAddress->getCountry();
        $fields['bill_to_address_line1'] = substr($billingAddress->getStreet(1),0,19);
        $fields['bill_to_address_line2'] = substr($billingAddress->getStreet(2),0,19);
        $fields['bill_to_address_postal_code'] = $billingAddress->getPostcode();
        $fields['bill_to_address_state'] = $billingAddress->getRegionCode();
        $fields['bill_to_company_name'] = $billingAddress->getCompany();
        $fields['bill_to_email'] = $this->getEmail();
        $fields['bill_to_forename'] = $billingAddress->getFirstname();
        $fields['bill_to_surname'] = $billingAddress->getLastname();
        $fields['bill_to_phone'] = $billingAddress->getTelephone();
        $fields['customer_ip_address'] = Mage::helper('core/http')->getRemoteAddr();
		$address = $order->getShippingAddress();
        $fields['ship_to_address_city'] = $address->getCity();
        $fields['ship_to_address_country'] = $address->getCountry();
        $fields['ship_to_address_line1'] = substr($address->getStreet(1),0,19);
       // $fields['ship_to_address_line2'] = $address->getStreet(2);
        $fields['ship_to_address_postal_code'] = $address->getPostcode();
        $fields['ship_to_address_state'] = $address->getRegionCode();
       // $fields['ship_to_company_name'] = $address->getCompany();
        //$fields['ship_to_email'] = $this->getEmail();
        $fields['ship_to_forename'] = $address->getFirstname();
        $fields['ship_to_surname'] = $address->getLastname();
        $fields['ship_to_phone'] = $address->getTelephone();
		//$fields['consumer_id'] = Mage::getSingleton('customer/session')->getId();
		//$fields['customer_id'] = Mage::getSingleton('customer/session')->getId();
		$fields['consumer_id'] = Mage::getSingleton('customer/session')->getId();
		$fields['customer_id'] = Mage::getSingleton('customer/session')->getId();
		
		 $fields['bill_address1'] = substr($billingAddress->getStreet(1),0,19);
		 $fields['bill_city'] = $billingAddress->getCity();
    	 $fields['bill_country'] = $billingAddress->getCountry();
		 $fields['customer_email'] = $this->getEmail();
         $fields['customer_lastname'] = $billingAddress->getLastname(); //
   		$categories = array();
		$products = array();
		$items = $order->getAllItems();
        $n = 0;
        foreach ($items as $_item) {
            $fields['item_' . $n . '_code'] = 'default';
            $fields['item_' . $n . '_unit_price'] = sprintf("%01.2f", $_item->getPrice());
            $fields['item_' . $n . '_sku'] = $_item->getSku();
            $fields['item_' . $n . '_quantity'] = (int) $_item->getQtyOrdered();
            $fields['item_' . $n . '_name'] = $_item->getName();
			//
			$productId = $_item->getProductId();
			$product = Mage::getModel('catalog/product')->load($productId);
			$cats = $product->getCategoryIds();
			foreach ($cats as $category_id) {
				$_cat = Mage::getModel('catalog/category')->load($category_id) ;
				$categories[] = $_cat->getName();
			}
			$products[] = $_item->getName();
			
			//
			
            $n++;
        }
//
		$fields['merchant_defined_data1'] = 'WC'; //Number of Failed Authorizations Attempts
		$fields['merchant_defined_data2'] = 'Yes'; //Number of orders to date since registering
		//$fields['merchant_defined_data3'] = substr(implode(',', array_unique(str_replace('"',"",$categories))),0,19); //Product Category
		$fields['merchant_defined_data3'] = "Electronics";
		$fields['merchant_defined_data4'] = substr(implode(',', array_unique(str_replace('"',"",$products))),0,19);
		$repeat = "Yes";
		$orderp = Mage::getResourceModel('sales/order_collection')
			->addFieldToSelect('entity_id')
			->addFieldToFilter('customer_id',Mage::getSingleton('customer/session')->getId())
			->setCurPage(1)
			->setPageSize(1)
			->getFirstItem();
		
		if ($orderp->getId()) {
			$repeat = "Yes";
		} else {
			$repeat = "NO";
		}		
		
		$fields['merchant_defined_data5'] = $repeat;
		$fields['merchant_defined_data6'] = "Standard";//$order->getShippingDescription();
		$fields['merchant_defined_data7'] = $n;
		$fields['merchant_defined_data8'] = "Pakistan";//$address->getCountry();
		$fields['merchant_defined_data20'] = "NO"; //
        $fields['line_item_count'] = $n;
        $fields['signed_field_names'] = implode(',', array_keys($fields));
        $fields['signature'] = $this->getHashSign($fields);
		//echo implode(',', array_unique($products));
		//print_r($fields);
		//exit;
        return $fields;
    }
}
