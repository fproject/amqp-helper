<?php
///////////////////////////////////////////////////////////////////////////////
//
// � Copyright f-project.net 2010-present.
//
// Licensed under the Apache License, Version 2.0 (the "License");
// you may not use this file except in compliance with the License.
// You may obtain a copy of the License at
//
//     http://www.apache.org/licenses/LICENSE-2.0
//
// Unless required by applicable law or agreed to in writing, software
// distributed under the License is distributed on an "AS IS" BASIS,
// WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
// See the License for the specific language governing permissions and
// limitations under the License.
//
///////////////////////////////////////////////////////////////////////////////
use fproject\amqp\ActivityNoticeSerializer;
include_once('TestModel01.php');
include_once('TestModel02.php');
include_once('TestModel03.php');
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
                'TestModel01' => [
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

        $config = $helper->getActivityNoticeConfig('TestModel01', 'add', null);
        $data = $helper->getSerializeData($model, $config);

        $this->assertArrayNotHasKey('_explicitType',$data);
        $this->assertArrayNotHasKey('field3',$data);
        $this->assertArrayHasKey('field2',$data);
        $this->assertArrayHasKey('field1',$data);
        $this->assertEquals($model->field1->format(\fproject\common\utils\DateTimeHelper::DATE_ISO8601_UTC),$data['field1']);
    }

    public function testGetSerializeData02()
    {
        $this->params = [
            'activityNotice' => [
                '*' => [
                    'notSerializeAttributes' => '_explicitType'
                ],
                'TestModel02' => [
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

        $config = $helper->getActivityNoticeConfig('TestModel02', 'update', null);

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
        $config = $helper->getActivityNoticeConfig('TestModel02', 'update', null);
        $data = $helper->getSerializeData($model, $config);
        $this->assertEmpty($data);
    }

    public function testGetSerializeData04()
    {
        $this->params = [
            'activityNotice' => [
                'TestModel02' => [
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

        $config = $helper->getActivityNoticeConfig('TestModel02', 'update', null);

        $data = $helper->getSerializeData($model, $config);

        $this->assertArrayHasKey('model1',$data);
        $this->assertNotNull($data['model1']);
        $this->assertTrue(is_array($data['model1']));
        $this->assertArrayHasKey('field1',$data['model1']);
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
                'TestModel01' => [
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

        $config = $helper->getActivityNoticeConfig('TestModel01', 'add', null);
        $data = $helper->getSerializeData($model, $config);

        $this->assertArrayNotHasKey('_explicitType',$data);
        $this->assertArrayNotHasKey('field3',$data);
        $this->assertArrayHasKey('field2',$data);
        $this->assertArrayHasKey('field1',$data);
        /** @var DateTime $tmpDate */
        $tmpDate = $model['field1'];
        $this->assertEquals($tmpDate->format(\fproject\common\utils\DateTimeHelper::DATE_ISO8601_UTC),$data['field1']);
    }

    public function testGetSerializeData06()
    {
        $this->params = [
            'activityNotice' => [
                '*' => [
                    'notSerializeAttributes' => '_explicitType'
                ],
                'TestModel01' => [
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

        $config = $helper->getActivityNoticeConfig('TestModel01', 'add', null);
        $data = $helper->getSerializeData($model, $config);

        $this->assertArrayHasKey('_explicitType',$data);
        $this->assertNull($data['_explicitType']);
        $this->assertArrayHasKey('field3',$data);
        $this->assertNull($data['field3']);
        $this->assertArrayHasKey('field2',$data);
        $this->assertArrayHasKey('field1',$data);
        $this->assertInstanceOf('DateTime',$data['field1']);
    }

    public function testGetSerializeData07()
    {
        $this->params = [
            'activityNotice' => [
                'TestModel02' => [
                    'notifyActions' => '*',
                    'serializeAttributes' => 'code,jsonData,model1.field1,model1.field3,workCalendar,resources,projectTasks'
                ],
            ]
        ];

        $helper = new ActivityNoticeSerializer($this->params);
        $model = new TestModel02();
        $model->model1 = new TestModel01();
        $model->model1->field1 = "ABC";
        $model->model1->field2 = "XYZ";
        $model->model1->field3 = "GHI";
        $model->code = null;

        $config = $helper->getActivityNoticeConfig('TestModel02', 'update', null);

        $data = $helper->getSerializeData($model, $config);

        $this->assertArrayHasKey('model1',$data);
        $this->assertNotNull($data['model1']);
        $this->assertTrue(is_array($data['model1']));
        $this->assertArrayHasKey('field1',$data['model1']);
        $this->assertEquals('ABC',$data['model1']['field1']);
        $this->assertArrayNotHasKey('field2',$data['model1']);
        $this->assertArrayHasKey('field3',$data['model1']);
        $this->assertEquals('GHI',$data['model1']['field3']);
    }

    public function testGetActivityNoticeConfig01()
    {
        $this->params = [
            'activityNotice' => [
                '*' => [
                    'notSerializeAttributes' => '_explicitType'
                ],
                'TestModel01' => [
                    'notifyActions' => [
                        'add,update,batchSave'=>[
                            'notSerializeAttributes'=>'field3,field2'
                        ]
                    ]
                ],
            ]
        ];

        $helper = new ActivityNoticeSerializer($this->params);

        $actions = ['add','update','batchSave'];
        foreach($actions as $action)
        {
            $config = $helper->getActivityNoticeConfig('TestModel01', $action, null);

            $this->assertNotEmpty($config);

            $this->assertArrayHasKey('notSerializeAttributes',$config);
            $this->assertTrue(is_array($config['notSerializeAttributes']));
            $this->assertCount(3, $config['notSerializeAttributes']);
            $this->assertEquals('field3',$config['notSerializeAttributes'][0]);
            $this->assertEquals('field2',$config['notSerializeAttributes'][1]);
            $this->assertEquals('_explicitType',$config['notSerializeAttributes'][2]);
        }
    }

    public function testGetSerializeData08()
    {
        $this->params = [
            'activityNotice' => [
                'TestModel03' => [
                    'notifyActions' => '*',
                    'notSerializeAttributes' => '_explicitType'
                ],
            ]
        ];

        $helper = new ActivityNoticeSerializer($this->params);
        $model = new TestModel03();
        $model->field1 = "ABC";
        $model->field2 = "XYZ";
        $model->field3 = "GHI";
        $model->myField1 = "My1";
        $model->myField2 = "My2";
        $model->_explicitType = "TestModel03";

        $config = $helper->getActivityNoticeConfig('TestModel03', 'save', null);

        $data = $helper->getSerializeData($model, $config);

        $this->assertArrayHasKey('field1',$data);
        $this->assertEquals('ABC',$data['field1']);
        $this->assertArrayHasKey('field2',$data);
        $this->assertEquals('XYZ',$data['field2']);
        $this->assertArrayHasKey('field3',$data);
        $this->assertEquals('GHI',$data['field3']);

        $this->assertArrayHasKey('myField1',$data);
        $this->assertEquals('My1',$data['myField1']);
        $this->assertArrayHasKey('myField2',$data);
        $this->assertEquals('My2',$data['myField2']);

        $this->assertNull($data['_explicitType']);
    }

    public function testGetSerializeData09_serializeDelegateFunction1()
    {
        $this->params = [
            'activityNotice' => [
                'TestModel03' => [
                    'notifyActions' => '*',
                    'notSerializeAttributes' => '_explicitType',
                    'serializeDelegateFunction' => function($data, $config)
                        {
                            $data->field1 = "@@@";
                            return $data;
                        }
                ],
            ]
        ];

        $helper = new ActivityNoticeSerializer($this->params);
        $model = new TestModel03();
        $model->field1 = "ABC";
        $model->field2 = "XYZ";
        $model->field3 = "GHI";
        $model->myField1 = "My1";
        $model->myField2 = "My2";
        $model->_explicitType = "TestModel03";

        $config = $helper->getActivityNoticeConfig('TestModel03', 'save', null);

        $data = $helper->getSerializeData($model, $config);

        $this->assertArrayHasKey('field1',$data);
        $this->assertEquals('@@@',$data['field1']);
        $this->assertArrayHasKey('field2',$data);
        $this->assertEquals('XYZ',$data['field2']);
        $this->assertArrayHasKey('field3',$data);
        $this->assertEquals('GHI',$data['field3']);

        $this->assertArrayHasKey('myField1',$data);
        $this->assertEquals('My1',$data['myField1']);
        $this->assertArrayHasKey('myField2',$data);
        $this->assertEquals('My2',$data['myField2']);

        $this->assertArrayHasKey('_explicitType',$data);
        $this->assertEquals('TestModel03',$data['_explicitType']);
    }

    public function testGetSerializeData10_serializeDelegateFunction2()
    {
        $this->params = [
            'activityNotice' => [
                'TestModel03' => [
                    'notifyActions' => '*',
                    'notSerializeAttributes' => '_explicitType',
                    'serializeDelegateFunction' => 'mySerializeDelegateFunction'
                ],
            ]
        ];

        $helper = new ActivityNoticeSerializer($this->params);
        $model = new TestModel03();
        $model->field1 = "ABC";
        $model->field2 = "XYZ";
        $model->field3 = "GHI";
        $model->myField1 = "My1";
        $model->myField2 = "My2";
        $model->_explicitType = "TestModel03";

        $config = $helper->getActivityNoticeConfig('TestModel03', 'save', null);

        $data = $helper->getSerializeData($model, $config);

        $this->assertArrayHasKey('field1',$data);
        $this->assertEquals('@@@',$data['field1']);
        $this->assertArrayHasKey('field2',$data);
        $this->assertEquals('XYZ',$data['field2']);
        $this->assertArrayHasKey('field3',$data);
        $this->assertEquals('GHI',$data['field3']);

        $this->assertArrayHasKey('myField1',$data);
        $this->assertEquals('My1',$data['myField1']);
        $this->assertArrayHasKey('myField2',$data);
        $this->assertEquals('My2',$data['myField2']);

        $this->assertArrayHasKey('_explicitType',$data);
        $this->assertEquals('TestModel03',$data['_explicitType']);
    }

    public function testGetSerializeData11()
    {
        $this->params = [
            'activityNotice' => [
                '*' => [
                    'notSerializeAttributes' => '_explicitType'
                ],
                'TestModel02' => [
                    'notifyActions' => [//Use '*' to indicate all actions will be applied
                        'delete' => ['serializeAttributes' => 'id'],
                        'add',//Use simple string value to indicate all attributes will be applied
                        'update' => [
                            //'listenAttributes' => '*',
                            'notListenAttributes' => 'jsonData',
                            //'serializeAttributes' => '*',
                            'notSerializeAttributes' => 'jsonData,model1,workCalendar,resources,projectTasks',
                            'serializeOldData' => true
                        ]
                    ]
                ],
            ]
        ];

        $helper = new ActivityNoticeSerializer($this->params);
        $model = new TestModel02();
        $model->id = '002';
        $model->endTime = new DateTime();
        $model->jsonData = '{}';
        $model->name = 'Name 002';
        $model->_explicitType = "TestModel02";

        $config = $helper->getActivityNoticeConfig('TestModel02', 'update', null);

        $this->assertArrayHasKey('serializeOldData', $config);
        $this->assertTrue($config['serializeOldData']);

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

    public function testGetActivityNoticeConfig12()
    {
        $this->params = [
            'activityNotice' => [
                '*' => [
                    'notSerializeAttributes' => '_explicitType'
                ],
                'TestModel01' => [
                    'notifyActions' => [
                        'add,update,batchSave'=>[
                            'notSerializeAttributes'=>'field3,field2',
                            'serializeOldData' => true,
                        ]
                    ]
                ],
            ]
        ];

        $helper = new ActivityNoticeSerializer($this->params);

        $actions = ['add','update','batchSave'];
        foreach($actions as $action)
        {
            $config = $helper->getActivityNoticeConfig('TestModel01', $action, null);

            $this->assertNotEmpty($config);

            $this->assertArrayHasKey('serializeOldData', $config);
            $this->assertTrue($config['serializeOldData']);

            $this->assertArrayHasKey('notSerializeAttributes',$config);
            $this->assertTrue(is_array($config['notSerializeAttributes']));
            $this->assertCount(3, $config['notSerializeAttributes']);
            $this->assertEquals('field3',$config['notSerializeAttributes'][0]);
            $this->assertEquals('field2',$config['notSerializeAttributes'][1]);
            $this->assertEquals('_explicitType',$config['notSerializeAttributes'][2]);
        }
    }
}
?>