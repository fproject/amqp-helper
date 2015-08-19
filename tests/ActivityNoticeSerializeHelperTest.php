<?php
use fproject\amqp\ActivityNoticeSerializeHelper;
include_once('TestModel01.php');
include_once('TestModel02.php');
class ActivityNoticeSerializeHelperTest extends PHPUnit_Framework_TestCase
{
    private $params = [];

    public function testGetSerializeData01()
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
        $model->_explicitType = "TestModel01";

        $config = $helper->getActivityNoticeConfig('testModel01', 'add', null);
        $data = $helper->getSerializeData($model, $config);

        $this->assertArrayNotHasKey('_explicitType',$data);
        $this->assertArrayNotHasKey('field3',$data);
        $this->assertArrayHasKey('field2',$data);
        $this->assertArrayHasKey('field1',$data);
        $this->assertInstanceOf('DateTime',$data['field1']);
    }

    public function testGetSerializeData02()
    {
        $this->params = [
            'activityNotice' => [
                '*' => [
                    'notSerializeAttributes' => '_explicitType'
                ],
                'testModel02' => [
                    'notifyActions' => [//Use '*' to indicate all actions will be applied
                        'delete' => ['serializeAttributes' => 'id'],
                        'add',//Use simple string value to indicate all attributes will be applied
                        'update' => [
                            //'listenAttributes' => '*',
                            'notListenAttributes' => 'jsonData',
                            //'serializeAttributes' => '*',
                            'notSerializeAttributes' => 'jsonData,group,workCalendar,resources,projectTasks'
                        ]
                    ]
                ],
            ]
        ];

        $helper = new ActivityNoticeSerializeHelper($this->params);
        $model = new TestModel02();
        $model->id = '001';
        $model->endTime = new DateTime();
        $model->jsonData = '{}';
        $model->name = 'Name 001';
        $model->_explicitType = "TestModel02";

        $config = $helper->getActivityNoticeConfig('testModel02', 'update', null);

        $data = $helper->getSerializeData($model, $config);

        $this->assertArrayHasKey('jsonData',$data);
        $this->assertNull($data['jsonData']);
        $this->assertArrayHasKey('_explicitType',$data);
        $this->assertNull($data['_explicitType']);
        $this->assertArrayHasKey('group',$data);
        $this->assertNull($data['group']);
        $this->assertArrayHasKey('id',$data);
        $this->assertArrayHasKey('endTime',$data);
        $this->assertInstanceOf('DateTime',$data['endTime']);
    }

    public function testGetSerializeData03()
    {
        $this->params = [
            'activityNotice' => []
        ];

        $helper = new ActivityNoticeSerializeHelper($this->params);
        $model = new TestModel02();
        $model->id = '001';
        $model->endTime = new DateTime();
        $model->jsonData = '{}';
        $model->name = 'Name 001';
        $model->_explicitType = "TestModel02";
        $config = $helper->getActivityNoticeConfig('testModel02', 'update', null);
        $data = $helper->getSerializeData($model, $config);
        $this->assertEmpty($data);
    }
}
?>