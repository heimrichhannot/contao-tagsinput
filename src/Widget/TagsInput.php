<?php
/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2023 Heimrich & Hannot GmbH
 *
 * @package tagsinput
 * @author  Rico Kaltofen <r.kaltofen@heimrich-hannot.de>
 * @license http://www.gnu.org/licences/lgpl-3.0.html LGPL
 */

namespace HeimrichHannot\TagsInput\Widget;

use Contao\Controller;
use Contao\Database;
use Contao\DataContainer;
use Contao\Input;
use Contao\Model;
use Contao\Model\Collection;
use Contao\RequestToken;
use Contao\StringUtil;
use Contao\Widget;

class TagsInput extends Widget
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
                $this->arrOptions = StringUtil::deserialize($varValue);
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
    protected function isSelected($arrOption): string
    {
        if (empty($this->varValue) && empty($_POST) && ($arrOption['default'] ?? null)) {
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

    /**
     * @param array|int|mixed $varValue
     * @return array|int|mixed
     */
    protected function setValuesByOptions($varValue)
    {
        $values    = [];
        $freeInput = $this->canInputFree();

        if (!is_array($varValue)) {
            $varValue = [$varValue];
        }

        // add remote options
        $this->arrOptions = $this->getOptions($varValue);

        foreach ($varValue as $key => $tag)
        {
            $found = false;

            // convert html entities back, otherwise compare for html entities will fail and tag never added
            $tag = Input::decodeEntities($tag);

            foreach ($this->arrOptions as $v)
            {
                // set value for existing tags
                if (array_key_exists('value', $v))
                {
                    // check options against numeric key or string value
                    if ($tag == $v['value'] || $tag == $v['label'])
                    {
                        if ($this->multiple) {
                            $values[$key] = $v['value'];
                        } else {
                            $values = $v['value'];
                        }

                        $found = true;
                        break;
                    }
                }
            }

            $intId = $this->addNewTag($tag);

            if (!$found && ($intId !== null) || $freeInput)
            {
                $val = ($freeInput && !$intId) ? $tag : $intId;

                if ($this->multiple) {
                    $values[$key] = $val;
                } else {
                    $values = $val;
                }

                // add new value to options
                $this->arrOptions[] = ['value' => $val, 'label' => $tag];
            }
        }

        return $values;
    }

    /**
     * Add a new tag
     */
    protected function addNewTag(string $tag): ?int
    {
        if ($tag == '') {
            return null;
        }

        $arrSaveConfig = $this->arrConfiguration['save_tags'] ?? null;
        if ($arrSaveConfig === null || !isset($arrSaveConfig['table'])) {
            return null;
        }

        $table = $arrSaveConfig['table'];
        $modelClass = Model::getClassFromTable($arrSaveConfig['table']);

        if (!class_exists($modelClass))
        {
            $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalidTagsModel'], $table));
            return null;
        }

        $strTagField = $arrSaveConfig['tagField'];

        if (!Database::getInstance()->fieldExists($strTagField, $arrSaveConfig['table']))
        {
            $this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['invalidTagsField'], $strTagField, $table));
            return null;
        }

        $objModel         = new $modelClass();
        $objModel->tstamp = 0;

        // overwrite model with defaults from dca
        if (is_array($arrSaveConfig['defaults'] ?? null)) {
            $objModel->setRow($arrSaveConfig['defaults']);
        }

        $objModel->{$strTagField} = $tag;
        $objModel->save();

        return $objModel->id;
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
            foreach ($arrHighlights as $varValue) {
                $this->arrHighlights[] = $this->generateOption($varValue, $varValue);
            }
        }

        foreach ($this->arrOptions as $arrOption) {
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
                        (!empty($arrOption['class']) ? 'class="' . $arrOption['class'] . '"' : ''),
                        (!empty($arrOption['target']) ? 'data-target="' . $arrOption['class'] . '"' : ''),
                        $arrOption['label']
                    );
                }
            }
        }

        $this->arrHighlights = array_map(function ($v) {
            return $v !== null;
        }, $this->arrHighlights);

        if (!empty($this->arrHighlights)) {
            $this->addAttribute('data-highlight', 1);
            $this->addAttribute('data-highlights', htmlspecialchars(json_encode($this->arrHighlights), ENT_QUOTES, 'UTF-8'));
        }

        $this->addAttribute('data-items', htmlspecialchars(json_encode($this->arrTags), ENT_QUOTES, 'UTF-8'));

        $this->addAttribute('data-free-input', ($this->canInputFree() !== false ? 'true' : 'false'));

        $strMode = !empty($this->arrConfiguration['mode']) ? $this->arrConfiguration['mode'] : static::MODE_LOCAL;

        $this->addAttribute('data-mode', $strMode);

        if ($strMode === static::MODE_REMOTE) {
            $this->addAttribute(
                'data-post-data',
                htmlspecialchars(
                    json_encode(
                        [
                            'action' => static::ACTION_FETCH_REMOTE_OPTIONS,
                            'name' => $this->strId,
                            'REQUEST_TOKEN' => RequestToken::get(),
                        ]
                    )
                )
            );
        }

        if ($this->arrConfiguration['placeholder'] ?? false) {
            $this->addAttribute('data-placeholder', $this->arrConfiguration['placeholder']);
        }
    }

    public static function loadAssets()
    {
        $bundle = 'bundles/heimrichhannotcontaotagsinput';

        $GLOBALS['TL_JAVASCRIPT']['tagsinput-be'] = "$bundle/assets/contao-tagsinput-be.js";
        $GLOBALS['TL_CSS']['tagsinput-be-theme'] = "$bundle/assets/contao-tagsinput-be-theme.css";

        // JS: ['tagsinput']    = 'assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js|static';
        // JS: ['sortable']     = 'assets/vendor/Sortable/Sortable.min.js|static';
        // JS: ['typeahead']    = 'assets/vendor/corejs-typeahead/dist/typeahead.bundle.min.js|static';
        // JS: ['tagsinput-be'] = 'assets/js/jquery.tagsinput.min.js|static';

        // CSS: ['tagsinput'] = 'assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.css';
        // CSS: ['tagsinput-be'] = 'assets/css/bootstrap-tagsinput-be.css';
        // CSS: ['typeahead-be'] = 'assets/css/typeahead-be.css';

        if (version_compare(\VERSION, '5.0', '<')) {
            $GLOBALS['TL_CSS']['tagsinput-be-contao4-theme'] = "$bundle/assets/contao-tagsinput-be-contao4-theme.css";
        }
    }

    /**
     * Generate the widget and return it as string
     *
     * @return string
     */
    public function generate()
    {
        static::loadAssets();

        $this->prepare();
        $classNames = trim(($this->strClass ?? null) ?: '');
        $selectedOptions = implode('', $this->arrSelectedOptions);
        $attributes = trim($this->getAttributes());
        $attributes = $attributes ? ' ' . $attributes : '';

        $strWidget = sprintf(
            '<select name="%s" id="ctrl_%s" class="%s"%s>%s</select>%s',
            $this->strName,
            $this->strId,
            $classNames,
            $attributes,
            $selectedOptions,
            $this->wizard
        );

        /**
         * @param array $arr
         * @param string|mixed $property
         * @param array $args
         * @return mixed|null
         */
        $getConfigByArrayOrCallbackOrFunction = function (array $arr, $property, array $args = [])
        {
            if (isset($arr[$property])) {
                return $arr[$property];
            }

            $callback = $arr[$property . '_callback'] ?? null;

            if (is_array($callback))
            {
                $instance = Controller::importStatic($callback[0]);
                return call_user_func_array([$instance, $callback[1]], $args);
            }

            if (is_callable($callback))
            {
                return call_user_func_array($callback, $args);
            }

            return null;
        };

        if ($this->arrConfiguration['showTagList'] ?? false)
        {
            $classCount = $this->arrConfiguration['tagListWeightClassCount'] ?? 6;

            $strTagList = "<ul class=\"tt-tag-list\" data-class-count=\"$classCount\">";

            if (isset($this->arrConfiguration['option_weights'])
                || isset($this->arrConfiguration['option_weights_callback']))
            {
                $tagWeights = $getConfigByArrayOrCallbackOrFunction(
                    (array)$this->arrConfiguration, 'option_weights', [$this->objDca]
                );

                $maxCount = 0;

                foreach ($tagWeights as $count) {
                    if ($count > $maxCount) {
                        $maxCount = $count;
                    }
                }

                foreach ($tagWeights as $strTag => $count) {
                    $strTagList .= '<li><a class="' . static::getTagSizeClass($count, $maxCount, $classCount) .
                        '" href="#"><span>' . $strTag . '</span> (' . $count . ')</a></li>';
                }
            }
            else
            {
                foreach ($this->arrOptionsAll as $arrTag) {
                    $strTagList .= '<li><a href="#">' . $arrTag['value'] . '</a></li>';
                }
            }

            $strTagList .= '</ul>';

            $strWidget = $strTagList . $strWidget;
        }

        return $strWidget;
    }

    public static function getTagSizeClass($intCount, $intMaxCount, $intClassCount): string
    {
        for ($i = $intClassCount - 1; $i >= 0; $i--) {
            if ($intCount >= $i * $intMaxCount / $intClassCount) {
                return 'size' . ($i + 1);
            }
        }
        return '';
    }

    protected function getRemoteOptionsFromQuery($strQuery): array
    {
        $arrOptions = [];

        if ($this->activeRecord === null) {
            return $arrOptions;
        }

        // get query options from relation table
        $arrRelationData = $this->getRelationData($this->arrConfiguration['remote']['foreignKey']);
        if ($arrRelationData !== false)
        {
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
            if ($arrOption === null) {
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

    protected function getRemoteOptionsFromRelationTable($strQuery, array $arrRelationData): array
    {
        $options = [];

        /** @var class-string<Model> $relModelClass */
        list($relTable, $relField, $relModelClass) = $arrRelationData;

        $strQueryField   = $this->arrConfiguration['remote']['queryField'];
        $strQueryPattern = $this->arrConfiguration['remote']['queryPattern']
            ? str_replace('QUERY', $strQuery, $this->arrConfiguration['remote']['queryPattern'])
            : ('%' . $strQuery . '%');
        $arrFields       = $this->arrConfiguration['remote']['fields'];
        $intLimit        = $this->arrConfiguration['remote']['limit'] ?: 10;

        if (empty($arrFields) || !is_numeric($intLimit) || !$strQueryField) {
            return $options;
        }

        /** @var Collection $entities */
        $entities = $relModelClass::findBy(["$relTable.$strQueryField LIKE ?"], $strQueryPattern, ['limit' => $intLimit]);

        if ($entities === null) {
            return $options;
        }

        while ($entities->next())
        {
            $option = $this->generateOption($entities->id, null, $this->arrConfiguration['remote']['format'], $arrFields, $entities->current());

            if ($option === null) {
                continue;
            }

            $options[] = $option;
        }

        asort($options);

        return $options;
    }

    public function generateAjax($strAction, DataContainer $objDca)
    {
        // no tagsinput action --> return
        if (!$this->isValidAjaxActions($strAction)) {
            return;
        }

        $strField = $objDca->field = Input::post('name');

        Controller::loadDataContainer($objDca->table);

        $modelClass = Model::getClassFromTable($objDca->table);
        $objActiveRecord = class_exists($modelClass) ? $modelClass::findByPk($objDca->id) : null;

        if ($objActiveRecord === null) {
            $this->log('No active record for "' . $strField . '" found (possible SQL injection attempt)', __METHOD__, TL_ERROR);
            header('HTTP/1.1 400 Bad Request');
            die('Bad Request');
        }

        $strField             = Input::post('name');
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
                $objWidget = new TagsInput(static::getAttributesFromDca($arrData, $strField, $objActiveRecord->{$strField}, $strField, $this->strTable, $objDca));
                $return    = array_values($objWidget->getRemoteOptionsFromQuery(Input::post('query')));
                break;
        }

        die(json_encode($return));
    }

    protected function getOptions(array $arrValues = [])
    {
        $arrChoices = [];

        switch ($this->mode) {
            case static::MODE_REMOTE:
                Controller::loadDataContainer($this->strTable);

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
                if (!$this->arrConfiguration['freeInput'] || empty($this->varValue)) {
                    $arrChoices = $this->addDefaultOptions($arrChoices);
                    break;
                }

                // add free input values from $this->varValue

                if (is_array($this->varValue))
                {
                    foreach ($this->varValue as $value)
                    {
                        $arrOption = $this->generateOption($value, $value);
                        if ($arrOption !== null) {
                            $arrChoices[] = $arrOption;
                        }
                    }

                    $arrChoices = $this->addDefaultOptions($arrChoices);

                    break;
                }

                $arrOption = $this->generateOption($this->varValue, $this->varValue);
                if ($arrOption !== null) {
                    $arrChoices[] = $arrOption;
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

        foreach ($this->arrOptions as $arrDefaultOption)
        {
            $option = $this->generateOption($arrDefaultOption['value'], $arrDefaultOption['label']);
            if ($option === null) {
                continue;
            }

            // default options should be sorted by given value order if value is set
            if (!empty($this->varValue) && is_array($this->varValue))
            {
                $pos = array_search($arrDefaultOption['value'], $this->varValue);
                if ($pos !== false && !in_array($pos, $arrSkip))
                {
                    $arrChoices[$pos] = $option;
                    $arrSkip[] = $pos;
                    continue;
                }
            }

            // store value as key for sorting by $this->varValue or if single varValue
            $arrChoices[$i] = $option;
            $i++;

        }

        // sort by keys
        ksort($arrChoices);

        return $arrChoices;
    }

    protected function getActiveRemoteOptionsFromLocalOptions(array $values = []): array
    {
        $options = [];
        $localValues = [];

        foreach ($this->arrOptions as $localOption)
        {
            if (!isset($localOption['value'])) {
                continue;
            }

            // restore postion from values position
            $pos = array_search($localOption['value'], $values);
            if ($pos === false) {
                continue;
            }

            $option = $this->generateOption($localOption['value'], $localOption['label']);
            if ($option === null) {
                continue;
            }

            $localValues[] = $localOption['value'];
            $options[$pos] = $option;
        }

        if ($this->canInputFree())
        {
            $freeValues = array_diff($values, $localValues);

            foreach ($freeValues as $freeValue)
            {
                // restore postion from arrValues position
                $pos = array_search($freeValue, $values);
                if ($pos === false) {
                    continue;
                }

                $option = $this->generateOption($freeValue, $freeValue);
                if ($option === null) {
                    continue;
                }

                $options[$pos] = $option;
            }
        }

        ksort($options);

        return $options;
    }


    protected function getActiveRemoteOptionsFromRelationTable(array $arrValues, array $arrRelationData)
    {
        $options = [];

        list($relTable, $relField, $relModelClass) = $arrRelationData;

        /** @var Collection $objEntities */
        $objEntities = $relModelClass::findMultipleByIds($arrValues);

        if ($objEntities === null) {
            return $options;
        }

        $arrFields = $this->arrConfiguration['remote']['fields'];

        while ($objEntities->next())
        {
            $arrOption = $this->generateOption(
                $objEntities->id,
                null,
                $this->arrConfiguration['remote']['format'],
                $arrFields,
                $objEntities->current()
            );

            if ($arrOption === null) {
                continue;
            }

            $options[] = $arrOption;
        }

        return $options;
    }


    protected function isValidAjaxActions($strAction): bool
    {
        return $strAction === static::ACTION_FETCH_REMOTE_OPTIONS;
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
        $arrRelation = StringUtil::trimsplit('.', $varValue);

        if (is_array($arrRelation) && !$arrRelation[0]) {
            return false;
        }

        $strTable = $arrRelation[0];
        $strField = $arrRelation[1];

        if (preg_match("/^%.*%$/", $strTable))
        {
            $strField = str_replace('%', '', $strTable);

            if (!$this->activeRecord->{$strField}) {
                return false;
            }

            $strTable = $this->activeRecord->{$strField};
        }

        $strModelClass = Model::getClassFromTable($strTable);

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
    protected function generateOption(
        $varValue,
        $strLabel = null,
        $strFormat = null,
        array $arrFields = [],
        Model $objItem = null
    ): ?array
    {
        $arrFieldValues = [];

        if ($strFormat && !empty($arrFields) && $objItem !== null)
        {
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
        if (is_array($this->arrConfiguration['tags_callback'] ?? null)) {
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
        if (!is_array($arrOption) || !isset($arrOption['value']))
        {
            return null;
        }

        return $arrOption;
    }

    protected function canInputFree(): bool
    {
        $hasFreeInput = false;

        if ($this->arrConfiguration['freeInput']) {
            $hasFreeInput = true;
        }

        if ($this->mode === static::MODE_REMOTE) {
            // disable free input if no relation data isset
            if ($this->getRelationData($this->arrConfiguration['remote']['foreignKey']) === false) {
                $hasFreeInput = false;
            }

            // disable if no save_tags configuration isset
            $arrSaveConfig = $this->arrConfiguration['save_tags'] ?? null;
            if ($arrSaveConfig === null && !isset($arrSaveConfig['table'])) {
                $hasFreeInput = false;
            }

            // support free input for local remote options
            if (!$hasFreeInput && is_array($this->arrOptions)) {
                $hasFreeInput = true;
            }
        }

        return $hasFreeInput;
    }
}

class_alias(TagsInput::class, 'TagsInput');
