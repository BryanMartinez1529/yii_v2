<?php

declare(strict_types=1);

namespace app\modules\v1;

use Yii;
use yii\web\Response;

class Module extends \yii\base\Module
{
    /**
     * {@inheritdoc}
     */
    public $controllerNamespace = 'app\modules\v1\controllers';

    /**
     * {@inheritdoc}
     */
    public function init(): void
    {
        parent::init();

        // The API is stateless: no session, no redirect to a login page.
        Yii::$app->user->enableSession = false;
        Yii::$app->user->loginUrl = null;

        // Errors are returned as JSON instead of the HTML page of site/error.
        Yii::$app->response->format = Response::FORMAT_JSON;
        Yii::$app->errorHandler->errorAction = null;
    }
}
