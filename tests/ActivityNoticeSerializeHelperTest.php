<?php
use fproject\amqp\ActivityNoticeSerializeHelper;
include_once('TestModel01.php');
class ActivityNoticeSerializeHelperTest extends PHPUnit_Framework_TestCase
{
    private $params = [];

    public function testPushAndPop()
    {
        $this->params = [
            'activityNotice' => [
                '*' => [
                    'notSerializeAttributes' => '_explicitType'
                ],
                'testModel01' => [
                    'notifyActions' => '*',
                    'serializeAttributes' => 'field1,field2'
                ],
            ]
        ];

        $helper = new ActivityNoticeSerializeHelper($this->params);
        $model = new TestModel01();
        $model->field1 = new DateTime();
        $model->field2 = 'ABC';
        $model->field3 = 123;
        $config = $helper->getActivityNoticeConfig('testModel01', 'add', null);
        $data = $helper->getSerializeData($model, $config);
        $this->assertArrayNotHasKey('field3',$data);
        $this->assertArrayHasKey('field2',$data);
        $this->assertArrayHasKey('field1',$data);
        $this->assertInstanceOf('DateTime',$data['field1']);
    }
}
?>