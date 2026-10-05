<?php 
use yii\helpers\Html;
use yii\widgets\ActiveForm;
?>

<?php
if (isset($mensaje)) {
    echo "<div class='alert alert-success'>$mensaje</div>";
} 
?>


<h1>Holaa  desde YII</h1>

<?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'email')->textInput(['maxlength' => true]) ?>
    <?= $form->field($model, 'message')->textarea(['rows' => 6]) ?>
    <?= $form->field($model, 'andress')->textInput(['maxlength' => true]) ?>

    <div class="form-group">
        <?= Html::submitButton('Submit', ['class' => 'btn btn-primary']) ?>
    </div>
<?php ActiveForm::end(); ?>