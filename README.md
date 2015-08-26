# Contao Tags Input

Contao port of [Bootstrap Tags Input](http://timschlechter.github.io/bootstrap-tagsinput/examples/) that provides an front and back end field to add custom values like tags to an field or database table.

## Features

- Frontend and backend support (inputType : tagsinput)
- Free Input can be set to true or false on dca field config.
- [typeahead.js](http://twitter.github.io/typeahead.js/) support
- styled for front and back end
- add new tags by the following actions: pressing tab/return/semicolon and comma or by leaving the field (as long as freeInput is enabled and a value was typed inside the field)

## Setup

1. Store values as array to an field


Add the following field syntax to your datacontainer field config and add the field to your palette of choice.

```
'locations'         => array
(
	'label'            => &$GLOBALS['TL_LANG']['tl_member']['locations'],
	'exclude'          => true,
	'inputType'        => 'tagsinput',
	'eval'             => array
	(
		'mandatory'   => true,
		'placeholder' => &$GLOBALS['TL_LANG']['tl_member']['placeholders']['locations'],
		'freeInput'   => false,
	),
	'options_callback' => array('tl_member_custom', 'getLocations'),
	'sql'              => 'blob NULL',
),
```

***

2. Store values as new entity in detached database table.

If you want to store the tags into another table for example tl_hobbies, add the following field syntax to your datacontainer field config and add the field to your palette of choice.


```
'hobbies' => array
(
	'label'            => &$GLOBALS['TL_LANG']['tl_member']['hobbies'],
	'inputType'        => 'tagsinput',
	'eval'             => array(
		'mandatory' => true,
		'freeInput' => true,
		'save_tags' => array(
			'table'    => 'tl_tags',
			'tagField' => 'title',
			'defaults' => array
			(
				'tstamp'    => time(),
				'type'      => 'community',
				'published' => false,
			),
		),
		'tags_callback' => array(array('tl_member_custom', 'addTagAttributes')),
		'placeholder' => &$GLOBALS['TL_LANG']['tl_member']['placeholders']['hobbies'],
	),
	'options_callback' => array('tl_member_custom', 'getHobbiesOptions'),
	'sql'              => 'blob NULL',
),
```

### save_tags settings

Option | Default | Description | Mandatory
------ | ---- | ---- | ----
table | | Must contain the table name, where new tags should be stored at. | true
tagField | | The name of the database field, that contains the label of the tag. | true
defaults | | An array of default values, that should be set for new database tag entries. | depends on your table config
freeInput | true | Enable or disable, the possibility to add custom tags. | false


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
