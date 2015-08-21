<?php
use fproject\amqp\ActivityNoticeSerializer;
use fproject\amqp\JsonHelper;

include_once('TestModel01.php');
include_once('TestModel02.php');
class JsonHelperTest extends PHPUnit_Framework_TestCase
{
    private $params = [];

    public function testJsonEncodeActivityNotice()
    {
        $this->params = [
            'activityNotice' => [
                'TestModel02' => [
                    'notifyActions' => '*',
                    'serializeAttributes' => 'jsonData,model1.field1,model1.field3,workCalendar,resources,projectTasks'
                ],
            ]
        ];

        $helper = new ActivityNoticeSerializer($this->params);
        $model = new TestModel02();
        $model->model1 = new TestModel01();
        $model->model1->field1 = "ABC";
        $model->model1->field2 = "XYZ";
        $model->model1->field3 = "GHI";
        $model->projectTasks = ['abc','def'];

        $config = $helper->getActivityNoticeConfig('TestModel02', 'update', null);
        $data = $helper->getSerializeData($model, $config);

        $json = JsonHelper::encode($data);
        $obj = json_decode($json);
        $this->assertNull($obj->jsonData);
        $this->assertNull($obj->workCalendar);
        $this->assertNull($obj->resources);
        $this->assertTrue(is_array($obj->projectTasks));
        $this->assertEquals("abc",$obj->projectTasks[0]);
        $this->assertEquals("def",$obj->projectTasks[1]);
        $this->assertNotNull($obj->model1);
        $this->assertEquals("ABC",$obj->model1->field1);
        $this->assertObjectNotHasAttribute("field2",$obj->model1);
        $this->assertEquals("GHI",$obj->model1->field3);
    }
}
?>