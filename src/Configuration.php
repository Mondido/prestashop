<?php declare(strict_types = 1);
namespace MondidoPayments;

use MondidoPayments;
use Configuration as PrestashopConfiguration;
use MondidoPayments\Exception\InvalidConfigurationValue;
use MondidoPayments\Exception\EmptyConfigurationValue;

class Configuration
{
    const MERCHANT_ID = 'MONDIDOPAYMENTS_MERCHANT_ID';
    const SECRET = 'MONDIDOPAYMENTS_SECRET';
    const PASSWORD = 'MONDIDOPAYMENTS_PASSWORD';
    const TEST_MODE = 'MONDIDOPAYMENTS_TEST_MODE';
    const PAYMENT_ACTION = 'MONDIDOPAYMENTS_PAYMENT_ACTION';
    const PAYMENT_VIEW = 'MONDIDOPAYMENTS_PAYMENT_VIEW';
    const PAYMENT_OPTIONS = 'MONDIDOPAYMENTS_PAYMENT_OPTIONS';

    private $module;

    public function __construct(MondidoPayments $module) {
        $this->module = $module;
    }

    public function paymentOptionsOptions() {
        return [
            ['value' => 'swish', 'label' => $this->module->l('Swish', 'configuration')],
            ['value' => 'bank', 'label' => $this->module->l('Bank', 'configuration')],
            ['value' => 'mondidopayments', 'label' => 'Mondido Payments'],
            ['value' => 'credit_card', 'label' => $this->module->l('Card', 'configuration')],
            ['value' => 'paypal', 'label' => $this->module->l('Paypal', 'configuration')],
            ['value' => 'invoice', 'label' => $this->module->l('Invoice', 'configuration')],
        ];
    }

    public function paymentActionOptions() {
        return [
            ['value' => 'capture', 'label' => $this->module->l('Capture', 'configuration')],
            ['value' => 'authorize', 'label' => $this->module->l('Authorize', 'configuration')],
        ];
    }

    public function paymentViewOptions() {
        return [
            ['value' => 'iframe', 'label' => $this->module->l('Iframe', 'configuration')],
            ['value' => 'redirect', 'label' => $this->module->l('Redirect', 'configuration')],
        ];
    }

    public function clearAll()
    {
        PrestashopConfiguration::deleteByName(self::MERCHANT_ID);
        PrestashopConfiguration::deleteByName(self::SECRET);
        PrestashopConfiguration::deleteByName(self::PASSWORD);
        PrestashopConfiguration::deleteByName(self::TEST_MODE);
        PrestashopConfiguration::deleteByName(self::PAYMENT_ACTION);
        PrestashopConfiguration::deleteByName(self::PAYMENT_VIEW);
        PrestashopConfiguration::deleteByName(self::PAYMENT_OPTIONS);
    }

    public function setMerchantId(int $value)
    {
        if (empty($value)) {
            return new EmptyConfigurationValue();
        }

        PrestashopConfiguration::updateValue(self::MERCHANT_ID, $value);
    }

    public function merchantId()
    {
        return $this->value(self::MERCHANT_ID);
    }

    public function setSecret(string $value)
    {
        if (empty($value)) {
            return new EmptyConfigurationValue();
        }

        PrestashopConfiguration::updateValue(self::SECRET, $value);
    }

    public function secret()
    {
        return $this->value(self::SECRET);
    }

    public function setPassword(string $value)
    {
        if (empty($value)) {
            return new EmptyConfigurationValue();
        }

        PrestashopConfiguration::updateValue(self::PASSWORD, $value);
    }

    public function password()
    {
        return $this->value(self::PASSWORD);
    }

    public function setTestMode($value)
    {
        if (!is_bool($value)) {
            return new InvalidConfigurationValue($value);
        }
        PrestashopConfiguration::updateValue(self::TEST_MODE, $value);
    }

