<?php declare(strict_types = 1);
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/vendor/autoload.php';

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use MondidoPayments\Configuration;
use MondidoPayments\Lock;
use MondidoPayments\SettingsForm;
use MondidoPayments\Api;
use MondidoPayments\Hook;

class mondidopayments extends PaymentModule
{
    private $config;

    public function __construct()
    {
        $this->name = 'mondidopayments';
        $this->version = '2.0.3';
        $this->author = 'Mondido Payments';
        $this->tab = 'payments_gateways';

        $this->need_instance = true;
        $this->bootstrap = true;
        $this->is_eu_compatible = true;
        $this->ps_versions_compliancy = ['min' => '1.7', 'max' => '1.7'];

        parent::__construct();

        $this->config = new Configuration($this);

        if (!$this->config->isConfigured()) {
            $this->warning = $this->l('Please configure module');
        }

        $this->displayName = 'Mondido Payments';
        $this->description = sprintf($this->l('Payment module for %s.'), 'Mondido Payments');
    }

    public function install()
    {
        return parent::install() &&
            $this->createOrderStates() &&
            $this->createDatabaseTables() &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('displayAdminOrder') &&
            $this->registerHook('displayAdminAfterHeader') &&
            $this->registerHook('actionOrderStatusUpdate');
    }

    public function uninstall()
    {
        Configuration::clearAll();
        return parent::uninstall();
    }

    public function getContent()
    {
        $form = new SettingsForm($this, $this->context);

        $errors = $form->updateConfig($this->config);
        return $form->render($this->config, $this->name, $errors);
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function getContext()
    {
        return $this->context;
    }

    public function getApiClient()
    {
        return new Api($this->config->merchantId(), $this->config->password());
    }

    public function getPaymentMethodName($transaction)
    {
        $method = $transaction->transaction_type;

        $names = [
            'after_pay' => 'After Pay',
            'amex' => 'American Express',
            'bank' => 'Bank',
            'credit_card' => 'Card',
            'diners' => 'Diners',
            'discover' => 'Discover',
            'e_payment' => 'E-payment',
            'e_payments' => 'E-Payments',
            'invoice' => 'Invoice',
            'jcb' => 'JCB',
            'manual_invoice' => 'Manual Invoice',
            'mastercard' => 'Mastercard',
            'mobile_pay' => 'Mobile Pay',
            'payment' => 'Payment',
            'paypal' => 'PayPal',
            'recurring' => 'Recurring',
            'siirto' => 'Siirto',
            'stored_card' => 'Stored card',
            'swish' => 'Swish',
            'vipps' => 'Vipps',
            'visa' => 'Visa',
        ];

        if (array_key_exists($method, $names)) {
            return $names[$method];
        }
        return $method;
    }

    public function hookPaymentOptions()
    {
        return (new Hook($this))->paymentOptions();
    }

    public function hookPaymentReturn($params)
    {
        return (new Hook($this))->paymentReturn($params);
    }

    public function hookActionOrderStatusUpdate($params)
    {
        return (new Hook($this))->orderStatusUpdate($params);
    }

    public function hookDisplayAdminAfterHeader()
    {
        return (new Hook($this))->displayAdminAfterHeader();
    }

    public function hookDisplayAdminOrder(&$params)
    {
        return (new Hook($this))->displayAdminOrder($params);
    }

    private function createDatabaseTables()
    {
        return Lock::install();
    }

    private function createOrderStates()
    {
        if (!$this->config->transactionStatusPending()) {
            $pending = new OrderState(null, $this->config->defaultLanguage());
            $pending->name = 'Mondido Payments payment pending';
            $pending->color = '#4169E1';
            $pending->invoice = false;
            $pending->send_email = false;
            $pending->module_name = $this->name;
            $pending->unremovable = true;
            $pending->hidden = false;
            $pending->logable = false;
            $pending->delivery = false;
            $pending->shipped = false;
            $pending->paid = false;
            $pending->deleted = false;
            $pending->template = 'preparation';
            $pending->add();

            $this->config->setTransactionStatusPending($pending->id);

            if (file_exists(dirname(dirname(__DIR__)) . '/img/os/9.gif')) {
                copy(dirname(dirname(__DIR__)) . '/img/os/9.gif', dirname(dirname(__DIR__)) . '/img/os/' . $pending->id . '.gif');
            }
        }

        if (!$this->config->transactionStatusAuthorized()) {
            $authorized = new OrderState(null, $this->config->defaultLanguage());
            $authorized->name = 'Mondido Payments payment authorized';
            $authorized->color = '#4169E1';
            $authorized->invoice = false;
            $authorized->send_email = false;
            $authorized->module_name = $this->name;
            $authorized->unremovable = true;
            $authorized->hidden = false;
            $authorized->logable = true;
            $authorized->delivery = false;
            $authorized->shipped = false;
            $authorized->paid = false;
            $authorized->deleted = false;
            $authorized->template = 'order_changed';
            $authorized->add();

            $this->config->setTransactionStatusAuthorized($authorized->id);

            if (file_exists(dirname(dirname(__DIR__)) . '/img/os/10.gif')) {
                copy(dirname(dirname(__DIR__)) . '/img/os/10.gif', dirname(dirname(__DIR__)) . '/img/os/' . $authorized->id . '.gif');
            }
        }

        if (!$this->config->transactionStatusDeclined()) {
            $declined = new OrderState(null, $this->config->defaultLanguage());
            $declined->name = 'Mondido Payments payment declined';
            $declined->color = '#8f0621';
            $declined->invoice = false;
            $declined->send_email = false;
            $declined->module_name = $this->name;
            $declined->unremovable = true;
            $declined->hidden = false;
            $declined->logable = false;
            $declined->delivery = false;
            $declined->shipped = false;
            $declined->paid = false;
            $declined->deleted = false;
            $declined->template = 'payment_error';
            $declined->add();

            $this->config->setTransactionStatusDeclined($declined->id);

            if (file_exists(dirname(dirname(__DIR__)) . '/img/os/9.gif')) {
                copy(dirname(dirname(__DIR__)) . '/img/os/9.gif', dirname(dirname(__DIR__)) . '/img/os/' . $declined->id . '.gif');
            }
        }

        if (!$this->config->transactionStatePending()) {
            $pending = new OrderState(null, $this->config->defaultLanguage());
            $pending->name = 'Mondido Payments status change pending';
            $pending->color = '#4169E1';
            $pending->invoice = false;
            $pending->send_email = false;
            $pending->module_name = $this->name;
            $pending->unremovable = true;
            $pending->hidden = true;
            $pending->logable = false;
            $pending->delivery = false;
            $pending->shipped = false;
            $pending->paid = false;
            $pending->deleted = false;
            $pending->template = 'preparation';
            $pending->add();

            $this->config->setTransactionStatePending($pending->id);
        }

        return true;
    }

    public function isInStatusChangeState() {
        return array_key_exists('MONDIDOPAYMENTS_STATUS_CHANGING', $GLOBALS);
    }

    public function setIsInStatusChangeState() {
        $GLOBALS['MONDIDOPAYMENTS_STATUS_CHANGING'] = true;
    }
}
