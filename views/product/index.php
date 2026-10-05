<?php

/** @var yii\web\View $this */
/** @var yii\data\ActiveDataProvider $dataProvider */

use yii\bootstrap5\Html;
use yii\grid\GridView;

$this->title = 'Products';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="product-index py-4">
    <div class="d-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 fw-bold mb-0"><?= Html::encode($this->title) ?></h1>
        <?= Html::a('New product', ['create'], ['class' => 'btn btn-primary']) ?>
    </div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'tableOptions' => ['class' => 'table table-striped align-middle'],
        'columns' => [
            'id',
            'name',
            [
                'attribute' => 'supplier_id',
                'value' => 'supplier.name',
            ],
            'description:ntext',
            'price:decimal',
            'stock:integer',
            'created_at:datetime',
        ],
    ]) ?>
</div>
