<?php
///////////////////////////////////////////////////////////////////////////////
//
// Licensed Source Code - Property of ProjectKit.net
//
// © Copyright ProjectKit.net 2015. All Rights Reserved.
//
///////////////////////////////////////////////////////////////////////////////
namespace fproject\amqp;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * ActivityNoticeManager provides a set of methods for sending/receiving message using PhpAmqpLib
 *
 * @author Bui Sy Nguyen <nguyenbs@gmail.com>
 */
class ActivityNoticeManager {
    /** @var  array $params The parameters array contains configuration of AMQP and activity notice settings */
    public $params;

    /**
     * Gets the user name that dispatch the activity notice.
     * This abstract method should be overridden by subclasses
     * @return string the user name
     */
    public function getDispatcherName()
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
        $connection = new AMQPStreamConnection($setting['host'], $setting['port'], $setting['user'], $setting['password']);
        $channel = $connection->channel();

        $msg = new AMQPMessage(JsonHelper::encode($notice));

        $channel->basic_publish($msg, $setting['exchangeName'], $setting['routingKey']);

        $channel->close();
        $connection->close();

        return true;
    }

    /**
     * This method is invoked after saving a record successfully.
     * The default implementation does nothing.
     * You may override this method to do postprocessing after record saving.
     *
     * @param mixed $model The model
     * @param mixed $configType the instance of class that is configured in 'config/activityNotice.php'
     * @param string $action the action, the possible values: "add", "update", "batch"
     * @param mixed $attributeNames the attributes
     * @param array $modelList1 if $action is "batch", this will be the inserted models, if action is "delete" and
     * there's multiple deletion executed, this will be the deleted models
     * @param array $modelList2 if $action is "batch", this will be the updated models, if action is "delete", this parameter is ignored.
     */
    public function noticeAfterModelAction($model, $configType, $action, $attributeNames=null, $modelList1=null, $modelList2=null)
    {
        $classId = lcfirst(get_class($configType));

        $noticeAction = ($action === 'delete' && isset($modelList1)) ? 'batchDelete' : $action;

        $helper = new ActivityNoticeSerializeHelper($this->params);

        $config = $helper->getActivityNoticeConfig($classId, $noticeAction, $attributeNames);

        if(!isset($config))
            return;

        $notice = new ActivityNotice([
            'kind'=>$classId.'AUD',
            'oriTime'=>date(DATE_ISO8601, time()),
            'oriType'=>'user',
            'oriId'=>$this->getDispatcherName(),
            'contentUpdatedFields'=>$attributeNames
        ]);

        if($action==='batchSave')
        {
            if(count($modelList1) > 0)
            {
                $notice->action = 'add';
                $notice->content = $helper->getSerializeListData($modelList1, $config);
                $this->sendActivityNotice($notice);
            }
            if(count($modelList2) > 0)
            {
                $notice->action = 'update';
                $notice->content = $helper->getSerializeListData($modelList2, $config);
                $this->sendActivityNotice($notice);
            }
        }
        else
        {
            $notice->action = $action;
            if($action==='delete' && isset($modelList1))
                $notice->content = $helper->getSerializeListData($modelList1, $config);
            else
                $notice->content = $helper->getSerializeData($model, $config);
            $this->sendActivityNotice($notice);
        }
    }
}