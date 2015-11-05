<?php
///////////////////////////////////////////////////////////////////////////////
//
// © Copyright f-project.net 2010-present.
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
use fproject\common\utils\JsonHelper;
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