# Contao Tags Input

Contao port of [Bootstrap Tags Input](http://timschlechter.github.io/bootstrap-tagsinput/examples/) that provides an front and back end field to add custom values like tags to an field or database table.

## Features

- Frontend and backend support (inputType : tagsinput)
- Free Input can be set to true or false on dca field config.
- [typeahead.js](http://twitter.github.io/typeahead.js/) support
- styled for front and back end
- fetch options asynchronously remote (optional)
- sort tags per drag & drop
- add new tags by the following actions: pressing tab/return/semicolon and comma or by leaving the field (as long as freeInput is enabled and a value was typed inside the field)

## Setup / Examples

### 1. Tagsinput with options or options_callback and one possible tag selection

```
'locations'         => array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_entity_lock']['locations'],
    'inputType' => 'tagsinput',
    'sql'       => "varchar(255) NOT NULL default ''",
    'options'   => array('boston', 'berlin', 'hamburg', 'london'),
    'eval'      => array
    (
        'freeInput'   => false
        'tl_class' => 'w50 autoheight'
    )
),
```

### 2. Tagsinput with options or options_callback and multiple possible tag selection and freeInput

```
'locations'         => array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_entity_lock']['locations'],
    'inputType' => 'tagsinput',
    'sql'       => "blob NULL",
    'options'   => array('boston', 'berlin', 'hamburg', 'london'),
    'eval'      => array
    (
        'multiple'        => true,
        'freeInput'       => true,
        'multiple'        => true,
		'maxTags'         => 3,
		'maxChars'        => 12,
		'trimValue'       => true,
		'allowDuplicates' => true,
		'tl_class' => 'w50 autoheight'
    )
),
```

### 3. Tagsinput with freeInput and one possible tag selection

```
'locations'         => array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_entity_lock']['locations'],
    'inputType' => 'tagsinput',
    'sql'       => "varchar(255) NOT NULL default ''",
    'eval'      => array
    (
        'freeInput'   => true,
        'tl_class' => 'w50 autoheight'
    )
),
```

### 4. Tagsinput with freeInput and multiple possible tag selection

```
'locations'         => array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_entity_lock']['locations'],
    'inputType' => 'tagsinput',
    'sql'       => "blob NULL",
    'eval'      => array
    (
        'multiple'    => true,
        'freeInput'   => true,
        'tl_class' => 'w50 autoheight'
    )
),
```

### 5. Tagsinput with remote options and one possible tag selection

```
'locations'         => array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_entity_lock']['locations'],
    'inputType' => 'tagsinput',
    'sql'       => "int(10) unsigned NOT NULL default '0'",
    'eval'      => array(
        'placeholder' => &$GLOBALS['TL_LANG']['tl_member']['placeholders']['locations'],
        'freeInput'   => false,
        'mode'        => \TagsInput::MODE_REMOTE,
        'remote'      => array
        (
            'fields'       => array('title', 'id'),
            'format'       => '%s [ID:%s]',
            'queryField'   => 'title',
            'queryPattern' => '%QUERY%', 
            'foreignKey'   => '%parentTable%.id',
            'limit'        => 10
        ),
        'tl_class' => 'w50 autoheight'
    )
),
```

As you can see, there must be a remote configuration, with the tags format and fields, taken from the `foreignKey` relation.
The `foreignKey` must be a valid database table reference, by a given table name and field. 
It is also possible to reference the table name from a field within the current record, by adding percent sign before and behind the field name.
This might be helpful for dynamic tagsinput fields. The `queryField` represents the lookup field for the given typeahead query.
To provide a custom query pattern for the LIKE search, simply add a custom `queryPattern` and place percent sign where you want;

### 5. Tagsinput with remote options from relation table and multiple possible tag selections and freeInputs 

```
'locations'         => array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_entity_lock']['locations'],
    'inputType' => 'tagsinput',
    'sql'       => "blob NULL",
    'eval'      => array(
        'placeholder' => &$GLOBALS['TL_LANG']['tl_member']['placeholders']['locations'],
        'freeInput'   => true,
        'mode'        => \TagsInput::MODE_REMOTE,
        'remote'      => array
        (
            'fields'       => array('title', 'id'),
            'format'       => '%s [ID:%s]',
            'queryField'   => 'title',
            'queryPattern' => '%QUERY%', 
            'foreignKey'   => '%parentTable%.id',
            'limit'        => 10
        ),
        'save_tags' => array(
            'table'    => 'tl_calendar_events',
            'tagField' => 'title',
            'defaults' => array
            (
                'tstamp'    => time(),
                'pid'       => 1,
                'type'      => 'community',
                'published' => false
            )
        ),
        'tl_class' => 'w50 autoheight'
    )
),
```

If you want to add tags as entities within remote mode, you have to enable freeInput and provide a valid save_tags configuration like the one provided above.
The `tagField` should be the field from the `save_tags` table where the user input from tagsinput should be stored in.

### 6. Tagsinput with remote options from options or options_callback and multiple possible tag selections and freeInputs and tags_callback

```
'locations'         => array
(
	'label'     => &$GLOBALS['TL_LANG']['tl_entity_lock']['locations'],
    'inputType' => 'tagsinput',
    'options'   => array('boston', 'berlin', 'hamburg', 'london'),
    'sql'       => "blob NULL",
    'eval'      => array(
        'placeholder' => &$GLOBALS['TL_LANG']['tl_member']['placeholders']['locations'],
        'freeInput'   => true,
        'mode'        => \TagsInput::MODE_REMOTE,
        'remote'      => array
        (
            'queryPattern' => '%QUERY%', 
            'limit'        => 10
        ),
        'tags_callback' => array(array('MyClass', 'addTagAttributes')),
        'tl_class' => 'w50 autoheight'
    ),
),
```
### eval options
Option | Default | Description | Mandatory
------ | ---- | ---- | ----
freeInput | true | Enable or disable, the possibility to add custom tags. | false
multiple | true | Enable multiple input tags, disable for single tags. | false  
maxTags | undefined | When set and multiple, no more than the given number of tags are allowed to add (default: undefined). When maxTags is reached, a class 'bootstrap-tagsinput-max' is placed on the tagsinput element. | false
maxChars | undefined | Defines the maximum length of a single tag. | false
trimValue | false | When true, automatically removes all whitespace around tags.
allowDuplicates | false | When true, the same tag can be added multiple times.| false
highlight | true | Set to false if the typeahead dropdown should not be shown on focus, without input. | false
highlightOptions | First 5 options | Enter the array that returns highlight option, when focus input without typing someting in input. | false
highlightOptionsCallback | First 5 options | Enter a valid callback, that returns all highlighted options as array, when focus input without typing someting in input. | false
limit | 5 | Limit the options when typed somethin in the input. | false
showTagList | false | Displays a tag list with all available options (local only atm) | false
tagListWeightClassCount | 6 | Specifies how many css classes are used for weighting (predefined styles are available for count up to 10) | false
option_weights | null | Expects a callable function returning an array where keys are tag values and values are counts or this array itself | false
option_weights_callback | null | Expects an array like ['MyClass', 'getWeights'] referencing a function returning an array where keys are tag values and values are counts or this array itself | false

### save_tags settings

Option | Default | Description | Mandatory
------ | ---- | ---- | ----
table | | Must contain the table name, where new tags should be stored at. | true
tagField | | The name of the database field, that contains the label of the tag. | true
defaults | | An array of default values, that should be set for new database tag entries. | depends on your table config

### remote settings

Option | Default | Description | Mandatory
------ | ---- | ---- | ----
fields | - | Must contain the table name, where new tags should be stored at. | Only when foreignKey is provided.
format | -  | The name of the database field, that contains the label of the tag. | Only when foreignKey is provided.
queryField | - | An array of default values, that should be set for new database tag entries. | Only when foreignKey is provided.
queryPattern | '%QUERY%' | Enable or disable, the possibility to add custom tags. | true
foreignKey | - | Enable or disable, the possibility to add custom tags. | false
limit | 10 | Enable or disable, the possibility to add custom tags. | false

### tags_callback

The tags_callback should be a valid callback that can be used to manipulate the tag object.

```
// tags_callback example callback
class tl_member_custom extends \Backend
{
	public function addTagAttributes($arrOption, DataContainer $dc)
	{
		$objTag = MyTagsModel::findByPk($arrOption['value']);

		if(!$objTag->published)
		{
			$arrOption['class'] .= ' new';
		}

		return $arrOption;
	}
}
```
