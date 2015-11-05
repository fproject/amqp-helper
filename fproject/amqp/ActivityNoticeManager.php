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
namespace fproject\amqp;

use fproject\common\utils\JsonHelper;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * ActivityNoticeManager provides a set of methods for sending/receiving message using PhpAmqpLib
 *
 * @author Bui Sy Nguyen <nguyenbs@gmail.com>
 */
class ActivityNoticeManager {
    public $amqpClientLibrary = "PhpAmqpLib";//use "PECL" for RabbitMQ-C client extension

    /** @var  array $params The parameters array contains configuration of AMQP and activity notice settings */
    public $params;

    /**
     * Gets the user name that dispatch the activity notice.
     * This abstract method should be overridden by subclasses
     * @return string the user name
     */
    public function getDispatcher()
    {
        return null;
    }

    /**
     * Send an activity notice using AMQP
     * @param ActivityNotice $notice
     * @return bool
     */
    public function sendActivityNotice($notice)
    {
        if(!isset($notice))
            return false;
        /** @var array $setting */
        $setting = $this->params['amqpSetting'];

        try
        {
            if($this->amqpClientLibrary == "PhpAmqpLib")
            {
                $connection = new AMQPStreamConnection($setting['host'], $setting['port'], $setting['user'], $setting['password']);
                $channel = $connection->channel();

                $msg = new AMQPMessage(JsonHelper::encode($notice));

                $channel->basic_publish($msg, $setting['exchangeName'], $setting['routingKey']);

                $channel->close();
                $connection->close();
            }
            elseif($this->amqpClientLibrary == "PECL")
            {
                $connection = new \AMQPConnection([
                    'host' => $setting['host'],
                    'port' => $setting['port'],
                    'login' => $setting['user'],
                    'password' => $setting['password']
                ]);
                $connection->connect();
                if($connection->isConnected())
                {
                    $channel = new \AMQPChannel($connection);
                    $exchange = new \AMQPExchange($channel);
                    $exchange->setName($setting['exchangeName']);
                    $exchange->publish(JsonHelper::encode($notice), $setting['routingKey']);
                    $connection->disconnect();
                }
            }
            else
            {
                return false;
            }
        }
        catch (\Exception $e)
        {
            return false;
        }

        return true;
    }

    /**
     * Get the data serializer instance
     *
     * @return ActivityNoticeSerializer
     * */
    public function getSerializer()
    {
        return new ActivityNoticeSerializer($this->params);
    }

    /**
     * This method is invoked after saving a record successfully.
     * The default implementation does nothing.
     * You may override this method to do postprocessing after record saving.
     *
     * @param mixed $data The model or action's data
     * @param mixed $configType the class name or instance of class that is configured in 'config/activityNotice.php'
     * @param string $action the action, the possible values: "add", "update", "batch"
     * @param mixed $attributeNames the attributes
     * @param array $modelList1 if $action is "batchSave", this will be the inserted models, if action is "batchDelete" and
     * there's multiple deletion executed, this will be the deleted models
     * @param array $modelList2 if $action is "batchSave", this will be the updated models, if action is "batchDelete", this parameter is ignored.
     */
    public function noticeAfterModelAction($data, $configType, $action, $attributeNames=null, $modelList1=null, $modelList2=null)
    {
        if(is_object($configType))
            $classId = get_class($configType);
        else
            $classId = $configType;

        $noticeAction = ($action === 'delete' && is_array($modelList1)) ? 'batchDelete' : $action;

        $serializer = $this->getSerializer();

        $config = $serializer->getActivityNoticeConfig($classId, $noticeAction, $attributeNames);

        if(!isset($config))
            return;

        $a=explode('\\', $classId);
        $shortClassId = array_pop($a);
        $notice = new ActivityNotice([
            'kind'=>lcfirst($shortClassId).'AUD',
            'dispatchTime'=>date(DATE_ISO8601, time()),
            'dispatcher'=>$this->getDispatcher(),
            'contentUpdatedFields'=>$attributeNames
        ]);

        if($action==='batchSave')
        {
            if(count($modelList1) > 0)
            {
                $notice->action = 'add';
                $notice->content = $serializer->getSerializeListData($modelList1, $config);
                $this->sendActivityNotice($notice);
            }
            if(count($modelList2) > 0)
            {
                $notice->action = 'update';
                $notice->content = $serializer->getSerializeListData($modelList2, $config);
                $this->sendActivityNotice($notice);
            }
        }
        else
        {
            $notice->action = $noticeAction;
            if($noticeAction==='batchDelete')
            {
                if(isset($modelList1))
                    $notice->content = $serializer->getSerializeListData($modelList1, $config);
                else
                    $notice->content = $data;
            }
            else
                $notice->content = $serializer->getSerializeData($data, $config);
            $this->sendActivityNotice($notice);
        }
    }
}