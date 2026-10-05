<?php

declare(strict_types=1);

namespace app\modules\v1\controllers;

use yii\filters\auth\HttpBearerAuth;
use yii\rest\ActiveController;
use yii\rest\Serializer;

/**
 * Base class shared by every resource of the API.
 */
abstract class ApiController extends ActiveController
{
    /**
     * {@inheritdoc}
     */
    public $serializer = [
        'class' => Serializer::class,
        // wraps collections as {"items": [...], "_links": {...}, "_meta": {...}}
        'collectionEnvelope' => 'items',
    ];

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        $behaviors = parent::behaviors();

        // Reading is public; creating, updating and deleting need a token.
        $behaviors['authenticator'] = [
            'class' => HttpBearerAuth::class,
            'except' => ['index', 'view', 'options'],
        ];

        return $behaviors;
    }
}
