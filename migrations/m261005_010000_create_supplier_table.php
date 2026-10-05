<?php

declare(strict_types=1);

use yii\db\Migration;

class m261005_010000_create_supplier_table extends Migration
{
    /**
     * {@inheritdoc}
     */
    public function safeUp(): void
    {
        $this->createTable('{{%supplier}}', [
            'id' => $this->primaryKey(),
            'name' => $this->string(120)->notNull()->unique(),
            'email' => $this->string(120),
            'phone' => $this->string(30),
            'created_at' => $this->integer()->notNull(),
            'updated_at' => $this->integer()->notNull(),
        ]);

        $now = time();
        $this->batchInsert(
            '{{%supplier}}',
            ['name', 'email', 'phone', 'created_at', 'updated_at'],
            [
                ['Periféricos del Sur', 'ventas@perifericosdelsur.example', '+593 2 555 0101', $now, $now],
                ['Pantallas Andinas', 'contacto@pantallasandinas.example', '+593 4 555 0202', $now, $now],
            ],
        );

        $this->addColumn('{{%product}}', 'supplier_id', $this->integer());
        $this->createIndex('idx-product-supplier_id', '{{%product}}', 'supplier_id');

        // products that already exist get the first supplier, so none is left without one
        $this->update('{{%product}}', ['supplier_id' => 1]);

        // SQLite cannot add a foreign key to an existing table
        if ($this->db->driverName !== 'sqlite') {
            $this->addForeignKey(
                'fk-product-supplier_id',
                '{{%product}}',
                'supplier_id',
                '{{%supplier}}',
                'id',
                'RESTRICT',
                'CASCADE',
            );
        }
    }

    /**
     * {@inheritdoc}
     */
    public function safeDown(): void
    {
        if ($this->db->driverName !== 'sqlite') {
            $this->dropForeignKey('fk-product-supplier_id', '{{%product}}');
        }

        $this->dropIndex('idx-product-supplier_id', '{{%product}}');
        // plain SQL because Yii's dropColumn() does not support SQLite
        $this->execute('ALTER TABLE {{%product}} DROP COLUMN [[supplier_id]]');
        $this->dropTable('{{%supplier}}');
    }
}
