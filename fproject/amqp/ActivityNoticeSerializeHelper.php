<?php
///////////////////////////////////////////////////////////////////////////////
//
// Licensed Source Code - Property of ProjectKit.net
//
// © Copyright ProjectKit.net 2015. All Rights Reserved.
//
///////////////////////////////////////////////////////////////////////////////
namespace fproject\amqp;
use ReflectionClass;
use ReflectionProperty;

/**
 * ActivityNoticeSerializeHelper provides a set of methods for serializing data before sending to message queue
 *
 * @author Bui Sy Nguyen <nguyenbs@gmail.com>
 */
class ActivityNoticeSerializeHelper {
    private static $instance;

    public function __construct($params)
    {
        $this->_params = $params;
    }

    /**
     * Singleton method
     * @param null $params
     * @return ActivityNoticeSerializeHelper
     */
    public static function getInstance($params=null)
    {
        if(!isset(self::$instance))
        {
            self::$instance = new ActivityNoticeSerializeHelper($params);
        }
        return self::$instance;
    }

    private $_params;

    /**
     * Return null if activity is not configured for given parameters.
     * @param $classId
     * @param $action
     * @param $actionAttributes
     * @return array|null
     */
    public function getActivityNoticeConfig($classId, $action, $actionAttributes)
    {
        /** @var array $config */
        $config = $this->_params['activityNotice'];
        if(array_key_exists($classId, $config))
            $actionConfig = $this->getActionConfig($config[$classId], $action);
        else
            $actionConfig = null;

        if($actionConfig !== null)
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

    private function getActionConfig($config,$action)
    {
        $actionConfig = null;
        if(isset($config['notifyActions']))
        {
            $actionCfgList = $config['notifyActions'];
            if(is_string($actionCfgList))
            {
                if($actionCfgList === '*')
                    $actionCfgList = [$action];
                else
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
                    {
                        $actionConfig = [];
                        break;
                    }
                }
            }

            if($actionConfig !== null && empty($actionConfig))
            {
                if(isset($config['serializeAttributes']))
                    $actionConfig['serializeAttributes'] = $config['serializeAttributes'];
                if(isset($config['notSerializeAttributes']))
                    $actionConfig['notSerializeAttributes'] = $config['notSerializeAttributes'];
                if(isset($config['listenAttributes']))
                    $actionConfig['listenAttributes'] = $config['listenAttributes'];
                if(isset($config['notListenAttributes']))
                    $actionConfig['notListenAttributes'] = $config['notListenAttributes'];
            }
        }

        return $actionConfig;
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
        if(isset($config['serializeAttributes']))
            $serializeAttributes = $config['serializeAttributes'];
        if(isset($config['notSerializeAttributes']))
            $notSerializeAttributes = $config['notSerializeAttributes'];

        if(!isset($serializeAttributes) && !isset($notSerializeAttributes))
        {
            $serializeData = $data;
        }
        elseif(isset($serializeAttributes))
        {
            $serializeData = [];
            foreach($serializeAttributes as $att)
            {
                if((is_object($data) && property_exists($data, $att)) || (is_array($data) && array_key_exists($att, $data)))
                {
                    if(!isset($notSerializeAttributes) || !array_search($att, $notSerializeAttributes))
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
        }
        else
        {
            $serializeData = [];
            if(is_object($data))
            {
                $reflection = new ReflectionClass($data);
                $public = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);
                $static = $reflection->getProperties(ReflectionProperty::IS_STATIC);
                $properties = array_diff($public, $static);
                /** @var ReflectionProperty $prop */
                foreach($properties as $prop)
                {
                    $att = $prop->name;
                    if(!array_search($att, $notSerializeAttributes))
                        $serializeData[$att] = $data->{$att};
                    else
                        $serializeData[$att] = null;
                }
            }
            elseif(is_array($data))
            {
                foreach($data as $att=>$value)
                {
                    if(!array_search($att, $notSerializeAttributes))
                        $serializeData[$att] = $value;
                    else
                        $serializeData[$att] = null;
                }
            }
        }

        return $serializeData;
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

        $sa = [];
        if($serializeAttributes !== null)
            $sa['serializeAttributes'] = $serializeAttributes;
        if($notSerializeAttributes !== null)
            $sa['notSerializeAttributes'] = $notSerializeAttributes;
        return $sa;
    }
}