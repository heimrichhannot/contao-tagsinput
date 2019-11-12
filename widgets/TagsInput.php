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

    /**
     * @var array
     */
    protected $arrOptionsAll = [];

    protected $arrTags = [];

    protected $arrHighlights = [];

    protected $arrSelectedOptions = [];

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
            $varInput = [$varInput];
        }

        if (!empty($varInput)) {
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
        $values    = [];
        $freeInput = $this->canInputFree();

        if (!is_array($varValue)) {
            $varValue = [$varValue];
        }

        // add remote options
        $this->arrOptions = $this->getOptions($varValue);

        foreach ($varValue as $key => $strTag) {
            $blnFound = false;

            // convert html entities back, otherwise compare for html entities will fail and tag never added
            $strTag = \Input::decodeEntities($strTag);

            foreach ($this->arrOptions as $v) {
                // set value for existing tags
                if (array_key_exists('value', $v)) {
                    // check options against numeric key or string value
                    if ($strTag == $v['value'] || $strTag == $v['label']) {
                        if ($this->multiple) {
                            $values[$key] = $v['value'];
                        } else {
                            $values = $v['value'];
                        }

                        $blnFound = true;
                        break;
                    }
                }
            }


            if (!$blnFound && ($intId = $this->addNewTag($strTag)) > 0 || $freeInput) {
                $value = ($freeInput && !$intId) ? $strTag : $intId;

                if ($this->multiple) {
                    $values[$key] = $value;
                } else {
                    $values = $value;
                }

                // add new value to options
                $this->arrOptions[] = ['value' => $value, 'label' => $strTag];
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
        if ($this->multiple) {
            $this->addAttribute('multiple', true);

            if ($this->maxTags > 1) {
                $this->addAttribute('data-max-tags', $this->maxTags);
            }

            $this->strName .= '[]';
        } else {
            $this->addAttribute('data-max-tags', 1);
        }

        if ($this->maxChars > 0) {
            $this->addAttribute('data-max-chars', $this->maxChars);
        }

        if ($this->trimValue) {
            $this->addAttribute('data-trim-value', 1);
        }

        if ($this->allowDuplicates) {
            $this->addAttribute('data-allow-duplicates', 1);
        }

        $this->addAttribute('data-limit', $this->limit > 0 ? $this->limit : 5);

        if ($this->submitOnChange) {
            unset($this->arrAttributes['onchange']);
            $this->addAttribute('data-submitonchange', true);
        }

        $arrHighlights = [];

        if ($this->highlight !== false) {
            if (is_array($this->highlightOptions)) {
                $arrHighlights = $this->highlightOptions;
            }

            if (is_array($this->highlightOptionsCallback)) {
                foreach ($this->highlightOptionsCallback as $callback) {
                    if (is_array($callback)) {
                        $this->import($callback[0]);
                        $arrHighlights = $this->{$callback[0]}->{$callback[1]}($arrHighlights, $this->arrOptions, $this);
                    } elseif (is_callable($callback)) {
                        $arrHighlights = $callback($arrHighlights, $this->arrOptions, $this);
                    }
                }
            }
        }

        $this->arrOptionsAll = $this->arrOptions;

        // add remote options or freeInput options
        $this->arrOptions = $this->getOptions(deserialize($this->varValue, true));

        // Add an empty option (XHTML) if there are none
        if (empty($this->arrOptions)) {
            $this->arrOptions = [['value' => '', 'label' => '-']];
        }

        // set highlights if not in freeInput mode
        if (!empty($arrHighlights) && $this->canInputFree()) {
            foreach ($arrHighlights as $strKey => $varValue) {
                $this->arrHighlights[] = $this->generateOption($varValue, $varValue);
            }
        }


        foreach ($this->arrOptions as $strKey => $arrOption) {
            if (isset($arrOption['value'])) {
                $this->arrTags[] = $arrOption;

                // remove highlights that are not valid when not in freeInput mode
                if (!empty($arrHighlights) && !$this->canInputFree() && ($idx = array_search($arrOption['value'], $arrHighlights)) !== false) {
                    $this->arrHighlights[] = $arrOption;
                }

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

        if (!empty($this->arrHighlights)) {
            $this->addAttribute('data-highlight', 1);
            $this->addAttribute('data-highlights', htmlspecialchars(json_encode($this->arrHighlights), ENT_QUOTES, 'UTF-8'));
        }

        $this->addAttribute('data-items', htmlspecialchars(json_encode($this->arrTags), ENT_QUOTES, 'UTF-8'));

        $this->addAttribute('data-free-input', ($this->canInputFree() !== false ? 'true' : 'false'));

        $strMode = $this->arrConfiguration['mode'] ?: static::MODE_LOCAL;

        $this->addAttribute('data-mode', $strMode);

        switch ($strMode) {
            case static::MODE_REMOTE:
                $this->addAttribute(
                    'data-post-data',
                    htmlspecialchars(
                        json_encode(
                            [
                                'action'        => static::ACTION_FETCH_REMOTE_OPTIONS,
                                'name'          => $this->strId,
                                'REQUEST_TOKEN' => \RequestToken::get(),
                            ]
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
        $strWidget = sprintf(
            '<select name="%s" id="ctrl_%s" class="%s"%s>%s</select>%s',
            $this->strName,
            $this->strId,
            trim(($this->strClass != '') ? ' ' . $this->strClass : ''),
            $this->getAttributes(),
            implode('', $this->arrSelectedOptions),
            $this->wizard
        );

        if ($this->arrConfiguration['showTagList']) {
            $intClassCount = $this->arrConfiguration['tagListWeightClassCount'] ?: 6;

            $strTagList = '<ul class="tt-tag-list" data-class-count="' . $intClassCount . '">';

            if (isset($this->arrConfiguration['option_weights']) || isset($this->arrConfiguration['option_weights_callback'])) {
                $arrTagWeights = \HeimrichHannot\Haste\Dca\General::getConfigByArrayOrCallbackOrFunction(
                    (array)$this->arrConfiguration, 'option_weights', [$this->objDca]);

                $intMaxCount = 0;

                foreach ($arrTagWeights as $strTag => $intCount) {
                    if ($intCount > $intMaxCount) {
                        $intMaxCount = $intCount;
                    }
                }

                foreach ($arrTagWeights as $strTag => $intCount) {
                    $strTagList .= '<li><a class="' . static::getTagSizeClass($intCount, $intMaxCount, $intClassCount) .
                        '" href="#"><span>' . $strTag . '</span> (' . $intCount . ')</a></li>';
                }
            } else {
                foreach ($this->arrOptionsAll as $arrTag) {
                    $strTagList .= '<li><a href="#">' . $arrTag['value'] . '</a></li>';
                }
            }

            $strTagList .= '</ul>';

            $strWidget = $strTagList . $strWidget;
        }

        return $strWidget;
    }

    public static function getTagSizeClass($intCount, $intMaxCount, $intClassCount)
    {
        for ($i = $intClassCount - 1; $i >= 0; $i--) {
            if ($intCount >= $i * $intMaxCount / $intClassCount) {
                return 'size' . ($i + 1);
            }
        }
    }

    protected function getRemoteOptionsFromQuery($strQuery)
    {
        $arrOptions = [];

        if ($this->activeRecord === null) {
            return $arrOptions;
        }

        // get query options from relation table
        if (($arrRelationData = $this->getRelationData($this->arrConfiguration['remote']['foreignKey'])) !== false) {

            return $this->getRemoteOptionsFromRelationTable($strQuery, $arrRelationData);
        }

        // get query options from options or options_callback label value
        if (is_array($this->arrOptions)) {
            return $this->getRemoteOptionsFromLocalOptions($strQuery);
        }

        return $arrOptions;
    }

    protected function getRemoteOptionsFromLocalOptions($strQuery)
    {
        $arrOptions = [];

        if (!is_array($this->arrOptions)) {
            return $arrOptions;
        }

        $strQueryPattern =
            $this->arrConfiguration['remote']['queryPattern'] ? str_replace('QUERY', $strQuery, $this->arrConfiguration['remote']['queryPattern']) : ('%' . $strQuery . '%');
        $strQueryPattern = str_replace('%', '.*', preg_quote($strQueryPattern, '/'));
        $intLimit        = $this->arrConfiguration['remote']['limit'] ?: 10;
        $i               = 0;

        foreach ($this->arrOptions as $arrLocalOption) {
            if (!isset($arrLocalOption['label'])) {
                continue;
            }

            if (((bool)preg_match("/^{$strQueryPattern}$/i", $arrLocalOption['label'])) === false) {
                continue;
            }

            $arrOption = $this->generateOption($arrLocalOption['value'], $arrLocalOption['label']);

            if ($arrOption === false) {
                continue;
            }

            $arrOptions[] = $arrOption;
            $i++;

            if ($i + 1 == $intLimit) {
                break;
            }
        }

        asort($arrOptions);

        return $arrOptions;
    }

    protected function getRemoteOptionsFromRelationTable($strQuery, array $arrRelationData)
    {
        $arrOptions = [];

        list($relTable, $relField, $relModelClass) = $arrRelationData;

        $strQueryField   = $this->arrConfiguration['remote']['queryField'];
        $strQueryPattern =
            $this->arrConfiguration['remote']['queryPattern'] ? str_replace('QUERY', $strQuery, $this->arrConfiguration['remote']['queryPattern']) : ('%' . $strQuery . '%');
        $arrFields       = $this->arrConfiguration['remote']['fields'];
        $intLimit        = $this->arrConfiguration['remote']['limit'] ?: 10;

        if (empty($arrFields) || !is_numeric($intLimit) || !$strQueryField) {
            return $arrOptions;
        }

        /** @var \Model $objEntities */
        $objEntities = $relModelClass::findBy(["$relTable.$strQueryField LIKE ?"], $strQueryPattern, ['limit' => $intLimit]);

        if ($objEntities === null) {
            return $arrOptions;
        }

        while ($objEntities->next()) {
            $arrOption = $this->generateOption($objEntities->id, null, $this->arrConfiguration['remote']['format'], $arrFields, $objEntities->current());

            if ($arrOption === false) {
                continue;
            }

            $arrOptions[] = $arrOption;
        }

        asort($arrOptions);

        return $arrOptions;
    }

    public function generateAjax($strAction, \DataContainer $objDca)
    {
        // no tagsinput action --> return
        if (!$this->isValidAjaxActions($strAction)) {
            return;
        }

        $strField = $objDca->field = \Input::post('name');

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
                $return    = array_values($objWidget->getRemoteOptionsFromQuery(\Input::post('query')));
                break;
        }

        die(json_encode($return));
    }

    protected function getOptions(array $arrValues = [])
    {
        $arrChoices = [];

        switch ($this->mode) {
            case static::MODE_REMOTE:
                \Controller::loadDataContainer($this->strTable);

                // get query options from relation table
                if (($arrRelationData = $this->getRelationData($this->arrConfiguration['remote']['foreignKey'])) !== false) {
                    return $this->getActiveRemoteOptionsFromRelationTable(array_filter($arrValues), $arrRelationData);
                }

                // get query options from options or options_callback label value
                if (is_array($this->arrOptions)) {
                    return $this->getActiveRemoteOptionsFromLocalOptions($arrValues);
                }

                break;
            default:
                // add free input values from $this->varValue
                if ($this->arrConfiguration['freeInput'] && !empty($this->varValue)) {
                    if (is_array($this->varValue)) {
                        foreach ($this->varValue as $value) {
                            if (($arrOption = $this->generateOption($value, $value)) === false) {
                                continue;
                            }

                            $arrChoices[] = $arrOption;
                        }

                        $arrChoices = $this->addDefaultOptions($arrChoices);

                        break;
                    }

                    if (($arrOption = $this->generateOption($this->varValue, $this->varValue)) !== false) {
                        $arrChoices[] = $arrOption;
                    }

                    $arrChoices = $this->addDefaultOptions($arrChoices);
                    break;
                }

                $arrChoices = $this->addDefaultOptions($arrChoices);

                break;
        }

        return $arrChoices;
    }

    protected function addDefaultOptions(array $arrChoices = [])
    {
        if (!is_array($this->arrOptions)) {
            return $arrChoices;
        }

        $i = is_array($this->varValue) ? count($this->varValue) : 0; // add new values after last varValue index
        $arrSkip = [];

        foreach ($this->arrOptions as $arrDefaultOption) {
            if (($arrOption = $this->generateOption($arrDefaultOption['value'], $arrDefaultOption['label'])) === false) {
                continue;
            }

            // default options should be sorted by given value order if value is set
            if (!empty($this->varValue)) {
                if (is_array($this->varValue) && ($pos = array_search($arrDefaultOption['value'], $this->varValue)) !== false && !in_array($pos, $arrSkip)) {
                    $arrChoices[$pos] = $arrOption;
                    $arrSkip[] = $pos;
                    continue;
                }
            }

            // store value as key for sorting by $this->varValue or if single varValue
            $arrChoices[$i] = $arrOption;
            $i++;

        }

        // sort by keys
        ksort($arrChoices);

        return $arrChoices;
    }

    protected function getActiveRemoteOptionsFromLocalOptions(array $arrValues = [])
    {
        $arrOptions     = [];
        $arrLocalValues = [];

        foreach ($this->arrOptions as $arrLocalOption) {
            if (!isset($arrLocalOption['value'])) {
                continue;
            }

            // restore postion from arrValues position
            if (($pos = array_search($arrLocalOption['value'], $arrValues)) === false) {
                continue;
            }

            $arrOption = $this->generateOption($arrLocalOption['value'], $arrLocalOption['label']);

            if ($arrOption === false) {
                continue;
            }

            $arrLocalValues[] = $arrLocalOption['value'];
            $arrOptions[$pos] = $arrOption;
        }

        if ($this->canInputFree()) {
            $arrFreeValues = array_diff($arrValues, $arrLocalValues);

            if (is_array($arrFreeValues)) {
                foreach ($arrFreeValues as $arrFreeValue) {
                    $arrOption = $this->generateOption($arrFreeValue, $arrFreeValue);

                    // restore postion from arrValues position
                    if (($pos = array_search($arrFreeValue, $arrValues)) === false) {
                        continue;
                    }

                    if ($arrOption === false) {
                        continue;
                    }

                    $arrOptions[$pos] = $arrOption;
                }
            }
        }

        ksort($arrOptions);

        return $arrOptions;
    }


    protected function getActiveRemoteOptionsFromRelationTable(array $arrValues, array $arrRelationData)
    {
        $arrOptions = [];

        list($relTable, $relField, $relModelClass) = $arrRelationData;

        /** @var \Model $objEntities */
        $objEntities = $relModelClass::findMultipleByIds($arrValues);

        if ($objEntities === null) {
            return $arrOptions;
        }

        $arrFields = $this->arrConfiguration['remote']['fields'];

        while ($objEntities->next()) {
            $arrOption = $this->generateOption($objEntities->id, null, $this->arrConfiguration['remote']['format'], $arrFields, $objEntities->current());

            if ($arrOption === false) {
                continue;
            }

            $arrOptions[] = $arrOption;
        }

        return $arrOptions;
    }


    protected function isValidAjaxActions($strAction)
    {
        return in_array(
            $strAction,
            [
                static::ACTION_FETCH_REMOTE_OPTIONS,
            ]
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

        if (is_array($arrRelation) && !$arrRelation[0]) {
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

        return [
            $strTable,
            $strField,
            $strModelClass,
        ];
    }

    /**
     * Generate the option array by given configuration
     *
     * @param mixed $varValue the 'value'
     * @param        string
     * @param string $strFormat optional: Format string, for vsprintf()
     * @param array $arrFields optional: The field names from the model. Taken values from $objItem and put them into $strFormat vsprintf()
     * @param \Model $objItem optional: The model data
     *
     * @return array The option as associative value / label array
     */
    protected function generateOption($varValue, $strLabel = null, $strFormat = null, array $arrFields = [], \Model $objItem = null)
    {
        $arrFieldValues = [];

        if ($strFormat && !empty($arrFields) && $objItem !== null) {
            foreach ($arrFields as $strField) {
                $arrFieldValues[] = $objItem->{$strField};
            }

            $strLabel = html_entity_decode(vsprintf($strFormat, $arrFieldValues));
        }


        $arrOption = [
            'value' => $varValue,
            'label' => $strLabel,
            'class' => 'label label-info',
            'title' => $varValue,
        ];

        // Call tags_callback
        if (is_array($this->arrConfiguration['tags_callback'])) {
            foreach ($this->arrConfiguration['tags_callback'] as $callback) {
                if (is_array($callback)) {
                    $this->import($callback[0]);
                    $arrOption = $this->{$callback[0]}->{$callback[1]}($arrOption, $this->dataContainer);
                } elseif (is_callable($callback)) {
                    $arrOption = $callback($arrOption, $this->dataContainer);
                }
            }
        }

        // check option after callback
        if (!is_array($arrOption) && !isset($arrOption['value'])) {
            return false;
        }

        return $arrOption;
    }

    protected function canInputFree()
    {
        $blnCheck = false;

        if ($this->arrConfiguration['freeInput']) {
            $blnCheck = true;
        }

        switch ($this->mode) {
            case static::MODE_REMOTE:
                // disable free input if no relation data isset
                if (($this->getRelationData($this->arrConfiguration['remote']['foreignKey'])) === false) {
                    $blnCheck = false;
                }

                // disable if no save_tags configuration isset
                if (($arrSaveConfig = $this->arrConfiguration['save_tags']) === null && !isset($arrSaveConfig['table'])) {
                    $blnCheck = false;
                }

                // support free input for local remote options
                if (!$blnCheck && is_array($this->arrOptions)) {
                    $blnCheck = true;
                }

                break;
        }

        return $blnCheck;
    }
}
