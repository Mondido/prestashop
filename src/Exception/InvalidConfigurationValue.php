<?php declare(strict_types = 1);

namespace MondidoPayments\Exception;

class InvalidConfigurationValue extends \Exception {

    public function __construct($value)
    {
        parent::__construct($value, 400);
    }
}
