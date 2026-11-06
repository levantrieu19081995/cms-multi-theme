<?php

namespace App\Dto;

class OptionDto
{
    public string $key;

    public string $value;

    public string $defaultValue;

    public bool $autoload;

    public function __construct($key, $value, $defaultValue, $autoload = true)
    {
        $this->value = $value;
        $this->key = $key;
        $this->defaultValue = $defaultValue;
        $this->autoload = $autoload;
    }
}
