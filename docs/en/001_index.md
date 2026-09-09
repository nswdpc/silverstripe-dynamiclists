## Documentation

## Dynamic list

A dynamic list is added via the "Dynamic Lists" administration area screen. Each list has a title and zero or more items.

An item is a child of a dynamic list, it also has a title.

Dynamic lists are used to structure data in a strict parent-child relationship.

## User defined forms

If you have the user forms module installed two fields become available for use in forms:

+ A dynamic list field - represented as a selection field where the options are the list child items.
+ A dependent dynamic list field - represented as a selection where the options are populated based on a linked dynamic list field.

###  Dependent dynamic list field usage

#### Form field setup
1. Add a Dynamic List field to the form. Take note of the "Merge field" value.
2. Add a "Dependent Dynamic List field"
3. In the "Source List" field, select a Dynamic List field that was added at step 1 above.

#### Dynamic list setup
1. Create a dynamic list and give it the same title as the "Source List" field name at 'Form field setup' step 1 above.
2. Add items to this list, these will be the options for the field created at 'Form field setup' step 1 above.
3. Create one dynamic list for each list item added at Step 2 above. Each of these lists should have items added.


With the above steps followed, when an option is selected in the dynamic list field, the linked dependent dynamic list field will show that field's items as options to select.
