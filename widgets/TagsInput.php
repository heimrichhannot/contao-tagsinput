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

	const MODE_LOCAL  = 'local';
	const MODE_REMOTE = 'remote';

	const ACTION_FETCH_REMOTE_OPTIONS = 'fetchRemoteOptions';

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
		if (empty($this->varValue) && empty($_POST) && $arrOption['default']) {
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
		if (!is_array($varInput)) {
			$varInput = array($varInput);
		}

		if (!empty($varInput))
		{
			// remove duplicates
			$varInput = array_unique($varInput);

			$varInput = $this->setValuesByOptions($varInput);

			if (!$this->isValidOption($varInput)) {
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalid'], (is_array($varInput) ? implode(', ', $varInput) : $varInput)));
			}
		}

		$varInput = $this->validator($varInput);

		if ($this->hasErrors()) {
			$this->class = 'error';
		}

		$this->varValue = $varInput;
	}

	protected function setValuesByOptions($varValue)
	{
		$values    = array();
		$freeInput = $this->arrConfiguration['freeInput'];

		if (!is_array($varValue)) {
			$varValue = array($varValue);
		}

		// add remote options
		$this->arrOptions = $this->getOptions($varValue);

		foreach ($varValue as $key => $strTag) {
			$blnFound = false;

			// convert html entities back, otherwise compare for html entities will fail and tag never added
			$strTag = \Input::decodeEntities($strTag);

			foreach ($this->arrOptions as $v) {
				// set value for existing tags
				if (array_key_exists('value', $v))
				{
					// check options against numeric key or string value
					if ((is_numeric($strTag) && $strTag == $v['value']) || $strTag == $v['label'])
					{
						if ($this->multiple)
						{
							$values[$key] = $v['value'];
						} else
						{
							$values = $v['value'];
						}

						$blnFound = true;
						break;
					}
				}
			}
			

			if (!$blnFound && ($intId = $this->addNewTag($strTag)) > 0 || $freeInput)
			{
				$value = $freeInput ? $strTag : $intId;

				if ($this->multiple) {
					$values[$key] = $value;
				} else {
					$values = $value;
				}

				// add new value to options
				$this->arrOptions[] = array('value' => $value, 'label' => $strTag);
			}
		}

		return $values;
	}

	/**
	 * Add a new tag
	 *
	 * @param $strTag
	 *
	 * @return bool
	 */
	protected function addNewTag($strTag)
	{
		if ($strTag == '') {
			return false;
		}

		if (($arrSaveConfig = $this->arrConfiguration['save_tags']) !== null && isset($arrSaveConfig['table'])) {
			$strTable      = $arrSaveConfig['table'];
			$strModelClass = \Model::getClassFromTable($arrSaveConfig['table']);

			if (!class_exists($strModelClass)) {
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalidTagsModel'], $strTable));

				return false;
			}

			$strTagField = $arrSaveConfig['tagField'];

			if (!\Database::getInstance()->fieldExists($strTagField, $arrSaveConfig['table'])) {
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalidTagsField'], $strTagField, $strTable));

				return false;
			}
			
			$objModel         = new $strModelClass();
			$objModel->tstamp = 0;

			// overwrite model with defaults from dca
			if (is_array($arrSaveConfig['defaults'])) {
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
		} else {
			$this->addAttribute('data-max-tags', 1);
		}

		if ($this->submitOnChange)
		{
			unset($this->arrAttributes['onchange']);
			$this->addAttribute('data-submitonchange', true);
		}

		// add remote options or freeInput options
		$this->arrOptions = $this->getOptions(deserialize($this->varValue, true));

		// Add an empty option (XHTML) if there are none
		if (empty($this->arrOptions)) {
			$this->arrOptions = array(array('value' => '', 'label' => '-'));
		}
		
		foreach ($this->arrOptions as $strKey => $arrOption)
		{
			if (isset($arrOption['value']))
			{
				$this->arrTags[] = $arrOption;

				// add only selected values as option
				if ($this->isSelected($arrOption)) {
					$this->arrSelectedOptions[] = sprintf(
						'<option value="%s"%s%s>%s</option>',
						is_numeric($arrOption['value']) ? $arrOption['value'] : specialchars($arrOption['label']),
						(($arrOption['class'] != '') ? 'class="' . $arrOption['class'] . '"' : ''),
						(($arrOption['target'] != '') ? 'data-target="' . $arrOption['class'] . '"' : ''),
						$arrOption['label']
					);
				}
			}
		}

		$this->addAttribute('data-items', htmlspecialchars(json_encode($this->arrTags), ENT_QUOTES, 'UTF-8'));
		
		$this->addAttribute('data-free-input', ($this->arrConfiguration['freeInput'] !== false ? 'true' : 'false'));

		$strMode = $this->arrConfiguration['mode'] ?: static::MODE_LOCAL;

		$this->addAttribute('data-mode', $strMode);

		switch ($strMode) {
			case static::MODE_REMOTE:
				$this->addAttribute(
					'data-post-data',
					htmlspecialchars(
						json_encode(
							array(
								'action'        => static::ACTION_FETCH_REMOTE_OPTIONS,
								'name'          => $this->name,
								'REQUEST_TOKEN' => \RequestToken::get(),
							)
						)
					)
				);
				break;
		}

		if ($this->arrConfiguration['placeholder']) {
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
			'<select name="%s" id="ctrl_%s" class="%s"%s>%s</select>%s',
			$this->strName,
			$this->strId,
			trim(($this->strClass != '') ? ' ' . $this->strClass : ''),
			$this->getAttributes(),
			implode('', $this->arrSelectedOptions),
			$this->wizard
		);
	}

	protected function addCssFiles()
	{
		$GLOBALS['TL_CSS']['tagsinput'] = 'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.css';

		if (TL_MODE == 'BE') {
			$GLOBALS['TL_CSS']['tagsinput-be'] = 'system/modules/tagsinput/assets/css/bootstrap-tagsinput-be.css';
			$GLOBALS['TL_CSS']['typeahead-be'] = 'system/modules/tagsinput/assets/css/typeahead-be.css';
		} else {
			$GLOBALS['TL_CSS']['tagsinput-fe'] = 'system/modules/tagsinput/assets/css/bootstrap-tagsinput-fe.css';
			$GLOBALS['TL_CSS']['typeahead-fe'] = 'system/modules/tagsinput/assets/css/typeahead-fe.css';
		}
	}

	protected function getRemoteOptionsFromQuery($strQuery)
	{
		$arrOptions = array();

		if ($this->activeRecord === null) {
			return $arrOptions;
		}

		if ((list($relTable, $relField, $relModelClass) = $this->getRelationData($this->arrConfiguration['remote']['foreignKey'])) === false) {
			return $arrOptions;
		}

		$strQueryField   = $this->arrConfiguration['remote']['queryField'];
		$strQueryPattern = $this->arrConfiguration['remote']['queryPattern'] ? str_replace('QUERY', $strQuery, $this->arrConfiguration['remote']['queryPattern']) : ('%' . $strQuery . '%');
		$arrFields       = $this->arrConfiguration['remote']['fields'];
		$intLimit        = $this->arrConfiguration['remote']['limit'] ?: 10;

		if (empty($arrFields) || !is_numeric($intLimit) || !$strQueryField) {
			return $arrOptions;
		}

		/** @var \Model $objEntities */
		$objEntities = $relModelClass::findBy(array("$relTable.$strQueryField LIKE ?"), $strQueryPattern, array('limit' => $intLimit));

		if ($objEntities === null) {
			return $arrOptions;
		}

		while ($objEntities->next())
		{
			$arrOptions[] = $this->generateOption($objEntities->id, null, $this->arrConfiguration['remote']['format'], $arrFields, $objEntities->current());
		}

		asort($arrOptions);

		return array_values($arrOptions);
	}

	public function generateAjax($strAction, \DataContainer $objDca)
	{
		// no tagsinput action --> return
		if (!$this->isValidAjaxActions($strAction)) {
			return;
		}

		$strField = \Input::post('name');

		\Controller::loadDataContainer($objDca->table);

		$objActiveRecord = \HeimrichHannot\Haste\Dca\General::getModelInstance($objDca->table, $objDca->id);

		if ($objActiveRecord === null) {
			$this->log('No active record for "' . $strField . '" found (possible SQL injection attempt)', __METHOD__, TL_ERROR);
			header('HTTP/1.1 400 Bad Request');
			die('Bad Request');
		}

		$strField             = \Input::post('name');
		$objDca->activeRecord = $objActiveRecord;
		$arrData              = $GLOBALS['TL_DCA'][$objDca->table]['fields'][$strField];

		if (!is_array($arrData)) {
			$this->log('No valid field configuration (dca) found for "' . $objDca->table . '.' . $strField . '" (possible SQL injection attempt)', __METHOD__, TL_ERROR);
			header('HTTP/1.1 400 Bad Request');
			die('Bad Request');
		}

		$return = '';

		switch ($strAction) {
			case static::ACTION_FETCH_REMOTE_OPTIONS:
				$objWidget = new \TagsInput(\Widget::getAttributesFromDca($arrData, $strField, $objActiveRecord->{$strField}, $strField, $this->strTable, $objDca));
				$return    = $objWidget->getRemoteOptionsFromQuery(\Input::post('query'));
				break;
		}

		die(json_encode($return));
	}

	protected function getOptions(array $arrValues = array())
	{
		$arrOptions = array();

		switch ($this->mode)
		{
			case static::MODE_REMOTE:
				\Controller::loadDataContainer($this->strTable);

				if ((list($relTable, $relField, $relModelClass) = $this->getRelationData($this->arrConfiguration['remote']['foreignKey'])) === false)
				{
					return $arrOptions;
				}

				/** @var \Model $objEntities */
				$objEntities = $relModelClass::findMultipleByIds($arrValues);

				if ($objEntities === null)
				{
					return $arrOptions;
				}

				$arrFields = $this->arrConfiguration['remote']['fields'];

				while ($objEntities->next())
				{
					$arrOption = $this->generateOption($objEntities->id, null, $this->arrConfiguration['remote']['format'], $arrFields, $objEntities->current());

					if($arrOption === false)
					{
						continue;
					}

					$arrOptions[] = $arrOption;
				}

				break;
			default:

				// add free input values from $this->varValue
				if($this->arrConfiguration['freeInput'] && !empty($this->varValue))
				{
					if(is_array($this->varValue))
					{
						foreach ($this->varValue as $value)
						{
							if(($arrOption = $this->generateOption($value, $value)) === false)
							{
								continue;
							}

							$arrOptions[] = $arrOption;
						}

						break;
					}

					if(($arrOption = $this->generateOption($this->varValue, $this->varValue)) !== false)
					{
						$arrOptions[] = $arrOption;
					}

					break;
				}

				// default: iterate over all options and trigger tags_callback
				if(is_array($this->arrOptions))
				{
					foreach ($this->arrOptions as $arrDefaultOption)
					{
						if(($arrOption = $this->generateOption($arrDefaultOption['value'], $arrDefaultOption['label'])) === false)
						{
							continue;
						}

						$arrOptions[] = $arrOption;
					}
				}

			break;
		}

		return $arrOptions;
	}

	protected function isValidAjaxActions($strAction)
	{
		return in_array(
			$strAction,
			array(
				static::ACTION_FETCH_REMOTE_OPTIONS,
			)
		);
	}

	/**
	 * Get relation table, field and model class, from a foreignKey
	 *
	 * @param $varValue The foreignKey as String (e.g tl_page.id)
	 *
	 * @return array|bool Return list with relation table, field and model class or false if no valid foreignKey
	 */
	protected function getRelationData($varValue)
	{
		$arrRelation = trimsplit('.', $varValue);

		if (is_array($arrRelation) && !$arrRelation[0])
		{
			return false;
		}

		$strTable = $arrRelation[0];
		$strField = $arrRelation[1];

		if (\HeimrichHannot\Haste\Util\StringUtil::startsWith($arrRelation[0], '%') && \HeimrichHannot\Haste\Util\StringUtil::endsWith($arrRelation[0], '%')) {
			$strField = str_replace('%', '', $arrRelation[0]);

			if (!$this->activeRecord->{$strField}) {
				return false;
			}

			$strTable = $this->activeRecord->{$strField};
		}

		$strModelClass = \Model::getClassFromTable($strTable);

		if (!class_exists($strModelClass)) {
			return false;
		}

		return array
		(
			$strTable,
			$strField,
			$strModelClass,
		);
	}

	/**
	 * Generate the option array by given configuration
	 *
	 * @param mixed  $varValue the 'value'
	 * @param string
	 * @param string $strFormat optional: Format string, for vsprintf()
	 * @param array  $arrFields optional: The field names from the model. Taken values from $objItem and put them into $strFormat vsprintf()
	 * @param \Model $objItem optional: The model data
	 *
	 * @return array The option as associative value / label array
	 */
	protected function generateOption($varValue, $strLabel = null, $strFormat = null, array $arrFields = array(), \Model $objItem = null)
	{
		$arrFieldValues = array();

		if($strFormat && !empty($arrFields) && $objItem !== null)
		{
			foreach ($arrFields as $strField)
			{
				$arrFieldValues[] = $objItem->{$strField};
			}

			$strLabel = html_entity_decode(vsprintf($strFormat, $arrFieldValues));
		}


		$arrOption = array
		(
			'value' => $varValue,
			'label' => $strLabel,
			'class' => 'label label-info',
			'title'	=> $varValue,
		);

		// Call tags_callback
		if (is_array($this->arrConfiguration['tags_callback']))
		{
			foreach ($this->arrConfiguration['tags_callback'] as $callback)
			{
				if (is_array($callback))
				{
					$this->import($callback[0]);
					$arrOption = $this->$callback[0]->$callback[1]($arrOption, $this->dataContainer);
				} elseif (is_callable($callback)) {
					$arrOption = $callback($arrOption, $this->dataContainer);
				}
			}
		}

		// check option after callback
		if (!is_array($arrOption) && !isset($arrOption['value']))
		{
			return false;
		}

		return $arrOption;
	}
}
