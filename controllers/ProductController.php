<?php

declare(strict_types=1);

namespace app\controllers;

use Yii;
use app\models\Product;
use app\models\Supplier;
use yii\data\ActiveDataProvider;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\web\Response;

class ProductController extends Controller
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
     * Lists all products.
     *
     * @return string
     */
    public function actionIndex(): string
    {
        $dataProvider = new ActiveDataProvider([
            'query' => Product::find()->with('supplier'),
            'pagination' => ['pageSize' => 10],
            'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
        ]);

        return $this->render('index', ['dataProvider' => $dataProvider]);
    }

    /**
     * Displays the product form and saves the product when it is submitted.
     *
     * @return Response|string
     */
    public function actionCreate(): Response|string
    {
        $model = new Product();

        if ($model->load($this->request->post()) && $model->save()) {
            Yii::$app->session->setFlash('success', "Product \"{$model->name}\" was created.");

            return $this->redirect(['index']);
        }

        // [id => name] pairs for the supplier dropdown
        $suppliers = Supplier::find()->select(['name', 'id'])->orderBy('name')->indexBy('id')->column();

        return $this->render('create', ['model' => $model, 'suppliers' => $suppliers]);
    }
}
