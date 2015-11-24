<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2015 Heimrich & Hannot GmbH
 *
 * @package tagsinput
 * @author  Rico Kaltofen <r.kaltofen@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */
class TagsInput extends \Widget
{
	/**
	 * Submit user input
	 *
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'be_widget';


	/**
	 * Class
	 *
	 * @var string
	 */
	protected $strClass = 'tl_tagsinput';

	protected $arrTags = array();

	protected $arrSelectedOptions = array();

	/**
	 * Add specific attributes
	 *
	 * @param string
	 * @param mixed
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey) {
			case 'mandatory':
				if ($varValue) {
					$this->arrAttributes['required'] = 'required';
				} else {
					unset($this->arrAttributes['required']);
				}
				parent::__set($strKey, $varValue);
				break;

			case 'size':
				if ($this->multiple) {
					$this->arrAttributes['size'] = $varValue;
				}
				break;

			case 'multiple':
				if ($varValue) {
					$this->arrAttributes['multiple'] = 'multiple';
				}
				break;

			case 'options':
				$this->arrOptions = deserialize($varValue);
				break;

			default:
				parent::__set($strKey, $varValue);
				break;
		}
	}


	/**
	 * Check whether an option is selected
	 *
	 * @param array $arrOption The options array
	 *
	 * @return string The "selected" attribute or an empty string
	 */
	protected function isSelected($arrOption)
	{
		if (empty($this->varValue) && empty($_POST) && $arrOption['default'])
		{
			return static::optionSelected(1, 1);
		}

		return static::optionSelected($arrOption['value'], $this->varValue);
	}

	/**
	 * Check for a valid option (see #4383)
	 */
	public function validate()
	{
		// set values from options instead of label
		$varInput = $this->getPost($this->strName);

		// support single tags
		if(!is_array($varInput))
		{
			$varInput = array($varInput);
		}

		if(!empty($varInput))
		{
			// remove duplicates
			$varInput = array_unique($varInput);

			$varInput = $this->addValuesFromOptions($varInput);

			if(!$this->isValidOption($varInput))
			{
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalid'], (is_array($varInput) ? implode(', ', $varInput) : $varInput)));
			}
		}

		$varInput = $this->validator($varInput);

		if ($this->hasErrors())
		{
			$this->class = 'error';
		}

		$this->varValue = $varInput;
	}

	protected function addValuesFromOptions($varValue)
	{
		$values = array();

		if(!is_array($varValue))
		{
			$varValue = array($varValue);
		}
		
		foreach($varValue as $key => $strTag)
		{
			$blnFound = false;

			// convert html entities back, otherwise compare for html entities will fail and tag never added
			$strTag = \Input::decodeEntities($strTag);

			foreach ($this->arrOptions as $v)
			{
				// set value for existing tags
				if (array_key_exists('value', $v))
				{
					if ($strTag == $v['label'])
					{
						if($this->multiple)
						{
							$values[$key] = $v['value'];
						}
						else{
							$values = $v['value'];
						}

						$blnFound = true;
						break;
					}
				}
			}
			

			if(!$blnFound && ($intId = $this->addNewTag($strTag)) > 0)
			{
				if($this->multiple)
				{
					$values[$key] = $intId;
				}
				else{
					$values = $intId;
				}

				// add new value to options
				$this->arrOptions[] = array('value' => $intId, 'label' => $strTag);
			}
		}

		return $values;
	}

	/**
	 * add a new tag
	 * @param $strTag
	 */
	protected function addNewTag($strTag)
	{
		if($strTag == '') return false;

		if(($arrSaveConfig = $this->arrConfiguration['save_tags']) !== null && isset($arrSaveConfig['table']))
		{
			$strTable = $arrSaveConfig['table'];
			$strModelClass = \Model::getClassFromTable($arrSaveConfig['table']);

			if(!class_exists($strModelClass))
			{
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalidTagsModel'], $strTable));
				return false;
			}

			$strTagField = $arrSaveConfig['tagField'];

			if(!\Database::getInstance()->fieldExists($strTagField, $arrSaveConfig['table'])){
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalidTagsField'], $strTagField, $strTable));
				return false;
			}
			
			$objModel = new $strModelClass();
			$objModel->tstamp = 0;

			// overwrite model with defaults from dca
			if(is_array($arrSaveConfig['defaults']))
			{
				$objModel->setRow($arrSaveConfig['defaults']);
			}

			$objModel->{$strTagField} = $strTag;
			$objModel->save();

			return $objModel->id;
		}
	}

