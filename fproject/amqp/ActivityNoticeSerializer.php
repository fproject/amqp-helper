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
use Exception;

/**
 * ActivityNoticeSerializeHelper provides a set of methods for serializing data before sending to message queue
 *
 * @author Bui Sy Nguyen <nguyenbs@gmail.com>
 */
class ActivityNoticeSerializer {
   private $_configCache;

    public function __construct($params)
    {
        $this->_params = $params;
        $this->_configCache = [];
    }

    private static $instance;

    /**
     * Singleton method
     * @param null $params
     * @return ActivityNoticeSerializer
     */
    public static function getInstance($params=null)
    {
        if(!isset(self::$instance))
        {
            self::$instance = new ActivityNoticeSerializer($params);
        }
        return self::$instance;
    }

    private $_params;

    /**
     * Return null if activity is not configured for given parameters.
     * @param string $classId
     * @param string $action
     * @param array $actionAttributes
     * @return array|null
     * @throws Exception
     */
    public function getActivityNoticeConfig($classId, $action, $actionAttributes)
    {
        $cacheKey = $classId.'.'.$action.'.'.(is_array($actionAttributes) ? implode(',',$actionAttributes) : '');
        if(array_key_exists($cacheKey, $this->_configCache))
            return $this->_configCache[$cacheKey];

        if(empty($this->_params) || !isset($this->_params['activityNotice']))
            throw new Exception('Invalid activityNotice configuration.');

        /** @var array $config */
        $config = $this->_params['activityNotice'];
        $actionConfig = $this->getActionConfig($classId,$config, $action);

        $attributeConfig = $this->getAttributeConfig($actionConfig, $actionAttributes);
        if(isset($attributeConfig))
            $this->_configCache[$cacheKey] = $attributeConfig;

        return $attributeConfig;
    }

    private function getAttributeConfig($actionConfig, $actionAttributes)
    {
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

    private function getActionConfig($classId,$configRoot,$action)
    {
        if(array_key_exists($classId, $configRoot))
            $config = $configRoot[$classId];
        else
            return null;

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
                    elseif(is_string($key))
                    {
                        $keys = explode(',',$key);
                        if(count($keys) > 1)
                        {
                            foreach($keys as $k)
                            {
                                if($k === $action)
                                {
                                    $actionConfig = $actionCfgList[$key];
                                    break;
                                }
                            }
                            if($actionConfig !== null)
                                break;
                        }
                    }
                }
            }

            $actionConfig = $this->mergeConfig($config, 'serializeAttributes', $actionConfig);
            $actionConfig = $this->mergeConfig($config, 'notSerializeAttributes', $actionConfig);
            $actionConfig = $this->mergeConfig($config, 'listenAttributes', $actionConfig);
            $actionConfig = $this->mergeConfig($config, 'notListenAttributes', $actionConfig);
        }

        if(isset($configRoot['*']))
        {
            $globalConfig = $configRoot['*'];
            $actionConfig = $this->mergeConfig($globalConfig, 'serializeAttributes', $actionConfig);
            $actionConfig = $this->mergeConfig($globalConfig, 'notSerializeAttributes', $actionConfig);
            $actionConfig = $this->mergeConfig($globalConfig, 'listenAttributes', $actionConfig);
            $actionConfig = $this->mergeConfig($globalConfig, 'notListenAttributes', $actionConfig);
        }

        return $actionConfig;
    }

    private function mergeConfig($globalConfig, $attSet, $cfg)
    {
        if(isset($globalConfig[$attSet]))
        {
            $src = $globalConfig[$attSet];
            if(is_string($src))
            {
                if($src === '*')
                {
                    $cfg[$attSet] = '*';
                    return $cfg;
                }
                $src = explode(',', $src);
            }

            if(!isset($cfg))
                $cfg = [$attSet=>[]];
            elseif(!isset($cfg[$attSet]))
                $cfg[$attSet] = [];
            elseif($cfg[$attSet] === '*')
                return $cfg;
            elseif(is_string($cfg[$attSet]))
                $cfg[$attSet] = explode(',', $cfg[$attSet]);
            foreach($src as $value)
            {
                if(!in_array($value, $cfg[$attSet]))
                    $cfg[$attSet][] = $value;
            }
        }
        return $cfg;
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
        if(is_null($config) || !is_array($config))
            return null;

        if(isset($config['serializeAttributes']))
            $serializeAttributes = $config['serializeAttributes'];
        if(isset($config['notSerializeAttributes']))
            $notSerializeAttributes = $config['notSerializeAttributes'];

        if(!isset($serializeAttributes) && !isset($notSerializeAttributes))
        {
            $serializeData = $data;
        }
        else
        {
            $serializeData = [];
            if(isset($serializeAttributes))
            {
                foreach($serializeAttributes as $attChain)
                {
                    $attChain = explode('.', $attChain);
                    if(!isset($notSerializeAttributes) || !in_array($attChain[0], $notSerializeAttributes))
                    {
                        $cnt = count($attChain);
                        $dt = $data;
                        $sd = &$serializeData;
                        $i = 0;
                        while($i < $cnt-1)
                        {
                            $att = $attChain[$i];
                            if(is_object($dt) && property_exists($dt, $att))
                            {
                                if(!isset($sd[$att]))
                                {
                                    if(empty($dt->{$att}))
                                    {
                                        $sd[$att] = null;
                                        break;
                                    }
                                    $sd[$att] = [];
                                }
                                $dt = $dt->{$att};
                            }
                            elseif(is_array($dt) && array_key_exists($att, $dt))
                            {
                                if(!isset($sd[$att]))
                                    $sd[$att] = [];
                                $dt = $dt[$att];
                            }
                            else
                            {
                                break;
                            }
                            $sd = &$sd[$att];
                            $i++;
                        }
                        if($i == $cnt-1)
                        {
                            $att = $attChain[$i];
                            if(is_object($dt) && property_exists($dt, $att))
                            {
                                $sd[$att] = $dt->{$att};
                            }
                            elseif(is_array($dt) && array_key_exists($att, $dt))
                            {
                                $sd[$att] = $dt[$att];
                            }
                        }
                    }
                    else
                    {
                        $serializeData[$attChain[0]] = null;
                    }
                }
            }
            else
            {
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
                        if(!in_array($att, $notSerializeAttributes))
                            $serializeData[$att] = $data->{$att};
                        else
                            $serializeData[$att] = null;
                    }
                }
                elseif(is_array($data))
                {
                    foreach($data as $att=>$value)
                    {
                        if(!in_array($att, $notSerializeAttributes))
                            $serializeData[$att] = $value;
                        else
                            $serializeData[$att] = null;
                    }
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