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