<?php

return [
    'class' => \yii\db\Connection::class,
    // SQLite: the database is a single file, no server or credentials needed.
    'dsn' => 'sqlite:' . dirname(__DIR__) . '/runtime/api.db',
    'charset' => 'utf8',

    // To use MySQL instead, replace the dsn above with:
    //'dsn' => 'mysql:host=localhost;dbname=yii2basic',
    //'username' => 'root',
    //'password' => '',

    // Schema cache options (for production environment)
    //'enableSchemaCache' => true,
    //'schemaCacheDuration' => 60,
    //'schemaCache' => 'cache',
];
