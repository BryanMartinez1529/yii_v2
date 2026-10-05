<?php

declare(strict_types=1);

namespace app\modules\v1\controllers;

use app\models\Supplier;

class SupplierController extends ApiController
{
    /**
     * {@inheritdoc}
     */
    public $modelClass = Supplier::class;

    /**
     * {@inheritdoc}
     */
    public function actions(): array
    {
        $actions = parent::actions();

        // suppliers cannot be deleted: their products would be left without one
        unset($actions['delete']);

        return $actions;
    }
}
