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


/**
 * Constants
 */
define('TAGSINPUT_NEW_TAG_PREFIX', '#!nt&_');

/**
 * Back end form fields
 */
$GLOBALS['BE_FFL']['tagsinput'] = 'TagsInput';

/**
 * Front end form fields
 */
$GLOBALS['TL_FFL']['tagsinput'] = 'FormTagsInput';

/**
 * Javascript
 */
if (TL_MODE == 'BE')
{
	$GLOBALS['TL_JAVASCRIPT']['jquery'] = 'assets/jquery/core/' . $GLOBALS['TL_ASSETS']['JQUERY'] . '/jquery.min.js|static';
	$GLOBALS['TL_JAVASCRIPT']['jquery-noconflict'] = 'system/modules/tagsinput/assets/js/jquery-noconflict.js';
}

$GLOBALS['TL_JAVASCRIPT']['tagsinput'] = 'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput' . (!$GLOBALS['TL_CONFIG']['debugMode'] ? '.min' : '') . '.js|static';
$GLOBALS['TL_JAVASCRIPT']['typeahead'] = 'system/modules/tagsinput/assets/vendor/typeahead.js/dist/typeahead.bundle' . (!$GLOBALS['TL_CONFIG']['debugMode'] ? '.min' : '') . '.js|static';

$GLOBALS['TL_JAVASCRIPT']['tagsinput-be'] = 'system/modules/tagsinput/assets/js/jquery.tagsinput' . (!$GLOBALS['TL_CONFIG']['debugMode'] ? '.min' : '') . '.js|static';

/**
 * CSS
 */
$GLOBALS['TL_CSS']['tagsinput']        = 'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.css';

if(TL_MODE == 'BE')
{
	$GLOBALS['TL_CSS']['tagsinput-be']        = 'system/modules/tagsinput/assets/css/bootstrap-tagsinput-be.css';
	$GLOBALS['TL_CSS']['typeahead-be']        = 'system/modules/tagsinput/assets/css/typeahead-be.css';
} else {
	$GLOBALS['TL_CSS']['tagsinput-fe']        = 'system/modules/tagsinput/assets/css/bootstrap-tagsinput-fe.css';
	$GLOBALS['TL_CSS']['typeahead-fe']        = 'system/modules/tagsinput/assets/css/typeahead-fe.css';
}
