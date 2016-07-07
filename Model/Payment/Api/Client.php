<?php
/**
 * Class Bread_BreadCheckout_Model_Payment_Api_Client
 *
 * @author  Bread   copyright   2016
 * @author  Joel    @Mediotype
 */
namespace ;

class 
{
    const STATUS_AUTHORIZED     = 'AUTHORIZED';
    const STATUS_SETTLED        = 'SETTLED';
    const STATUS_PENDING        = 'PENDING';
    const STATUS_CANCELED       = 'CANCELED';

    /**
     * Call API Cancel Method
     *
     * @param $breadTransactionId
     * @param int $amount
     * @param array $lineItems
     * @throws \Exception
     */
    public function cancel($breadTransactionId, $amount = 0, $lineItems = array())
    {
        $data = array('type'   => 'cancel');

        if (!$amount == 0){
            $data['amount'] = $amount;
        }

        if(!empty($lineItems)){
            $data['lineItems'] = $lineItems;
        }

        $result = $this->call($this->getUpdateTransactionUrl($breadTransactionId), $data);

        if( $result->status != self::STATUS_CANCELED ){
            Mage::helper('breadcheckout')->log(array("ERROR"=>"Transaction cancel failed", "RESULT"=>$result), 'bread-exception.log');
            throw new \Magento\Framework\Exception\LocalizedException(Mage::helper('breadcheckout')->__('Transaction cancel failed (current transaction status :' . $result->status . ')'));
        }

        return $result;
    }

    /**
     * Call API Authorize Method
     *
     * @param $breadTransactionId
     * @param $amount
     * @param null $merchantOrderId
     * @return mixed
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function authorize($breadTransactionId, $amount, $merchantOrderId = null)
    {
        $data_array = array('type' => 'authorize');
        if ($merchantOrderId != null) {
            $data_array['merchantOrderId'] = $merchantOrderId;
        }

        $result = $this->call(
            $this->getUpdateTransactionUrl($breadTransactionId),
            $data_array);

        if ( $result->status != self::STATUS_AUTHORIZED )
        {
            Mage::helper('breadcheckout')->log(array("ERROR"=>"AUTHORIZATION FAILED", "RESULT"=>$result), 'bread-exception.log');
            throw new \Magento\Framework\Exception\LocalizedException(Mage::helper('breadcheckout')->__('Transaction authorize failed (current transaction status :' . $result->status . ')'));
        }

        $breadAmount = $result->total;
        if ( (int)$breadAmount != (int)$amount )
        {
            Mage::helper('breadcheckout')->log(array("ERROR"=>"BREAD AMOUNT AND QUOTE AMOUNT MIS-MATCH", "BREAD AMOUNT"=>(int)$breadAmount ,"QUOTE AMOUNT"=>(int)$amount , "RESULT"=>$result), 'bread-exception.log');
            throw new \Magento\Framework\Exception\LocalizedException(Mage::helper('breadcheckout')->__('Bread authorized amount ' . $breadAmount . ' but transaction expected ' . $amount));
        }

        return $result;
    }

    /**
     * Call API Update Order Id
     *
     * @param $breadTransactionId
     * @param $merchantOrderId
     * @return mixed
     * @throws \Exception
     */
    public function updateOrderId($breadTransactionId, $merchantOrderId)
    {
        $result = $this->call(
            $this->getTransactionInfoUrl($breadTransactionId),
            array('merchantOrderId' => $merchantOrderId),
            \Zend_Http_Client::PUT);

        return $result;
    }

