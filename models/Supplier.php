<?php

declare(strict_types=1);

namespace app\models;

use yii\behaviors\TimestampBehavior;
use yii\db\ActiveQuery;
use yii\db\ActiveRecord;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property int $created_at
 * @property int $updated_at
 *
 * @property-read Product[] $products
 */
class Supplier extends ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName(): string
    {
        return '{{%supplier}}';
    }

    /**
     * {@inheritdoc}
     */
    public function behaviors(): array
    {
        return [
            TimestampBehavior::class,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function rules(): array
    {
        return [
            ['name', 'required'],
            [['name', 'email', 'phone'], 'trim'],
            ['name', 'string', 'max' => 120],
            ['name', 'unique'],
            ['email', 'string', 'max' => 120],
            ['email', 'email'],
            ['phone', 'string', 'max' => 30],
        ];
    }

    /**
     * One supplier has many products.
     */
    public function getProducts(): ActiveQuery
    {
        return $this->hasMany(Product::class, ['supplier_id' => 'id']);
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
            'name',
            'email',
            'phone',
            'created_at' => fn (self $model): string => date(DATE_ATOM, $model->created_at),
            'updated_at' => fn (self $model): string => date(DATE_ATOM, $model->updated_at),
        ];
    }

    /**
     * Fields returned only when asked for with ?expand=products
     *
     * {@inheritdoc}
     */
    public function extraFields(): array
    {
        return ['products'];
    }
}
