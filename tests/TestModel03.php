<?php
class TestModel03 extends TestModel01
{
    public $myField1;
    public $myField2;

    public function mySerializeDelegateFunction($config)
    {
        $this->field1 = "@@@";
        return $this;
    }
}