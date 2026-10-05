<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\models\Supplier;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class SupplierController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            'access' => [
                'class' => AccessControl::class,
                'only' => ['create'],
                'rules' => [
                    [
                        'actions' => ['create'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
        ];
    }

    /**
     * Lists all suppliers.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Supplier::find(),
            'pagination' => ['pageSize' => 10],
            'sort' => ['defaultOrder' => ['name' => SORT_ASC]],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    /**
     * Displays the supplier form and saves the supplier when it is submitted.
     *
     * @return Response|string
     */
    public function actionCreate(): Response|string
    {
        $model = new Supplier();

        if ($model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Supplier \"{$model->name}\" was created.");

            return $this->redirect(['index']);
        }

        return $this->render('create', ['model' => $model]);
    }
}