	protected function prepare()
	{
		$this->addCssFiles();

		if ($this->multiple) {
			$this->addAttribute('multiple', true);
			$this->strName .= '[]';
		} else{
			$this->addAttribute('data-max-tags', 1);
		}

		if ($this->submitOnChange)
		{
			unset($this->arrAttributes['onchange']);
			$this->addAttribute('data-submitonchange', true);
		}

		// Add an empty option (XHTML) if there are none
		if (empty($this->arrOptions)) {
			$this->arrOptions = array(array('value' => '', 'label' => '-'));
		}
		
		foreach ($this->arrOptions as $strKey => $arrOption)
		{
			if (isset($arrOption['value']))
			{
				$arrOption['class'] = 'label label-info';

				// Call tags_callback
				if (is_array($this->arrConfiguration['tags_callback']))
				{
					foreach ($this->arrConfiguration['tags_callback'] as $callback)
					{
						if (is_array($callback))
						{
							$this->import($callback[0]);
							$arrOption = $this->$callback[0]->$callback[1]($arrOption, $this->objDca);
						}
						elseif (is_callable($callback))
						{
							$arrOption = $callback($arrOption, $this->objDca);
						}
					}
				}

				// check option after callback
				if(!is_array($arrOption) && !isset($arrOption['value'])) continue;

				$this->arrTags[] = $arrOption;

				// add only selected values as option
				if($this->isSelected($arrOption))
				{
					$this->arrSelectedOptions[] = sprintf(
						'<option value="%s"%s%s>%s</option>',
						specialchars($arrOption['label']),
						(($arrOption['class'] != '') ? 'class="' . $arrOption['class'] . '"': ''),
						(($arrOption['target'] != '') ? 'data-target="' . $arrOption['class'] . '"': ''),
						$arrOption['label']
					);
				}
			}
		}

		$this->addAttribute('data-items', htmlspecialchars(json_encode($this->arrTags), ENT_QUOTES, 'UTF-8'));
		
		$this->addAttribute('data-free-input', ($this->arrConfiguration['freeInput'] !== false ? 'true' : 'false'));

		if($this->arrConfiguration['placeholder'])
		{
			$this->addAttribute('data-placeholder', $this->arrConfiguration['placeholder']);
		}
	}


	/**
	 * Generate the widget and return it as string
	 *
	 * @return string
	 */
	public function generate()
	{
		$this->prepare();

		return sprintf(
			'<select name="%s" id="ctrl_%s" class="%s"%s onfocus="Backend.getScrollOffset()">%s</select>%s',
			$this->strName,
			$this->strId,
			(($this->strClass != '') ? ' ' . $this->strClass : ''),
			$this->getAttributes(),
			implode('', $this->arrSelectedOptions),
			$this->wizard
		);
	}

	protected function addCssFiles()
	{
		$GLOBALS['TL_CSS']['tagsinput']        = 'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.css';

		if(TL_MODE == 'BE')
		{
			$GLOBALS['TL_CSS']['tagsinput-be']        = 'system/modules/tagsinput/assets/css/bootstrap-tagsinput-be.css';
			$GLOBALS['TL_CSS']['typeahead-be']        = 'system/modules/tagsinput/assets/css/typeahead-be.css';
		} else {
			$GLOBALS['TL_CSS']['tagsinput-fe']        = 'system/modules/tagsinput/assets/css/bootstrap-tagsinput-fe.css';
			$GLOBALS['TL_CSS']['typeahead-fe']        = 'system/modules/tagsinput/assets/css/typeahead-fe.css';
		}
	}

}
