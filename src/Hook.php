<?php declare(strict_types = 1);
namespace MondidoPayments;

use MondidoPayments;
use Tools;
use Order;
use OrderHistory;
use PrestaShop\PrestaShop\Core\Payment\PaymentOption;
use RuntimeException;
use PrestaShopLogger;

class Hook
{
    private $config;
    private $module;

    public function __construct(MondidoPayments $module) {
        $this->module = $module;
        $this->config = $module->getConfig();
        $this->context = $module->getContext();
        $this->api = $module->getApiClient();
    }

    public function paymentOptions()
    {
        if (!$this->module->active || !$this->config->isConfigured()) {
            return [];
        }

        $payment_view = $this->config->paymentView();
        $active_options = [];
        $available_options = $this->config->paymentOptions();
        $label = $this->module->l('Pay with %s', 'hook');

        foreach ($this->config->paymentOptionsOptions() as $option) {
            $name = $option['value'];
            if ($available_options[$name] === true) {
                $active_options[] = (new PaymentOption())
                    ->setModuleName('MONDIDOPAYMENTS')
                    ->setCallToActionText(sprintf($label, $option['label']))
                    ->setLogo("/modules/mondidopayments/views/images/payment_options/$name.png")
                    ->setAction($this->context->link->getModuleLink('mondidopayments', 'payment', array_filter([
                        'view' => $payment_view,
                        'payment_method' => $name === 'mondidopayments' ? null : $name,
                    ]), true));
            }
        }

        return $active_options;
    }

    public function paymentReturn($params)
    {
        if ($this->module->active) {
            if ((int) $params['order']->current_state === $this->config->transactionStatusPending()) {
                return $this->module->display(dirname(__DIR__), '/views/templates/payment/payment_pending.tpl');
            }
        }
    }

    public function displayAdminOrder(&$params)
    {
        return (new AdminDisplay($this->api, $this->context->smarty))->addBlockToOrderView($params['id_order']);
    }

    public function displayAdminAfterHeader()
    {
        $errors = [];

        if ($this->module->active && !$this->api->testCredentials()) {
            $errors[] = 'settings';
        }

        if (Tools::getValue('mondidopayments_error')) {
            $errors[] = Tools::getValue('mondidopayments_error');
        }

        return (new AdminDisplay($this->api, $this->context->smarty))->showErrors($errors);
    }

    public function orderStatusUpdate($params)
    {
        if ($this->module->isInStatusChangeState()) {
            return;
        }
        $this->module->setIsInStatusChangeState();
        $order = new Order($params['id_order']);
        try {
            $current_state = $order->getCurrentOrderState();
            $new_state = $params['newOrderStatus'];
            $transaction = $this->api->getTransactionFromReference($order->id_cart);

            if ($transaction) {
                $new_state_id = (int) $new_state->id;
                $current_state_id = $this->config->transactionStatusPending();

                if ($current_state) {
                    $current_state_id = $current_state->id;
                }

                if (!$this->isValidStatusChange($current_state_id, $new_state_id)) {
                    Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders', true, [], [
                        'vieworder' => '',
                        'id_order' => $params['id_order'],
                        'mondidopayments_error_message' => sprintf(
                            $this->module->l("Changing status from %s to %s not allowed", 'hook'),
                            $this->statusName($current_state),
                            $this->statusName($new_state)
                        )
                    ]));
                    return;
                }
                Lock::aquire('transaction', $transaction->id);
                if ($new_state_id === $this->config->transactionStatusApproved()) {
                    $actions = new AdminOrderActions($this->api, $this->config, $this->context->link);
                    $actions->capture($order, $transaction);
                }
                if ($new_state_id === $this->config->transactionStatusRefunded()) {
                    $actions = new AdminOrderActions($this->api, $this->config, $this->context->link);
                    $actions->refund($order, $transaction);
                }
                Lock::drop('transaction', $transaction->id);
            }
        } catch (\Throwable $error) {
            if ($transaction) {
                Lock::drop('transaction', $transaction->id);
            }
            PrestaShopLogger::addLog(
                $error->getMessage() . " : " . basename($error->getFile()) . ":" . $error->getLine(),
                4,
                0,
                'Order',
                (int) $params['id_order'],
                true
            );
            Tools::redirectAdmin($this->context->link->getAdminLink('AdminOrders', true, [], [
                'vieworder' => '',
                'id_order' => $params['id_order'],
                'mondidopayments_error' => 'status',
            ]));
        }
    }

    private function isValidStatusChange(int $old, int $new)
    {
        $locked = [
            $this->config->transactionStatusRefunded(),
            $this->config->transactionStatusPending(),
            $this->config->transactionStatePending(),
        ];

        if (in_array($old, $locked)) {
            return false;
        }

        $invalid = [
            $this->config->transactionStatusPending(),
            $this->config->transactionStatusAuthorized(),
            $this->config->transactionStatusDeclined(),
            $this->config->transactionStatusFailed(),
            $this->config->transactionStatePending(),
        ];

        if (in_array($new, $invalid)) {
            return false;
        }

        if ($old === $this->config->transactionStatusAuthorized()) {
            $valid = [
                $this->config->transactionStatusApproved(),
                $this->config->transactionStatusRefunded(),
                $this->config->transactionStatusCanceled(),
            ];
            if (!in_array($new, $valid)) {
                return false;
            }
        }

        return true;
    }

    private function statusName($status) {
        if (is_array($status->name)) {
            $lang_id = $this->context->cookie->id_lang;
            if (array_key_exists($lang_id, $status->name)) {
                return $status->name[$lang_id];
            }
            $default_lang_id = $this->config->defaultLanguage();
            if (array_key_exists($lang_id, $status->name)) {
                return $status->name[$default_lang_id];
            }
            return reset($status->name);
        }

        return $status->name;
    }
}
