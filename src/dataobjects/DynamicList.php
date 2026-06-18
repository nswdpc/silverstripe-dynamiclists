<?php

namespace Symbiote\DynamicLists;

use SilverStripe\Forms\GridField\GridFieldConfig_RelationEditor;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;

/**
 * A data list is a user specified list of data items that can be used
 * for a variety of areas in the site where a predefined list is used
 * using the DynamicListField form control.
 *
 * @author Marcus Nyeholt <marcus@symbiote.com.au>
 * @license BSD License http://silverstripe.org/bsd-license
 */
class DynamicList extends DataObject
{
    private static $table_name = 'DynamicList';

    private static $db = [
        'Title' => 'Varchar(128)',
        'CachedItems'   => 'Text'
    ];

    private static $indexes = [
        'Title' => true
    ];

    private static $has_many = [
        'Items' => DynamicListItem::class
    ];

    /**
     * Should list items be cached?
     */
    private static bool $cache_lists = false;

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName('CachedItems');

        if ($this->ID) {
            $orderableComponent = GridFieldOrderableRows::create('Sort');
            $conf = GridFieldConfig_RelationEditor::create(20);
            $conf->addComponent($orderableComponent);
            $fields->addFieldToTab('Root.Items', GridField::create('Items', 'Dynamic List Items', $this->Items(), $conf));
        }

        // Allow extension.

        $this->extend('updateDynamicListCMSFields', $fields);
        return $fields;
    }

    public function onBeforeDelete()
    {
        parent::onBeforeDelete();
        // delete all items that were attached
        $items = $this->Items();
        foreach ($items as $item) {
            $item->delete();
        }
    }

    public function canView($member = null)
    {
        return true;
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canEdit($member = null)
    {
        return Permission::check('CMS_ACCESS_Symbiote\DynamicLists\DynamicListAdmin', 'any', $member);
    }

    /**
     * @param Member $member
     * @return boolean
     */
    public function canDelete($member = null)
    {
        return Permission::check('CMS_ACCESS_Symbiote\DynamicLists\DynamicListAdmin', 'any', $member);
    }

    /**
     * @todo Should canCreate be a static method?
     *
     * @param Member $member
     * @return boolean
     */
    public function canCreate($member = null, $context = [])
    {
        return Permission::check('CMS_ACCESS_Symbiote\DynamicLists\DynamicListAdmin', 'any', $member);
    }

    /**
     * Convenience method for getting a data list via its title
     */
    public static function get_dynamic_list(string $title): ?DynamicList
    {
        $list = DynamicList::get()->filter(['Title' => $title])->first();
        return $list;
    }

    public function getItemByTitle($title): DynamicListItem
    {
        $item = DynamicListItem::get()->filter([
            "ListID" => $this->ID,
            "Title" => $title
        ])->first();
        if (!$item || !$item->exists()) {
            // create item
            $item = DynamicListItem::create();
            $item->ListID = $this->ID;
            $item->Title = $title;
            $item->write();
        }

        return $item;
    }

    public function cacheListData()
    {
        $items = $this->Items();
        if ($items) {
            $mapped = [];
            foreach ($items as $i) {
                $mapped[$i->ID] = $i->Title;
            }
        }
    }

    /**
     * Get a map of ID => Title for the contained items in this list
     */
    public function itemArray()
    {
        if ($this->config()->get('cache_lists')) {
            $str = $this->CachedItems;
            if (strlen($str) && $items = @unserialize($str)) {
                return $items;
            }
        }

        $mapped = $this->Items()->map()->toArray();
        return $mapped;
    }
}
