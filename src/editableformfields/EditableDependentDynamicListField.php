<?php

namespace Symbiote\DynamicLists;

use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\ORM\DataObject;
use SilverStripe\ORM\HasManyList;
use SilverStripe\UserForms\Extension\UserFormFieldEditorExtension;
use SilverStripe\UserForms\Model\EditableFormField\EditableDropdown;
use SilverStripe\UserForms\Model\EditableFormField;

/*

Copyright (c) 2009, Symbiote
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

    * Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
    * Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the
      documentation and/or other materials provided with the distribution.
    * Neither the name of SilverStripe nor the names of its contributors may be used to endorse or promote products derived from this software
      without specific prior written permission.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE
GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY
OF SUCH DAMAGE.
*/

/**
 * A dynamic list whose values are dependent on another list in the page.
 *
 * Relies on the DynamicList module for selecting which dynamic lists it is dependent
 * upon.
 *
 * @author Marcus Nyeholt <marcus@symbiote.com.au>
 */

if (!class_exists(EditableDropdown::class)) {
    return;
}

/**
 * @property ?string $SourceList
 */
class EditableDependentDynamicListField extends EditableDropdown
{
    private static array $db = [
        'SourceList' => 'Varchar(512)'
    ];

    private static string $table_name = 'EditableDependentDynamicListField';

    private static string $singular_name = 'Dependent Dynamic List field';

    private static string $plural_name = 'Dependent Dynamic List fields';

    public function Icon(): string
    {
        return 'userforms/images/editabledropdown.png';
    }

    #[\Override]
    public function getHasAddableOptions()
    {
        return false;
    }

    /**
     * Get the parent source object, must have the UserFormFieldEditorExtension
     * as an extension
     */
    final protected function getParentForFields(): ?DataObject
    {
        $parent = $this->Parent();
        if ($parent && $parent->hasExtension(UserFormFieldEditorExtension::class)) {
            return $parent;
        } else {
            return null;
        }

    }

    /**
     * Get a list of EditableDynamicListField fields
     * from the parent
     */
    protected function getRelevantFieldsFromParent(): ?HasManyList
    {
        $parent = $this->getParentForFields();
        $fields = null;
        if ($parent instanceof DataObject) {
            // the parent has the UserFormFieldEditorExtension extension
            // which provides the 'Fields' relation
            // @phpstan-ignore method.notFound
            $fields = $parent->Fields();
            if ($fields instanceof HasManyList) {
                $fields = $fields->innerJoin('EditableDynamicListField', '"EditableDynamicListField"."ID" = "EditableFormField"."ID"');
            }
        }

        return $fields;
    }

    #[\Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        // select another form field that has the titles of the lists to use for this list when displayed
        // The assumption being made here is that each entry in the source list has a corresponding dynamic list
        // defined for it, which we use later on.
        $options = [];
        $sourceList = $this->getRelevantFieldsFromParent();
        if ($sourceList instanceof \SilverStripe\ORM\HasManyList) {
            $options = $sourceList->map('Name', 'Title');
        }

        $fields->addFieldToTab(
            'Root.Main',
            DropdownField::create(
                'SourceList',
                _t('EditableDependentDynamicListField.SOURCE_LIST_TITLE', 'Source List'),
                $options
            )->setEmptyString(_t(self::class . '.DYNAMICLIST_SELECT_ONE', '(select one)'))
        );

        return $fields;
    }

    #[\Override]
    public function getFormField()
    {
        $sourceList = trim($this->SourceList ?? '');
        $optionLists = [];

        $source = null;
        if ($sourceList !== '') {
            // first off lets go and output all the options we need
            $fields = $this->getRelevantFieldsFromParent();

            foreach ($fields as $field) {
                if ($field->Name == $sourceList) {
                    $source = $field;
                    break;
                }
            }
        }

        if ($source instanceof \SilverStripe\ORM\DataObject) {
            // all our potential lists come from the source list's dynamic list source, so we need to go load that
            // first, then iterate it and build all the additional required lists
            $sourceList = DynamicList::get_dynamic_list($source->ListTitle ?? '');
            if ($sourceList instanceof \Symbiote\DynamicLists\DynamicList) {
                $items = $sourceList->Items();

                // now lets create a bunch of option fields
                foreach ($items as $sourceItem) {
                    // now get the dynamic list that is represented by this one
                    $list = DynamicList::get_dynamic_list($sourceItem->Title ?? '');
                    if ($list instanceof \Symbiote\DynamicLists\DynamicList) {
                        $optionLists[$sourceItem->Title] = $sourceItem->Title;
                    }
                }
            }

            if ($optionLists !== []) {
                $field = DependentDynamicListDropdownField::create(
                    $this->Name,
                    $this->Title,
                    $optionLists,// array
                    $source->Name// string
                )->addExtraClass('uf-dependentdynamiclistdropdown');
            } else {
                $field = DropdownField::create($this->Name, $this->Title, []);
            }

            $field->setFieldHolderTemplate(EditableFormField::class . '_holder')
                ->setTemplate(self::class);

        } else {
            $field = LiteralField::create(
                $this->Name,
                '<p>' . htmlspecialchars(_t('EditableDependentDynamicListField.NO_SOURCE_LIST_FOUND', 'No source list found')) . '</p>'
            );
        }

        $this->doUpdateFormField($field);
        return $field;
    }
}
