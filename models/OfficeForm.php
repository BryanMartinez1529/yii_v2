<?php

namespace app\models;

use yii\base\Model;

class  OfficeForm extends Model
{
    public $name;
    public $email;
    public $message;
    public $andress;

    public function rules()
    {
        return [
            [['name', 'email', 'message'], 'required'],
            ['email', 'email'],
            ['andress', 'string', 'max' => 255],
        ];
    }
}


?>
