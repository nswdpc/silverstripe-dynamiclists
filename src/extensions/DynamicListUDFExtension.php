<?php

namespace Symbiote\DynamicLists;

use SilverStripe\Versioned\Versioned;
use SilverStripe\Forms\HTMLReadonlyField;
use SilverStripe\Core\Extension;
use SilverStripe\UserForms\Extension\UserFormFieldEditorExtension;

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

        $title = trim($this->getOwner()->Title ?? '');
        if($title !== '') {

            $used = Versioned::withVersionedMode(
                function() use ($title) {
                    Versioned::set_stage(Versioned::DRAFT);
                    return EditableDynamicListField::get()->filter(['ListTitle' => $title]);
                }
            );

            // Determine whether this dynamic list is being used anywhere.
            $found = [];
            foreach ($used as $field) {
                $parent = $field->Parent();
                if($parent && $parent->hasExtension(UserFormFieldEditorExtension::class) && $parent->hasMethod('getCMSEditLink')) {
                    $link = htmlspecialchars($parent->getCMSEditLink());
                    $title = htmlspecialchars($parent->Title);
                    $found[$field->ParentID] = "<a href=\"{$link}\">{$title}</a>";
                }
            }

            // Display whether there were any dynamic lists found on user defined forms.
            $html = "";
            if ($found !== []) {
                $html = "<ul><li>" . implode("</li><li>", $found) . "</li></ul>";
            }

            $fields->insertAfter(
                'Title',
                HTMLReadonlyField::create(
                    'UsedOnUserDefinedFormField',
                    _t(self::class . '.USED_ON_THESE_FORMS', 'This list is used on these forms'),
                    $html
                )
            );
        }
    }
}
