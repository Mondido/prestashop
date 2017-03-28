<?php
/**
 * Mondido Payment Module for PrestaShop
 * @author    Mondido
 * @copyright 2017 Mondido
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * @link https://www.mondido.com
 */

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;

class mondidopay extends PaymentModule
{
    protected $_html = '';
    protected $_postErrors = [];

    public function __construct()
    {
        $this->name = 'mondidopay';
        $this->displayName = $this->trans('Mondido Payments', [], 'Modules.MondidoPay.Admin');
        $this->description = $this->trans('Online payment by Mondido', [], 'Modules.MondidoPay.Admin');
        $this->author = 'Mondido';
        $this->version = '2.0.0';
        $this->tab = 'payments_gateways';
        $this->confirmUninstall = $this->trans('Are you sure you want to uninstall?', [], 'Modules.MondidoPay.Admin');
        $this->ps_versions_compliancy = ['min' => '1.7.0.0', 'max' => _PS_VERSION_];
        $this->bootstrap = true;
        $this->controllers = ['payment', 'validation', 'transaction', 'error'];
        $this->need_instance = 1;
        $this->is_eu_compatible = 1;

        // Init Configuration
        $config = Configuration::getMultiple(['MONDIDO_MERCHANTID', 'MONDIDO_SECRET', 'MONDIDO_PASSWORD', 'MONDIDO_TEST', 'MONDIDO_AUTHORIZE']);
        $this->merchantID = isset($config['MONDIDO_MERCHANTID']) ? $config['MONDIDO_MERCHANTID'] : '';
        $this->secretCode = isset($config['MONDIDO_SECRET']) ? $config['MONDIDO_SECRET'] : '';
        $this->password = isset($config['MONDIDO_PASSWORD']) ? $config['MONDIDO_PASSWORD'] : '';
        $this->test = isset($config['MONDIDO_TEST']) ? $config['MONDIDO_TEST'] : false;
        $this->authorize = isset($config['MONDIDO_AUTHORIZE']) ? $config['MONDIDO_AUTHORIZE'] : false;

        parent::__construct();

        if (empty($this->merchantID) || empty($this->password) || empty($this->secretCode)) {
            $this->warning = $this->trans('Please configure module', [], 'Modules.MondidoPay.Admin');
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
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('actionPaymentConfirmation') &&
            $this->registerHook('paymentOptions');
    }

    /**
     * UnInstall Hook
     * @return bool
     */
    public function uninstall()
    {
        Configuration::deleteByName('MONDIDO_MERCHANTID');
        Configuration::deleteByName('MONDIDO_SECRET');
        Configuration::deleteByName('MONDIDO_PASSWORD');
        Configuration::deleteByName('MONDIDO_TEST');
        Configuration::deleteByName('MONDIDO_AUTHORIZE');
        return parent::uninstall();
    }

    /**
     * Add Order Statuses
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
     * Payment Options Hook
     * @param $params
     * @return array
     */
    public function hookPaymentOptions($params)
    {
        if (!$this->active) {
            return;
        }

        $newOption = new PaymentOption();
        $newOption->setCallToActionText($this->trans('Pay with your Credit Card', [], 'Modules.MondidoPay.Shop'))
            ->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true));

