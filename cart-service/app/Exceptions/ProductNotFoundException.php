<?php

namespace App\Exceptions;

use RuntimeException;

class ProductNotFoundException extends RuntimeException
{
    public function __construct(int $productId)
    {
        parent::__construct("product dengan id {$productId} tidak di temukan");
    }
}