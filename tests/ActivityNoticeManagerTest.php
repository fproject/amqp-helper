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
include_once('TestModel01.php');
include_once('TestModel02.php');
include_once('TestModel03.php');
class ActivityNoticeManagerTest extends PHPUnit_Framework_TestCase
{
    private $params = [];

    public function testNoticeAfterModelActionForDeletingByPK()
    {
        $anm = new \fproject\amqp\ActivityNoticeManager();
        $anm->params =  [
            'activityNotice' => [
                'TestModel03' => [
                    'notifyActions' => '*',
                    'notSerializeAttributes' => '_explicitType'
                ],
            ],
            'amqpSetting' =>
                [
                    'host' => 'so.projectkit.net',
                    'port' => '5672',
                    'user' => 'pkadmin',
                    'password' => 'pkrb@12345',
                    'exchangeName' => 'pk-main.notices',
                    'routingKey' => 'rk.activity-notices'
                ],
        ];

       /* $helper = new ActivityNoticeSerializer($this->params);
        $config = $helper->getActivityNoticeConfig('TestModel03', 'delete', null, '1001');*/

        $notice = $anm->noticeAfterModelAction(new TestModel03(), 'TestModel03', 'delete', null, '1001');

        $this->assertEquals('1001', $notice->content);
    }

    public function testNoticeAfterModelActionIncludeOldData()
    {
        $anm = new \fproject\amqp\ActivityNoticeManager();
        $anm->params =  [
            'activityNotice' => [
                'TestModel03' => [
                    'notifyActions' => [
                        'add,update,delete' => [
                            'serializeOldData' => true,
                        ]
                    ],
                    'notSerializeAttributes' => '_explicitType',
                ],
            ],
            'amqpSetting' =>
                [
                    'host' => 'so.projectkit.net',
                    'port' => '5672',
                    'user' => 'pkadmin',
                    'password' => 'pkrb@12345',
                    'exchangeName' => 'pk-main.notices',
                    'routingKey' => 'rk.activity-notices'
                ],
        ];

        /* $helper = new ActivityNoticeSerializer($this->params);
         $config = $helper->getActivityNoticeConfig('TestModel03', 'delete', null, '1001');*/

        $notice = $anm->noticeAfterModelAction(new TestModel03(), 'TestModel03', 'delete', null, '1001', null, '1000');

        $this->assertEquals('1001', $notice->content);
        $this->assertEquals('1000', $notice->oldContent);
    }

}
?>