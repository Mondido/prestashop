<?php declare(strict_types = 1);

namespace MondidoPayments;

use ContextCore;
use MondidoPayments;
use Tools;
use HelperForm;
use MondidoPayments\Exception\InvalidConfigurationValue;
use MondidoPayments\Exception\EmptyConfigurationValue;

class SettingsForm
{
    private $module;
    private $context;

    const MERCHANT_ID = 'merchant_id';
    const SECRET = 'secret';
    const PASSWORD = 'password';
    const TEST_MODE = 'test_mode';
    const PAYMENT_ACTION = 'payment_action';
    const PAYMENT_VIEW = 'payment_view';
    const PAYMENT_OPTIONS = 'payment_options';

    public function __construct(MondidoPayments $module, ContextCore $context)
    {
        $this->module = $module;
        $this->context = $context;
    }

    public function render(Configuration $config, string $name, array $errors)
    {
        $general_settings = [
            'form' => [
                'legend' => [
                    'title' => $this->module->l('General settings', 'settingsform'),
                    'icon' => 'icon-cogs',
                ],
                'error' => $this->generateErrors($errors),
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->module->l('Merchant id', 'settingsform'),
                        'name' => self::MERCHANT_ID,
                        'required' => true,
                    ],
                    [
                        'type' => 'password',
                        'label' => $this->module->l('Secret', 'settingsform'),
                        'name' => self::SECRET,
                        'required' => true,
                    ],
                    [
                        'type' => 'password',
                        'label' => $this->module->l('Password', 'settingsform'),
                        'name' => self::PASSWORD,
                        'required' => true,
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->module->l('Test mode', 'settingsform'),
                        'name' => self::TEST_MODE,
                        'is_bool' => true,
                        'values' => [
                            [
                                'id' => 'test_mode_on',
                                'value' => true,
                                'label' => $this->module->l('Enabled', 'settingsform'),
                            ],
                            [
                                'id' => 'test_mode_off',
                                'value' => false,
                                'label' => $this->module->l('Disabled', 'settingsform'),
                            ],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Payment action', 'settingsform'),
                        'name' => self::PAYMENT_ACTION,
                        'options' => $this->mapOptionEntries($config->paymentActionOptions()),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->module->l('Payment view', 'settingsform'),
                        'name' => self::PAYMENT_VIEW,
                        'options' => $this->mapOptionEntries($config->paymentViewOptions()),
                    ],
                ],
                'submit' => [
                    'title' => $this->module->l('Save', 'settingsform'),
                ]
            ],
        ];

        $payment_options = [
            'form' => [
                'legend' => [
                    'title' => $this->module->l('Payment options', 'settingsform'),
                    'icon' => 'icon-cogs',
                ],
                'input' => [],
                'submit' => [
                    'title' => $this->module->l('Save', 'settingsform'),
                ]
            ]
        ];

        foreach ($config->paymentOptionsOptions() as $option) {
            $payment_options['form']['input'][] = [
                'type' => 'switch',
                'label' => $option['label'],
                'name' => self::PAYMENT_OPTIONS . "[$option[value]]",
                'is_bool' => true,
                'values' => [
                    [
                        'id' => 'option_on',
                        'value' => true,
                        'label' => $this->module->l('Enabled', 'settingsform'),
                    ],
                    [
                        'id' => 'option_off',
                        'value' => false,
                        'label' => $this->module->l('Disabled', 'settingsform'),
                    ],
                ],
            ];
        }

        $helper = new HelperForm();
        $helper->show_toolbar = true;
        $helper->submit_action = 'btnSubmit';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false).'&configure='.$name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = [
            'fields_value' => [
                self::MERCHANT_ID => Tools::getValue(self::MERCHANT_ID, $config->merchantId()),
                self::SECRET => Tools::getValue(self::SECRET, $config->secret()),
                self::PASSWORD => Tools::getValue(self::PASSWORD, $config->password()),
                self::TEST_MODE => Tools::getValue(self::TEST_MODE, $config->isTestMode()),
                self::PAYMENT_ACTION => Tools::getValue(self::PAYMENT_ACTION, $config->paymentAction()),
                self::PAYMENT_VIEW => Tools::getValue(self::PAYMENT_VIEW, $config->paymentView()),
            ],
        ];

        foreach ($config->paymentOptions() as $option => $value) {
            $helper->tpl_vars['fields_value'][self::PAYMENT_OPTIONS . "[{$option}]"] = $value;
        }

        return $helper->generateForm([$general_settings, $payment_options]);
    }

    public function updateConfig(Configuration &$config)
    {
        $password = Tools::getValue(self::PASSWORD);
        if (empty($password)) {
            $password = $config->password();
        }
        $secret = Tools::getValue(self::SECRET);
        if (empty($secret)) {
            $secret = $config->secret();
        }

        if (Tools::isSubmit('btnSubmit')) {
            return array_filter([
                self::MERCHANT_ID => $config->setMerchantId((int) Tools::getValue(self::MERCHANT_ID)),
                self::SECRET => $config->setSecret($secret),
                self::PASSWORD => $config->setPassword($password),
                self::TEST_MODE => $config->setTestMode(Tools::getValue(self::TEST_MODE) == '1' ? true : false),
                self::PAYMENT_ACTION => $config->setPaymentAction(Tools::getValue(self::PAYMENT_ACTION)),
                self::PAYMENT_VIEW => $config->setPaymentView(Tools::getValue(self::PAYMENT_VIEW)),
                self::PAYMENT_OPTIONS => $config->setPaymentOptions($this->updatedPaymentOptions()),
            ]);
        }

        return [];
    }

    private function generateErrors($errors)
    {
        if (count($errors) === 0) {
            return;
        }
        $invalidValue = $this->module->l('Invalid value', 'settingsform');
        $emptyValue = $this->module->l('Empty value', 'settingsform');

        $output = '<ul>';

        foreach ($errors as $field => $error) {
            $label = $this->module->l($field, 'settingsform');
            if ($error instanceof InvalidConfigurationValue) {
                $output .= "<li>$label: $invalidValue '{$error->getMessage()}'</li>";
            } elseif ($error instanceof EmptyConfigurationValue) {
                $output .= "<li>$label: $emptyValue</li>";
            }
        }

        return $output . '</ul>';
    }

    private function mapOptionEntries($entries)
    {
        return [
            'id' => 'id',
            'name' => 'name',
            'query' => array_map(function($entry) {
                return ['id' => $entry['value'], 'name' => $entry['label']];
            }, $entries),
        ];
    }

    private function updatedPaymentOptions() {
        $names = [];
        foreach (Tools::getValue(self::PAYMENT_OPTIONS) as $name => $value) {
            if ($value === '1' || $value === true) {
                $names[] = $name;
            }
        }

        return $names;
    }
}
