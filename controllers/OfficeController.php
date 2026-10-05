<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\filters\AccessControl;

use app\models\OfficeForm;



class  OfficeController  extends  Controller
{
    public function actionIndex()
    {
        $model = new OfficeForm();

        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            // Process the form data
            $valorRespuesta = ("El  resultado  es: " . $model->name . ", " . $model->email . ", " . $model->message . ", " . $model->andress);

            return  $this->render('index', [
                'model' => $model,
                'mensaje' => $valorRespuesta,
            ]);

        }



        return $this->render('index', ['mensaje'=> "" , 'model' => $model]);
    }
}



?>
