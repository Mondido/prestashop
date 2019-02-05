<?php
/**
 * Mondido Payment Module for PrestaShop
 * @author    Mondido
 * @copyright 2018 Mondido
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @link https://www.mondido.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

class mondidopay extends PaymentModule 
{
    protected $_errors = array();
    public function __construct() 
    {
        $this->name = 'mondidopay';
        $this->displayName = $this->l('MONDIDO PAYMENTS');
        $this->description = $this->l('Online payment by Mondido');
        $this->author = 'Mondido';
        $this->version = '1.5.3';
        $this->tab = 'payments_gateways';
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
        $this->need_instance = 1;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;

        // Init Configuration
        $config = Configuration::getMultiple(array('MONDIDO_MERCHANTID', 'MONDIDO_PASSWORD', 'MONDIDO_SECRET', 'MONDIDO_TEST', 'MONDIDO_DEV'));
        $this->merchantID = isset($config['MONDIDO_MERCHANTID']) ? $config['MONDIDO_MERCHANTID'] : '';
        $this->password = isset($config['MONDIDO_PASSWORD']) ? $config['MONDIDO_PASSWORD'] : '';
        $this->secretCode = isset($config['MONDIDO_SECRET']) ? $config['MONDIDO_SECRET'] : '';
        $this->test = isset($config['MONDIDO_TEST']) ? $config['MONDIDO_TEST'] : '';
        $this->dev = isset($config['MONDIDO_DEV']) ? $config['MONDIDO_DEV'] : '';

        parent::__construct();
    
        if (empty($this->merchantID) || empty($this->password) || empty($this->secretCode)) {
            $this->warning = $this->l('Please configure module');
        }

        // @todo Remove in future version
        $this->addOrderStates();
	    $this->registerHook('DisplayHeader');

        /* Backward compatibility */
        if (_PS_VERSION_ < '1.5') {
            require(_PS_MODULE_DIR_ . $this->name.'/backward_compatibility/backward.php');
        }
    }

	/**
	 * Install Hook
	 * @return bool
	 */
    public function install() 
    {
        // Install Order statuses
        $this->addOrderStates();

        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('DisplayHeader') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('payment') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('actionPaymentConfirmation') &&
            $this->registerHook('displayPayment');
    }

	/**
	 * UnInstall Hook
	 * @return mixed
	 */
    public function uninstall() 
    {
        Configuration::deleteByName('MONDIDO_MERCHANTID');
        Configuration::deleteByName('MONDIDO_SECRET');
        Configuration::deleteByName('MONDIDO_PASSWORD');
        Configuration::deleteByName('MONDIDO_TEST');
        Configuration::deleteByName('MONDIDO_DEV');
        Configuration::deleteByName('MONDIDO_SUCCESS_URL');
        Configuration::deleteByName('MONDIDO_ERROR_URL');
        return parent::uninstall();
    }

    /**
     * Add PayEx Order Statuses
     */
    private function addOrderStates()
    {
        // Pending
        if (!(Configuration::get('PS_OS_MONDIDOPAY_PENDING') > 0)) {
            $OrderState = new OrderState(null, Configuration::get('PS_LANG_DEFAULT'));
            $OrderState->name = 'Mondido: Pending';
            $OrderState->invoice = false;
            $OrderState->send_email = true;
            $OrderState->module_name = $this->name;
            $OrderState->color = '#4169E1';
            $OrderState->unremovable = true;
            $OrderState->hidden = false;
            $OrderState->logable = true;
            $OrderState->delivery = false;
            $OrderState->shipped = false;
            $OrderState->paid = false;
            $OrderState->deleted = false;
            $OrderState->template = 'preparation';
            $OrderState->add();

            Configuration::updateValue('PS_OS_MONDIDOPAY_PENDING', $OrderState->id);

            if (file_exists(dirname(dirname(dirname(__FILE__))) . '/img/os/9.gif')) {
                @copy(dirname(dirname(dirname(__FILE__))) . '/img/os/9.gif', dirname(dirname(dirname(__FILE__))) . '/img/os/' . $OrderState->id . '.gif');
            }
        }

        // Authorized
        if (!(Configuration::get('PS_OS_MONDIDOPAY_AUTHORIZED') > 0)) {
            $OrderState = new OrderState(null, Configuration::get('PS_LANG_DEFAULT'));
            $OrderState->name = 'Mondido: Authorized';
            $OrderState->invoice = false;
            $OrderState->send_email = true;
            $OrderState->module_name = $this->name;
            $OrderState->color = '#FF8C00';
            $OrderState->unremovable = true;
            $OrderState->hidden = false;
            $OrderState->logable = true;
            $OrderState->delivery = false;
            $OrderState->shipped = false;
            $OrderState->paid = false;
            $OrderState->deleted = false;
            $OrderState->template = 'order_changed';
            $OrderState->add();

            Configuration::updateValue('PS_OS_MONDIDOPAY_AUTHORIZED', $OrderState->id);

            if (file_exists(dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif')) {
                @copy(dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif', dirname(dirname(dirname(__FILE__))) . '/img/os/' . $OrderState->id . '.gif');
            }
        }

        // Approved
        if (!(Configuration::get('PS_OS_MONDIDOPAY_APPROVED') > 0)) {
            $OrderState = new OrderState(null, Configuration::get('PS_LANG_DEFAULT'));
            $OrderState->name = 'Mondido: Approved';
            $OrderState->invoice = true;
            $OrderState->send_email = true;
            $OrderState->module_name = $this->name;
            $OrderState->color = '#32CD32';
            $OrderState->unremovable = true;
            $OrderState->hidden = false;
            $OrderState->logable = true;
            $OrderState->delivery = false;
            $OrderState->shipped = false;
            $OrderState->paid = true;
            $OrderState->deleted = false;
            $OrderState->template = 'payment';
            $OrderState->add();

            Configuration::updateValue('PS_OS_MONDIDOPAY_APPROVED', $OrderState->id);

            if (file_exists(dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif')) {
                @copy(dirname(dirname(dirname(__FILE__))) . '/img/os/10.gif', dirname(dirname(dirname(__FILE__))) . '/img/os/' . $OrderState->id . '.gif');
            }
        }

        // Declined
        if (!(Configuration::get('PS_OS_MONDIDOPAY_DECLINED') > 0)) {
            $OrderState = new OrderState(null, Configuration::get('PS_LANG_DEFAULT'));
            $OrderState->name = 'Mondido: Declined';
            $OrderState->invoice = false;
            $OrderState->send_email = true;
            $OrderState->module_name = $this->name;
            $OrderState->color = '#DC143C';
            $OrderState->unremovable = true;
            $OrderState->hidden = false;
            $OrderState->logable = true;
            $OrderState->delivery = false;
            $OrderState->shipped = false;
            $OrderState->paid = false;
            $OrderState->deleted = false;
            $OrderState->template = 'payment_error';
            $OrderState->add();

            Configuration::updateValue('PS_OS_MONDIDOPAY_DECLINED', $OrderState->id);

            if (file_exists(dirname(dirname(dirname(__FILE__))) . '/img/os/9.gif')) {
                @copy(dirname(dirname(dirname(__FILE__))) . '/img/os/9.gif', dirname(dirname(dirname(__FILE__))) . '/img/os/' . $OrderState->id . '.gif');
            }
        }
    }

	/**
	 * Hook: DisplayHeader
	 * @param $params
	 */
	public function hookDisplayHeader($params)
	{
		$this->context->controller->addCSS($this->_path . 'css/style.css', 'all');
	}

	/**
	 * Hook: Payment
	 * Render payment method in payment methods list
	 * @param $params
	 */
    public function hookPayment($params) 
    {
	    if (!$this->active) {
		    return;
	    }

	    $this->smarty->assign(array(
		    'this_path' => $this->_path,
		    'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/',
	    ));

	    return $this->display(__FILE__, 'views/templates/hooks/payment.tpl');
    }

    /**
     * Hook: Payment Return
     * @param $params
     * @return bool
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }
        $message = '';
        $order = $params['objOrder'];
        switch ($order->current_state) {
            case Configuration::get('PS_OS_MONDIDOPAY_APPROVED'):
                $status = 'ok';
                break;
            case Configuration::get('PS_OS_MONDIDOPAY_PENDING');
            case Configuration::get('PS_OS_MONDIDOPAY_AUTHORIZED'):
                $status = 'pending';
                break;
            case Configuration::get('PS_OS_MONDIDOPAY_DECLINED'):
                $status = 'declined';
                $message = $this->l('Payment declined');
                break;
            case Configuration::get('PS_OS_ERROR'):
                $status = 'error';
                $message = $this->l('Payment error');
                break;
            default:
                $status = 'error';
                $message = $this->l('Order error');
        }
        $this->smarty->assign(array(
            'message' => $message,
            'status' => $status,
            'id_order' => $order->id
        ));
        if (property_exists($order, 'reference') && !empty($order->reference)) {
            $this->smarty->assign('reference', $order->reference);
        }
        return $this->display(__FILE__, 'confirmation.tpl');
    }

	/**
	 * Module Settings
	 * @return string
	 */
    public function getContent() 
    {
        if (Tools::getValue('mondido_updateSettings')) 
        {
            Configuration::updateValue('MONDIDO_MERCHANTID', Tools::getValue('merchantID'));
            Configuration::updateValue('MONDIDO_SECRET', Tools::getValue('secretCode'));
            Configuration::updateValue('MONDIDO_PASSWORD', Tools::getValue('password'));
            Configuration::updateValue('MONDIDO_TEST', Tools::getValue('test'));
            Configuration::updateValue('MONDIDO_DEV', Tools::getValue('dev'));
            Configuration::updateValue('MONDIDO_SUCCESS_URL', Tools::getValue('success_url'));
            Configuration::updateValue('MONDIDO_ERROR_URL', Tools::getValue('error_url'));
        }
        $this->context->smarty->assign(array(
        	'version' => $this->version,
            'merchantID' => $this->merchantID,
            'secretCode' => $this->secretCode,
            'password'	=> $this->password,
            'test'	=> $this->test,
            'dev'	=> $this->dev,
            'this_path' => $this->_path,
            'this_path_ssl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/'
        ));
        return $this->display(__FILE__, 'views/templates/admin/config.tpl');
    }

    /**
     * Lookup transaction data
     * @param $transaction_id
     * @return array|bool
     */
    public function lookupTransaction($transaction_id) {
        $streamcontext = stream_context_create(array(
            'http' => array(
                'method' => 'GET',
                'header' => 'Authorization: Basic ' . base64_encode("{$this->merchantID}:{$this->password}")
            )
        ));
        $result = Tools::file_get_contents('https://api.mondido.com/v1/transactions/' . $transaction_id, false, $streamcontext);
        $data = (array)Tools::jsonDecode($result, true);
        if (!$data) {
            return false;
        }

        return $data;
    }

    /**
     * Confirm placed order
     * @param $order_id
     * @param $transaction_data
     * @return void
     */
    public function confirmOrder($order_id, $transaction_data) {
        $order = new Order($order_id);
        //TODO: update delivery address if it is the case
        if ($transaction_data['transaction_type'] === 'invoice') {
            $pd = $transaction_data['payment_details'];

            $shipping_address = new Address((int)$order->id_address_invoice);
            if (!empty($pd['phone'])) {
                $shipping_address->phone = $pd['phone'];
            }
            if (!empty($pd['last_name'])) {
                $shipping_address->lastname = $pd['last_name'];
            }
            if (!empty($pd['first_name'])) {
                $shipping_address->firstname = $pd['first_name'];
            }
            if (!empty($pd['address_1'])) {
                $shipping_address->address1 = $pd['address_1'];
            }
            if (!empty($pd['address_2'])) {
                $shipping_address->address2 = $pd['address_2'];
            }
            if (!empty($pd['city'])) {
                $shipping_address->city = $pd['city'];
            }
            if (!empty($pd['zip'])) {
                $shipping_address->postcode = $pd['zip'];
            }
            if (!empty($pd['country_code'])) {
                $shipping_address->country = $pd['country_code'];
            }

            $shipping_address->update();
        }

        if (_PS_VERSION_ >= '1.5') {
            $payments = $order->getOrderPaymentCollection();
            if ($payments->count() > 0) {
                $payments[0]->transaction_id = $transaction_data['id'];
                $payments[0]->card_number = $transaction_data['card_number'];
                $payments[0]->card_holder = $transaction_data['card_holder'];
                $payments[0]->card_brand = $transaction_data['card_type'];
                $payments[0]->payment_method = $transaction_data['transaction_type'];
                $payments[0]->update();
            }
        }
    }

    /**
     * Get an order by its cart id
     *
     * @param integer $id_cart Cart id
     * @return array Order details
     */
    public static function getOrderByCartId($id_cart)
    {
        $sql = 'SELECT `id_order`
				FROM `'._DB_PREFIX_.'orders`
				WHERE `id_cart` = '.(int)($id_cart)
            . (_PS_VERSION_ < '1.5' ? '' : Shop::addSqlRestriction());
        $result = Db::getInstance()->getRow($sql, false);

        return isset($result['id_order']) ? $result['id_order'] : false;
    }

	/**
	 * Get Shop Domain
	 * @return string
	 */
    public static function getShopDomain()
    {
	    if (Tools::usingSecureMode()) {
	    	return Tools::getShopDomainSsl(true);
	    }

	    return Tools::getShopDomain(true);
    }

	/**
	 * Get Module Path
	 * @param bool $secure
	 *
	 * @return string
	 */
    public function getPath($secure = false)
    {
    	if ($secure) {
		    return Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'modules/' . $this->name . '/';
	    }

	    return $this->_path;
    }
}
