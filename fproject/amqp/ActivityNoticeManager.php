<?php
///////////////////////////////////////////////////////////////////////////////
//
// Licensed Source Code - Property of ProjectKit.net
//
// © Copyright ProjectKit.net 2015. All Rights Reserved.
//
///////////////////////////////////////////////////////////////////////////////

namespace fproject\amqp;
use PhpAmqpLib\Connection\AMQPConnection;
use PhpAmqpLib\Message\AMQPMessage;

/**
 * AMQPHelper provides a set of methods for sending/receiving message using PhpAmqpLib
 *
 * @author Bui Sy Nguyen <nguyenbs@gmail.com>
 */
class ActivityNoticeManager {
    /** @var  array the params contains config settings for AMQP */
    public $amqpConfig;

    /** @var  array the params contains config settings of activity notification for each model classes */
    public $activityNoticeConfig;

    /** @var string the user name that dispatch the activity notice */
    public $dispatcherName;

    /**
     * Send an activity notice using AMQP
     * @param ActivityNotice $notice
     * @return bool
     */
    public function sendActivityNotice($notice)
    {
        if(!isset($notice))
            return false;
        $connection = new AMQPConnection(
            $this->amqpConfig['host'],
            $this->amqpConfig['port'],
            $this->amqpConfig['user'],
            $this->amqpConfig['password']);

        $channel = $connection->channel();

        $msg = new AMQPMessage(json_encode($notice));

        $channel->basic_publish($msg, $this->amqpConfig['exchangeName'], $this->amqpConfig['routingKey']);

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

        $config = $this->getActivityNoticeConfig($classId, $noticeAction, $attributeNames);

        if(!isset($config))
            return;

        $notice = new ActivityNotice([
            'kind' => $classId.'AUD',
            'oriTime' => date(DATE_ISO8601, time()),
            'oriType' => 'user',
            'oriId' => $this->dispatcherName,
            'contentUpdatedFields' => $attributeNames
        ]);

        if($action==='batchSave')
        {
            if(count($modelList1) > 0)
            {
                $notice->action = 'add';
                $notice->content = $this->getSerializeListData($modelList1, $config);
                $this->sendActivityNotice($notice);
            }
            if(count($modelList2) > 0)
            {
                $notice->action = 'update';
                $notice->content = $this->getSerializeListData($modelList2, $config);
                $this->sendActivityNotice($notice);
            }
        }
        else
        {
            $notice->action = $action;
            if($action==='delete' && isset($modelList1))
                $notice->content = $this->getSerializeListData($modelList1, $config);
            else
                $notice->content = $this->getSerializeData($model, $config);
            $this->sendActivityNotice($notice);
        }
    }

    /**
     * Get configured serialize list data of the original list data.
     * @param $listData
     * @param $config
     * @return array
     */
    public function getSerializeListData($listData, $config)
    {
        $list = [];
        foreach($listData as $data)
        {
            $list[] = $this->getSerializeData($data, $config);
        }
        return $list;
    }

    /**
     * Get configured serialize data of the original data.
     * @param $data
     * @param $config
     * @return array
     */
    public function getSerializeData($data, $config)
    {
        if(!isset($config['serializeAttributes']))
            return $data;
        $serializeAttributes = $config['serializeAttributes'];
        $notSerializeAttributes = isset($config['notSerializeAttributes']) ? $config['notSerializeAttributes'] : [];
        $serializeData = [];
        if(isset($serializeAttributes))
        {
            foreach($serializeAttributes as $att)
            {
                if(array_key_exists($att, $data) && !array_search($att, $notSerializeAttributes))
                {
                    if(is_object($data))
                        $serializeData[$att] = $data->{$att};
                    else
                        $serializeData[$att] = $data[$att];
                }
                else
                    $serializeData[$att] = null;
            }
        }

        return $serializeData;
    }


    /**
     * Return null if activity is not configured for given parameters.
     * @param $classId
     * @param $action
     * @param $actionAttributes
     * @return array|null
     */
    public function getActivityNoticeConfig($classId, $action, $actionAttributes)
    {
        if(array_key_exists($classId, $this->activityNoticeConfig))
        {
            if(isset($this->activityNoticeConfig[$classId]['notifyActions']))
            {
                $actionCfgList= $this->activityNoticeConfig[$classId]['notifyActions'];
                if(is_string($actionCfgList))
                {
                    if($actionCfgList === '*')
                        return [];
                    $actionCfgList = explode(',', $actionCfgList);
                }
                if(array_key_exists($action, $actionCfgList))
                {
                    $actionConfig = $actionCfgList[$action];
                }
                else
                {
                    foreach($actionCfgList as $key=>$a)
                    {
                        if(!is_string($key) && $a === $action)
                            $actionConfig = [];
                    }
                }
            }
        }

        if(isset($actionConfig) && is_array($actionConfig))
        {
            if(count($actionConfig) == 0 || !isset($actionAttributes))
                return $this->getSerializeAttributes($actionConfig);

            if(array_key_exists('listenAttributes', $actionConfig))
            {
                $includeAttributes = $actionConfig['listenAttributes'];
                if($includeAttributes === '*')
                    return $this->getSerializeAttributes($actionConfig);
                if(is_string($includeAttributes))
                    $includeAttributes = explode(',', $includeAttributes);
            }
            if(array_key_exists('listenAttributes', $actionConfig))
            {
                $excludeAttributes = $actionConfig['notListenAttributes'];
                if(is_string($excludeAttributes))
                    $excludeAttributes = explode(',', $excludeAttributes);
            }

            if(is_string($actionAttributes))
            {
                $actionAttributes = explode(',', $actionAttributes);
            }

            if(isset($includeAttributes))
            {
                foreach($actionAttributes as $aAttr)
                {
                    foreach($includeAttributes as $iAttr)
                    {
                        if($iAttr === $aAttr)
                            return $this->getSerializeAttributes($actionConfig);
                    }
                }
            }
            elseif(isset($excludeAttributes))
            {
                foreach($actionAttributes as $aAttr)
                {
                    $found = false;
                    foreach($excludeAttributes as $iAttr)
                    {
                        if($iAttr === $aAttr)
                        {
                            $found = true;
                            break;
                        }
                    }

                    if(!$found)
                        return $this->getSerializeAttributes($actionConfig);
                }
            }
        }
        return null;
    }

    /**
     * @param $actionConfig
     * @return array
     */
    private function getSerializeAttributes($actionConfig)
    {
        $serializeAttributes = null;
        $notSerializeAttributes = null;
        if(array_key_exists('serializeAttributes', $actionConfig))
        {
            $serializeAttributes = $actionConfig['serializeAttributes'];
            if(is_string($serializeAttributes))
                $serializeAttributes =  $serializeAttributes !== '*' ? explode(',', $serializeAttributes) : null;
        }
        if(array_key_exists('notSerializeAttributes', $actionConfig))
        {
            $notSerializeAttributes = $actionConfig['notSerializeAttributes'];
            if(is_string($notSerializeAttributes))
                $notSerializeAttributes = explode(',', $notSerializeAttributes);
        }

        return [
            'serializeAttributes' => $serializeAttributes,
            'notSerializeAttributes'=> $notSerializeAttributes
        ];
    }
}