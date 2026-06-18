<?php

namespace Symbiote\DynamicLists;

use SilverStripe\Core\Convert;
use SilverStripe\ORM\DataObject;
use SilverStripe\Dev\CsvBulkLoader;

class DynamicListCsvLoader extends CsvBulkLoader
{
    public function __construct($objectClass)
    {
        parent::__construct($objectClass);

        $this->relationCallbacks = [
            'AgencyTitle' => [
                'relationname' => 'Items',
                'callback' => 'getItemByTitle'
            ]
        ];
    }

    protected function processRecord($record, $columnMap, &$results, $preview = false)
    {
        $class = $this->objectClass;

        $title = trim((string) $record['Title']);
        $item = trim((string) $record['ListItem']);

        $existingList = DynamicList::get_dynamic_list($title);
        if (!$existingList) {
            $existingList = DynamicList::create();
            $existingList->Title = $title;
            $existingList->write();
        }

        // now add the item to that list
        $existingItem = DataObject::get_one(
            DynamicListItem::class,
            '"Title"=\'' . Convert::raw2sql($item) . '\' AND "ListID" = ' . ((int) $existingList->ID)
        );
        if (!$existingItem) {
            $existingItem = DynamicListItem::create();
            $existingItem->Title = $item;
            $existingItem->ListID = $existingList->ID;
            $existingItem->write();
        }

        return $existingList->ID;
    }
}
