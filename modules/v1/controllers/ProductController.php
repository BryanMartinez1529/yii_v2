<?php

declare(strict_types=1);

namespace app\modules\v1\controllers;

use app\models\Product;

class ProductController extends ApiController
{
    /**
     * {@inheritdoc}
     */
    public $modelClass = Product::class;
}
