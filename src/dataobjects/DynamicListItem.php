<?php

namespace Symbiote\DynamicLists;

use SilverStripe\ORM\DB;
use SilverStripe\Security\Member;
use SilverStripe\Security\Permission;
use SilverStripe\ORM\DataObject;

/**
 * A dynamic list is a user specified list of data items that can be used
 * for a variety of areas in the site where a predefined list is used
 * using the DynamicListField form control.
 *
 * @author Marcus Nyeholt <marcus@symbiote.com.au>
 * @property string $Title
 * @property int $Sort
 * @property int $ListID
 * @method \Symbiote\DynamicLists\DynamicList List()
 */
class DynamicListItem extends DataObject
{
    private static string $table_name = 'DynamicListItem';

    private static array $db = [
        'Title' => 'Varchar(128)',
        'Sort' => 'Int'
    ];

    private static array $indexes = [
        'Title' => true,
        'Sort' => true
    ];

    private static array $has_one = [
        'List' => DynamicList::class
    ];

    private static array $summary_fields = [
        'Title'
    ];

    private static string $default_sort = 'Sort, ID';


    #[\Override]
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName('Sort');
        $fields->removeByName('ListID');
        return $fields;
    }

    #[\Override]
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        //      if (!$this->Sort) {
        //          $parentID = ($this->ListID) ? $this->ListID : 0;
        //          $this->Sort = DB::query("SELECT MAX(\"Sort\") + 1 FROM \"DynamicListItem\" WHERE \"ListID\" = $parentID")->value();
        //      }
    }

    #[\Override]
    public function onAfterWrite()
    {
        parent::onAfterWrite();
        if (($list = $this->List()) && $list->config()->get('cache_lists')) {
            $list->cacheListData();
        }
    }

    #[\Override]
    public function canView($member = null)
    {
        return true;
    }

    /**
     * @param Member $member
     * @return boolean
     */
    #[\Override]
    public function canEdit($member = null)
    {
        return Permission::check('CMS_ACCESS_Symbiote\DynamicLists\DynamicListAdmin', 'any', $member);
    }

    /**
     * @param Member $member
     * @return boolean
     */
    #[\Override]
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
    #[\Override]
    public function canCreate($member = null, $context = [])
    {
        return Permission::check('CMS_ACCESS_Symbiote\DynamicLists\DynamicListAdmin', 'any', $member);
    }
}
