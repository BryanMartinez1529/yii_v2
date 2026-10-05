<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property int $supplier_id
 * @property string $name
 * @property string|null $description
 * @property string $price
 * @property int $stock
 * @property int $created_at
 * @property int $updated_at
 *
 * @property-read Supplier|null $supplier
 */
class Product extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%product}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            // fills created_at and updated_at automatically
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            [['name', 'price', 'supplier_id'], 'required'],
            [['name', 'description'], 'trim'],
            ['name', 'string', 'max' => 120],
            ['description', 'string'],
            ['price', 'number', 'min' => 0],
            ['stock', 'integer', 'min' => 0],
            ['stock', 'default', 'value' => 0],
            ['supplier_id', 'integer'],
            // the supplier must exist in the supplier table
            ['supplier_id', 'exist', 'targetClass' => Supplier::class, 'targetAttribute' => 'id'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels(): array
    {
        return [
            'supplier_id' => 'Supplier',
        ];
    }

    /**
     * Each product belongs to one supplier.
     */
    public function getSupplier(): ActiveQuery
    {
        return $this->hasOne(Supplier::class, ['id' => 'supplier_id']);
    }

    /**
     * Fields exposed by the API.
     *
     * {@inheritdoc}
     */
    public function fields(): array
    {
        return [
            'id',
            'supplier_id',
            'name',
            'description',
            'price' => fn (self $model): float => (float) $model->price,
            'stock',
            'created_at' => fn (self $model): string => date(DATE_ATOM, $model->created_at),
            'updated_at' => fn (self $model): string => date(DATE_ATOM, $model->updated_at),
        ];
    }

    /**
     * Fields returned only when asked for with ?expand=supplier
     *
     * {@inheritdoc}
     */
    public function extraFields(): array
    {
        return ['supplier'];
    }
}
