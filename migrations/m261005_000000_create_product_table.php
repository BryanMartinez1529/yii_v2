<?php

declare(strict_types=1);

use yii\db\Migration;

class m261005_000000_create_product_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createTable('{{%product}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(120)->notNull(),
            'description' => $this->text(),
            'price' => $this->decimal(10, 2)->notNull(),
            'stock' => $this->integer()->notNull()->defaultValue(0),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $now = time();
        $this->batchInsert(
            '{{%product}}',
            ['name', 'description', 'price', 'stock', 'created_at', 'updated_at'],
            [
                ['Teclado mecánico', 'Switches rojos, layout español', 59.90, 25, $now, $now],
                ['Mouse inalámbrico', 'Sensor óptico de 1600 DPI', 19.50, 80, $now, $now],
                ['Monitor 24"', 'Panel IPS Full HD a 75 Hz', 149.00, 12, $now, $now],
            ],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        $this->dropTable('{{%product}}');
    }
}
