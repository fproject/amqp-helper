<?php
///////////////////////////////////////////////////////////////////////////////
//
// Licensed Source Code - Property of ProjectKit.net
//
// © Copyright ProjectKit.net 2015. All Rights Reserved.
//
///////////////////////////////////////////////////////////////////////////////
namespace fproject\amqp;

/**
 * This is the abstract base class for all form model classes of this application.
 * It is a customized model based on Yii CFormModel class.
 * All form model classes of this application should extend from this class.
 * @method array getMasterAttributeLabel(mixed $attribute, mixed $type) Get an item data from MasterValue text values,
 * make it available for label displaying in forms and views.
 *
 * @author Bui Sy Nguyen <nguyenbs@gmail.com>
 */
class ActivityNotice {
    /** @var  string $kind */
    public $kind;

    /** @var  string $action */
    public $action;

    /** @var  string $oriType */
    public $oriType;

    /** @var  string $oriId */
    public $oriId;

    /** @var  mixed $oriTime */
    public $oriTime;

    /** @var  mixed $content */
    public $content;

    /** @var  string[] $contentUpdatedFields */
    public $contentUpdatedFields;

    /**
     * @param array $source
     */
    public function __construct($source = [])
    {
        if(isset($source))
        {
            foreach($source as $key=>$value)
            {
                $this->{$key} = $value;
            }
        }
    }
}