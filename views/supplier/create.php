<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\Supplier $model */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'New supplier';
$this->params['breadcrumbs'][] = ['label' => 'Suppliers', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="supplier-create row justify-content-center py-4">
    <div class="col-md-8 col-lg-6">
        <h1 class="h3 fw-bold mb-4"><?= Html::encode($this->title) ?></h1>

        <?php $form = ActiveForm::begin(['id' => 'supplier-form']); ?>

        <?= $form->field($model, 'name')->textInput(['maxlength' => true, 'autofocus' => true]) ?>

        <div class="row">
            <div class="col-sm-6">
                <?= $form->field($model, 'email')->input('email', ['maxlength' => true]) ?>
            </div>
            <div class="col-sm-6">
                <?= $form->field($model, 'phone')->textInput(['maxlength' => true]) ?>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <?= Html::submitButton('Save', ['class' => 'btn btn-primary', 'name' => 'save-button']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
