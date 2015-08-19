<?php
///////////////////////////////////////////////////////////////////////////////
//
// Licensed Source Code - Property of ProjectKit.net
//
// © Copyright ProjectKit.net 2015. All Rights Reserved.
//
///////////////////////////////////////////////////////////////////////////////
/* ****************************************************************************
 *
 * This class is automatically generated and maintained by Gii, be careful
 * when modifying it.
 *
 * Your additional properties and methods should be placed at the bottom of
 * this class.
 *
 *****************************************************************************/
/**
 * This is the ProjectKit.net VO class associated with the model "Project".
 */
class TestModel02
{
    /**
     * Map the ActionScript class that has alias 'FProject' to this VO class:
     */
    public $_explicitType = 'TestModel02';

    /** @var integer $id */
    public $id;

    /** @var string $code */
    public $code;

    /** @var string $name */
    public $name;

    /** @var integer $type */
    public $type;

    /** @var string $description */
    public $description;

    /** @var DateTime $startTime */
    public $startTime;

    /** @var DateTime $endTime */
    public $endTime;

    /** @var mixed $jsonData */
    public $jsonData;

    /** @var integer $status */
    public $status;

    /** @var  TestModel01 $model1 */
    public $model1;

    public $issueWorkflow;

    public $projectSummaryTask;

    public $workCalendar;

    public $resources;

    public $projectTasks;
}