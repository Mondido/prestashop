<?php declare(strict_types = 1);

namespace MondidoPayments\Exception;

class InvalidPaymentView extends \Exception
{
    public function __construct($view)
    {
        parent::__construct("Invalid payment view: '$view'", 400);
    }
}