        return [
            $newOption,
        ];
    }

    /**
     * Payment Return Hook
     * @param $params
     * @return bool
     */
    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $message = '';
        $order = $params['order'];

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
                $message = $this->trans('Payment declined', [], 'Modules.MondidoPay.Shop');
                break;
            case Configuration::get('PS_OS_ERROR'):
                $status = 'error';
                $message = $this->trans('Payment error', [], 'Modules.MondidoPay.Shop');
                break;
            default:
                $status = 'error';
                $message = $this->trans('Unknown error', [], 'Modules.MondidoPay.Shop');
        }
        $this->context->smarty->assign([
            'shop_name' => $this->context->shop->name,
            'message' => $message,
            'status' => $status,
            'id_order' => $order->id
        ]);
        if (property_exists($order, 'reference') && !empty($order->reference)) {
            $this->smarty->assign('reference', $order->reference);
        }
        return $this->display(__FILE__, 'payment_return.tpl');
    }

    /**
     *Configuration Form
     * @return mixed
     */
    public function renderForm()
    {
        $fields_form = [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Account details', [], 'Modules.MondidoPay.Admin'),
                    'icon' => 'icon-envelope'
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Merchant ID', [], 'Modules.MondidoPay.Admin'),
                        'name' => 'MONDIDO_MERCHANTID',
                        'required' => true
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Secret', [], 'Modules.MondidoPay.Admin'),
                        'name' => 'MONDIDO_SECRET',
                        'desc' => $this->trans('Given secret code from Mondido', [], 'Modules.MondidoPay.Admin'),
                        'required' => true
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Password', [], 'Modules.MondidoPay.Admin'),
                        'name' => 'MONDIDO_PASSWORD',
                        'desc' => $this->trans('API Password from Mondido', [], 'Modules.MondidoPay.Admin'),
                        'required' => true,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Set in testmode', [], 'Modules.MondidoPay.Admin'),
                        'name' => 'MONDIDO_TEST',
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ]
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Authorize', [], 'Modules.MondidoPay.Admin'),
                        'name' => 'MONDIDO_AUTHORIZE',
                        'desc' => $this->trans('Reserve money, do not auto-capture', [], 'Modules.MondidoPay.Admin'),
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'authorize_on',
                                'value' => true,
                                'label' => $this->l('Enabled'),
                            ],
                            [
                                'id' => 'authorize_off',
                                'value' => false,
                                'label' => $this->l('Disabled'),
                            ]
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ]
            ],
        ];

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;

        $lang = new Language((int)Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG') ?: 0;

        $this->fields_form = [];
        $helper->id = (int)Tools::getValue('id_carrier');
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure='
            . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id
        ];

        return $helper->generateForm([$fields_form]);
    }

    /**
     * Configuration Values
     * @return array
     */
    public function getConfigFieldsValues()
    {
        return [
            'MONDIDO_PASSWORD' => Tools::getValue('MONDIDO_PASSWORD', Configuration::get('MONDIDO_PASSWORD')),
            'MONDIDO_SECRET' => Tools::getValue('MONDIDO_SECRET', Configuration::get('MONDIDO_SECRET')),
            'MONDIDO_MERCHANTID' => Tools::getValue('MONDIDO_MERCHANTID', Configuration::get('MONDIDO_MERCHANTID')),
            'MONDIDO_TEST' => Tools::getValue('MONDIDO_TEST', Configuration::get('MONDIDO_TEST')),
            'MONDIDO_AUTHORIZE' => Tools::getValue('MONDIDO_AUTHORIZE', Configuration::get('MONDIDO_AUTHORIZE'))
        ];
    }

    /**
     * Configuration Validation
     * @return void
     */
    protected function _postValidation()
    {
        if (Tools::isSubmit('btnSubmit')) {
            if (empty(Tools::getValue('MONDIDO_MERCHANTID'))) {
                $this->_postErrors[] = $this->trans('Merchant ID required.', [], 'Modules.MondidoPay.Admin');
            }
            if (empty(Tools::getValue('MONDIDO_SECRET'))) {
                $this->_postErrors[] = $this->trans('Secret required.', [], 'Modules.MondidoPay.Admin');
            }
            if (empty(Tools::getValue('MONDIDO_PASSWORD'))) {
                $this->_postErrors[] = $this->trans('Password required.', [], 'Modules.MondidoPay.Admin');
            }
        }
    }

    /**
     * Configuration Handler
     * @return void
     */
    protected function _postProcess()
    {
        if (Tools::isSubmit('btnSubmit')) {
            Configuration::updateValue('MONDIDO_MERCHANTID', Tools::getValue('MONDIDO_MERCHANTID'));
            Configuration::updateValue('MONDIDO_SECRET', Tools::getValue('MONDIDO_SECRET'));
            Configuration::updateValue('MONDIDO_PASSWORD', Tools::getValue('MONDIDO_PASSWORD'));
            Configuration::updateValue('MONDIDO_TEST', Tools::getValue('MONDIDO_TEST'));
            Configuration::updateValue('MONDIDO_AUTHORIZE', Tools::getValue('MONDIDO_AUTHORIZE'));
        }
        $this->_html .= $this->displayConfirmation($this->trans('Settings updated', [], 'Admin.Global'));
    }

    /**
     * Additional template for Configuration
     * @return mixed
     */
    protected function _displayInfos()
    {
        return $this->display(__FILE__, 'infos.tpl');
    }

    /**
     * Configuration
     * @return string
     */
    public function getContent()
    {
        if (Tools::isSubmit('btnSubmit')) {
            $this->_postValidation();
            if (!count($this->_postErrors)) {
                $this->_postProcess();
            } else {
                foreach ($this->_postErrors as $err) {
                    $this->_html .= $this->displayError($err);
                }
            }
        } else {
            $this->_html .= '<br />';
        }

        $this->_html .= $this->_displayInfos();
        $this->_html .= $this->renderForm();

        return $this->_html;
    }

    /**
     * Lookup transaction data
     * @param $transaction_id
     * @return array|bool
     */
    public function lookupTransaction($transaction_id)
    {
        $streamcontext = stream_context_create([
            'http' => [
                'method' => 'GET',
                'header' => 'Authorization: Basic ' . base64_encode("{$this->merchantID}:{$this->password}")
            ]
        ]);
        $result = Tools::file_get_contents('https://api.mondido.com/v1/transactions/' . $transaction_id, false, $streamcontext);
        $data = json_decode($result, true);
        return $data;
    }

    /**
     * Confirm placed order
     * @param $order_id
     * @param $transaction_data
     * @return void
     */
    public function confirmOrder($order_id, $transaction_data)
    {
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

    /**
     * Get an order by its cart id
     *
     * @param integer $id_cart Cart id
     * @return array Order details
     */
    public static function getOrderByCartId($id_cart)
    {
        $sql = 'SELECT `id_order`
				FROM `' . _DB_PREFIX_ . 'orders`
				WHERE `id_cart` = ' . (int)($id_cart)
            . Shop::addSqlRestriction();
        $result = Db::getInstance()->getRow($sql, false);

        return isset($result['id_order']) ? $result['id_order'] : false;
    }

    /**
     * Display Error
     * @param $message
     * @return void
     */
    public function showError($message)
    {
        @session_start();
        $_SESSION['message'] = $message;
        Tools::redirect($this->context->link->getModuleLink($this->module->name, 'error', [], true));
    }
}
