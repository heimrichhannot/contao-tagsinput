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

class FormTagsInput extends \TagsInput
{
	/**
	 * Submit user input
	 *
	 * @var boolean
	 */
	protected $blnSubmitInput = true;

	/**
	 * Add a for attribute
	 *
	 * @var boolean
	 */
	protected $blnForAttribute = true;

	/**
	 * Template
	 *
	 * @var string
	 */
	protected $strTemplate = 'form_tagsinput';


	/**
	 * Class
	 *
	 * @var string
	 */
	protected $strClass = 'tagsinput';


	/**
	 * The CSS class prefix
	 *
	 * @var string
	 */
	protected $strPrefix = 'widget widget-tagsinput';

	/**
	 * Check for a valid option (see #4383)
	 */
	public function validate()
	{
		// set values from options instead of label
		$varInput = $this->getPost($this->strName);

		if(!empty($varInput))
		{
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
		else
		{
			$this->varValue = $varInput;
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
			'<select name="%s" id="ctrl_%s" class="%s"%s>%s</select>',
			$this->strName,
			$this->strId,
			(($this->strClass != '') ? ' ' . $this->strClass : ''),
			$this->getAttributes(),
			implode('', $this->arrSelectedOptions)
		);
	}
}