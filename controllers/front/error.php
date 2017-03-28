<?php

class mondidopayErrorModuleFrontController extends ModuleFrontController
{
    public $ssl = TRUE;

    /**
     * @see FrontController::initContent()
     */
    public function initContent()
    {
        parent::initContent();

        $message = Tools::getValue('error_name');
        if (empty($message)) {
            $message = isset($_SESSION['message']) ? $_SESSION['message'] : '';
        }

        @session_start();
        $this->context->smarty->assign([
            'message' => $message
        ]);

        $this->setTemplate('module:mondidopay/views/templates/front/error.tpl');

        unset($_SESSION['message']);
    }
}
