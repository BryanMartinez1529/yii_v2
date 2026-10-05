<?php

/** @var yii\web\View $this */
/** @var yii\bootstrap5\ActiveForm $form */
/** @var app\models\Product $model */
/** @var array<int, string> $suppliers */

use yii\bootstrap5\ActiveForm;
use yii\bootstrap5\Html;

$this->title = 'New product';
$this->params['breadcrumbs'][] = ['label' => 'Products', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="product-create row justify-content-center py-4">
    <div class="col-md-8 col-lg-6">
        <h1 class="h3 fw-bold mb-4"><?= Html::encode($this->title) ?></h1>

        <?php $form = ActiveForm::begin(['id' => 'product-form']); ?>

        <?= $form->field($model, 'supplier_id')
            ->dropDownList($suppliers, ['prompt' => 'Select a supplier...'])
            ->hint(Html::a('Create a new supplier', ['/supplier/create']), ['encode' => false]) ?>

        <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

        <?= $form->field($model, 'description')->textarea(['rows' => 4]) ?>

        <div class="row">
            <div class="col-sm-6">
                <?= $form->field($model, 'price')->input('number', ['min' => 0, 'step' => '0.01']) ?>
            </div>
            <div class="col-sm-6">
                <?= $form->field($model, 'stock')->input('number', ['min' => 0, 'step' => 1]) ?>
            </div>
        </div>

        <div class="d-flex gap-2 mt-3">
            <?= Html::submitButton('Save', ['class' => 'btn btn-primary', 'name' => 'save-button']) ?>
            <?= Html::a('Cancel', ['index'], ['class' => 'btn btn-outline-secondary']) ?>
        </div>

        <?php ActiveForm::end(); ?>
    </div>
</div>
