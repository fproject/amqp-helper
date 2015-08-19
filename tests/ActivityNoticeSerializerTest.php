<?php
use fproject\amqp\ActivityNoticeSerializer;
include_once('TestModel01.php');
include_once('TestModel02.php');
class ActivityNoticeSerializerTest extends PHPUnit_Framework_TestCase
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

        $helper = new ActivityNoticeSerializer($this->params);
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
                            'notSerializeAttributes' => 'jsonData,model1,workCalendar,resources,projectTasks'
                        ]
                    ]
                ],
            ]
        ];

        $helper = new ActivityNoticeSerializer($this->params);
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
        $this->assertArrayHasKey('model1',$data);
        $this->assertNull($data['model1']);
        $this->assertArrayHasKey('id',$data);
        $this->assertArrayHasKey('endTime',$data);
        $this->assertInstanceOf('DateTime',$data['endTime']);
    }

    public function testGetSerializeData03()
    {
        $this->params = [
            'activityNotice' => []
        ];

        $helper = new ActivityNoticeSerializer($this->params);
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

    public function testGetSerializeData04()
    {
        $this->params = [
            'activityNotice' => [
                'testModel02' => [
                    'notifyActions' => [//Use '*' to indicate all actions will be applied
                        'delete' => ['serializeAttributes' => 'id'],
                        'add',//Use simple string value to indicate all attributes will be applied
                        'update' => [
                            //'serializeAttributes' => '*',
                            'serializeAttributes' => 'jsonData,model1.field1,workCalendar,resources,projectTasks'
                        ]
                    ]
                ],
            ]
        ];

        $helper = new ActivityNoticeSerializer($this->params);
        $model = new TestModel02();
        $model->model1 = new TestModel01();
        $model->model1->field1 = "ABC";
        $model->model1->field2 = "XYZ";

        $config = $helper->getActivityNoticeConfig('testModel02', 'update', null);

        $data = $helper->getSerializeData($model, $config);

        $this->assertArrayHasKey('model1',$data);
        $this->assertNotNull($data['model1']);
        $this->assertTrue(is_array($data['model1']));
        $this->assertEquals('ABC',$data['model1']['field1']);
        $this->assertArrayNotHasKey('field2',$data['model1']);
    }

    public function testGetSerializeData05()
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

        $helper = new ActivityNoticeSerializer($this->params);
        $model = [
            'field1'=>new DateTime(),
            'field2' => 'ABC',
            'field3' => 123,
            '_explicitType' => "TestModel01"
        ];

        $config = $helper->getActivityNoticeConfig('testModel01', 'add', null);
        $data = $helper->getSerializeData($model, $config);

        $this->assertArrayNotHasKey('_explicitType',$data);
        $this->assertArrayNotHasKey('field3',$data);
        $this->assertArrayHasKey('field2',$data);
        $this->assertArrayHasKey('field1',$data);
        $this->assertInstanceOf('DateTime',$data['field1']);
    }

    public function testGetSerializeData06()
    {
        $this->params = [
            'activityNotice' => [
                '*' => [
                    'notSerializeAttributes' => '_explicitType'
                ],
                'testModel01' => [
                    'notifyActions' => [
                        'add'=>[
                            'notSerializeAttributes'=>'field3'
                        ]
                    ]
                ],
            ]
        ];

        $helper = new ActivityNoticeSerializer($this->params);
        $model = [
            'field1'=>new DateTime(),
            'field2' => 'ABC',
            'field3' => 123,
            '_explicitType' => "TestModel01"
        ];

        $config = $helper->getActivityNoticeConfig('testModel01', 'add', null);
        $data = $helper->getSerializeData($model, $config);

        $this->assertArrayHasKey('_explicitType',$data);
        $this->assertNull($data['_explicitType']);
        $this->assertArrayHasKey('field3',$data);
        $this->assertNull($data['field3']);
        $this->assertArrayHasKey('field2',$data);
        $this->assertArrayHasKey('field1',$data);
        $this->assertInstanceOf('DateTime',$data['field1']);
    }
}
?>