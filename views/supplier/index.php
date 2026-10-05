<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\bootstrap5\Html;
use yii\grid\GridView;

$this->title = 'Suppliers';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="supplier-index py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 fw-bold mb-0"><?= Html::encode($this->title) ?></h1>
        <?= Html::a('New supplier', ['create'], ['class' => 'btn btn-primary']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped align-middle'],
        'columns' => [
            'id',
            'name',
            'email:email',
            'phone',
            'created_at:datetime',
        ],
    ]) ?>
</div>