    /**
     * Call API Update Order Id Capture Authorized Transaction Method
     *
     * @param $breadTransactionId
     * @return mixed
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function settle($breadTransactionId)
    {
        $result = $this->call(
            $this->getUpdateTransactionUrl($breadTransactionId),
            array('type' => 'settle'));

        if ( $result->status != self::STATUS_SETTLED ) {
            throw new \Magento\Framework\Exception\LocalizedException(Mage::helper('breadcheckout')->__('Transaction settle failed (current transaction status :' . $result->status . ')'));
        }

        return $result;
    }

    /**
     * Call API Refund Method
     *
     * @param $breadTransactionId
     * @param int $amount
     * @param array $lineItems
     * @return mixed
     * @throws \Exception
     */
    public function refund($breadTransactionId, $amount = 0, $lineItems = array())
    {
        $data = array('type'   => 'refund');

        if ( !$amount == 0 ) {
            $data['amount'] = $amount;
        }

        if( !empty($lineItems) ) {
            $data['lineItems'] = $lineItems;
        }

        return $this->call($this->getUpdateTransactionUrl($breadTransactionId), $data);
    }

    /**
     * Call API Get Info Method
     *
     * @param $breadTransactionId
     * @return mixed
     * @throws \Exception
     */
    public function getInfo($breadTransactionId)
    {
        return $this->call(
            $this->getTransactionInfoUrl($breadTransactionId),
            array(),
            \Zend_Http_Client::GET);
    }

    /**
     * Interact with the API
     *
     * @param $url
     * @param array $data
     * @param string $method
     * @return mixed
     * @throws \Exception
     */
    protected function call($url, array $data, $method = \Zend_Http_Client::POST)
    {
        $username   = Mage::helper('breadcheckout')->getApiPublicKey();
        $password   = Mage::helper('breadcheckout')->getApiSecretKey();

        try {
            $curl = curl_init($url);
            curl_setopt($curl, CURLOPT_HEADER, 0);
            curl_setopt($curl, CURLOPT_USERPWD, $username . ":" . $password);
            curl_setopt($curl, CURLOPT_TIMEOUT, 30);

            if ($method == \Zend_Http_Client::POST) {
                curl_setopt($curl, CURLOPT_POST, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, Mage::helper('core')->jsonEncode($data));
            }

            if ($method == \Zend_Http_Client::PUT) {
                curl_setopt($curl, CURLOPT_CUSTOMREQUEST, 'PUT');
                curl_setopt($curl, CURLOPT_POSTFIELDS, Mage::helper('core')->jsonEncode($data));
            }

            curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);
            $result = curl_exec($curl);
            $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);

            if( $status != 200 ) {
                throw new \Magento\Framework\Exception\LocalizedException(Mage::helper('breadcheckout')->__('Call to Bread API failed.  Error: '. $result));
            }
        } catch (Exception $e){
            Mage::helper('breadcheckout')->log( array(
                    "USER"      => $username,
                    "PASSWORD"  => $password,
                    "URL"       => $url,
                    "DATA"      => $data,
                    "RESULT"    => $result,
                ), 'bread-exception.log');

            curl_close($curl);
            throw $e;
        }

        curl_close($curl);

        Mage::helper('breadcheckout')->log(array(
                "USER"      => $username,
                "PASSWORD"  => $password,
                "URL"       => $url,
                "DATA"      => $data,
                "RESULT"    => $result,
            ));

        if(!$this->isJson($result)){
            throw new \Magento\Framework\Exception\LocalizedException(Mage::helper('breadcheckout')->__('API Response Is Not Valid JSON.  Result: ' . $result));
        }

        return json_decode($result);
    }

    /**
     * Form transaction info URI string
     *
     * @param $transactionId
     * @return string
     */
    protected function getTransactionInfoUrl($transactionId)
    {
        $baseUrl = Mage::helper('breadcheckout')->getTransactionApiUrl();
        return join('/', array(trim($baseUrl, '/'), 'transactions', trim($transactionId, '/')));
    }

    /**
     * Form update transaction URI string
     * @param $transactionId
     * @return string
     */
    protected function getUpdateTransactionUrl($transactionId)
    {
        $baseUrl = Mage::helper('breadcheckout')->getTransactionApiUrl();
        return join('/', array(trim($baseUrl, '/'), 'transactions/actions', trim($transactionId, '/')));
    }

    /**
     * Check a string to verify JSON format is valid
     *
     * @param $string
     * @return bool
     */
    protected function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}