    public function isTestMode()
    {
        $is_test = $this->value(self::TEST_MODE, true);

        if ($is_test === true || $is_test === '1') {
            return true;
        }

        return false;
    }

    public function setPaymentAction(string $value)
    {
        if (!$this->validOptionValue($value, $this->paymentActionOptions())) {
            return new InvalidConfigurationValue($value);
        }

        PrestashopConfiguration::updateValue(self::PAYMENT_ACTION, $value);
    }

    public function paymentAction()
    {
        return $this->value(self::PAYMENT_ACTION, $this->paymentActionOptions()[0]['value']);
    }

    public function setPaymentView(string $value)
    {
        if (!$this->validOptionValue($value, $this->paymentViewOptions())) {
            return new InvalidConfigurationValue($value);
        }

        PrestashopConfiguration::updateValue(self::PAYMENT_VIEW, $value);
    }

    public function paymentView()
    {
        return $this->value(self::PAYMENT_VIEW, $this->paymentViewOptions()[0]['value']);
    }

    public function setPaymentOptions(array $options)
    {
        foreach ($options as $option) {
            if (!$this->validOptionValue($option, $this->paymentOptionsOptions())) {
                return new InvalidConfigurationValue($option);
            }
        }

        PrestashopConfiguration::updateValue(self::PAYMENT_OPTIONS, json_encode($options));
    }

    public function paymentOptions()
    {
        $options = [];
        $saved = json_decode($this->value(self::PAYMENT_OPTIONS, '["mondidopayments"]'));

        foreach ($this->paymentOptionsOptions() as $option) {
            $key = $option['value'];
            $options[$key] = in_array($key, $saved, true);
        }

        return $options;
    }

    public function isConfigured()
    {
        return $this->secret() && $this->password() && $this->merchantId();
    }

    public function setTransactionStatusDeclined(string $value)
    {
        PrestashopConfiguration::updateValue('MONDIDOPAYMENTS_ORDER_STATE_DECLINED', $value);
    }

    public function transactionStatusDeclined()
    {
        return (int) $this->value('MONDIDOPAYMENTS_ORDER_STATE_DECLINED');
    }

    public function setTransactionStatePending(string $value)
    {
        PrestashopConfiguration::updateValue('MONDIDOPAYMENTS_ORDER_STATE_STATUS_PENDING', $value);
    }

    public function transactionStatePending()
    {
        return (int) $this->value('MONDIDOPAYMENTS_ORDER_STATE_STATUS_PENDING');
    }

    public function setTransactionStatusPending(string $value)
    {
        PrestashopConfiguration::updateValue('MONDIDOPAYMENTS_ORDER_STATE_PENDING', $value);
    }

    public function transactionStatusPending()
    {
        return (int) $this->value('MONDIDOPAYMENTS_ORDER_STATE_PENDING');
    }

    public function transactionStatusFailed()
    {
        return (int) $this->value('PS_OS_ERROR');
    }

    public function transactionStatusApproved()
    {
        return (int) $this->value('PS_OS_PAYMENT');
    }

    public function transactionStatusAuthorized()
    {
        return (int) $this->value('PS_OS_PREPARATION');
    }

    public function transactionStatusRefunded()
    {
        return (int) $this->value('PS_OS_REFUND');
    }

    public function transactionStatusCanceled()
    {
        return (int) $this->value('PS_OS_CANCELED');
    }

    public function defaultLanguage()
    {
        return $this->value('PS_LANG_DEFAULT');
    }

    public function isGuestCheckoutEnabled()
    {
        return $this->value('PS_GUEST_CHECKOUT_ENABLED');
    }

    private function validOptionValue($value, $options) {
        $option_values = array_map(function($option) {
            return $option['value'];
        }, $options);

        return in_array($value, $option_values, true);
    }

    private function value($key, $default = null) {
        return PrestashopConfiguration::get($key, null, null, null, $default);
    }
}
