<?php declare(strict_types = 1);

namespace MondidoPayments\Exception;

class InvalidPaymentMethod extends \Exception
{
    public function __construct($method)
    {
        parent::__construct("Invalid payment method: '$method'", 400);
    }
}
