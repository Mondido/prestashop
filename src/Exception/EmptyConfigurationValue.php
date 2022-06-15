<?php declare(strict_types = 1);

namespace MondidoPayments\Exception;

class EmptyConfigurationValue extends \Exception {

    public function __construct()
    {
        parent::__construct('Empty value', 400);
    }
}
