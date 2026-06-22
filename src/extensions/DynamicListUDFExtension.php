<?php

namespace Symbiote\DynamicLists;

use SilverStripe\Versioned\Versioned;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Core\Extension;
use SilverStripe\UserForms\Model\EditableFormField;
use SilverStripe\UserForms\Model\UserDefinedForm;

/**
 *  This extension is to help identify dynamic lists a little better.
 *  @author Nathan Glasl <nathan@symbiote.com.au>
 * @extends \SilverStripe\Core\Extension<(static & \Symbiote\DynamicLists\DynamicList)>
 */
class DynamicListUDFExtension extends Extension
{
    private static string $default_sort = 'Title';

    public function updateDynamicListCMSFields($fields)
    {

        // Make sure the draft records are being looked at.

        $stage = Versioned::get_stage();
        Versioned::set_stage(Versioned::DRAFT);
        $used = EditableFormField::get()->filter(['ClassName:PartialMatch' => DynamicList::class]);

        // Determine whether this dynamic list is being used anywhere.

        $found = [];
        foreach ($used as $field) {
            // This information is stored using a serialised list, therefore we need to iterate through.
            // Make sure there are no duplicates recorded.
            if ($field->ListTitle === $this->getOwner()->Title && (!isset($found[$field->ParentID]) && $form = UserDefinedForm::get()->byID($field->ParentID))) {
                $found[$field->ParentID] = "<a href='{$form->getCMSEditLink()}'>{$form->Title}</a>";
            }
        }

        // Display whether there were any dynamic lists found on user defined forms.

        if ($found !== []) {
            $fields->removeByName('UsedOnHeader');
            $fields->addFieldToTab('Root.Main', HeaderField::create('UsedOnHeader', 'Used On', 5));
        }

        $display = count($found) ? implode('<br>', $found) : 'This dynamic list is <strong>not</strong> used.';
        $fields->removeByName('UsedOn');
        $fields->addFieldToTab('Root.Main', LiteralField::create('UsedOn', '<div>' . $display . '</div>'));
        Versioned::set_stage($stage);
    }
}
