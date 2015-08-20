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

    /** @var  string $action the action of activity notice */
    public $action;

    /** @var  mixed $dispatchTime the dispatched time of the notice*/
    public $dispatchTime;

    /** @var  mixed $dispatcher the dispatcher, often be the currently logged in user */
    public $dispatcher;

    /** @var  mixed $content the content of activity */
    public $content;

    /** @var  string[] $contentUpdatedFields the list of updated fields of the content */
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