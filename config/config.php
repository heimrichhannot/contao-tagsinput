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
    $strJQueryPath = version_compare(VERSION, '4.0', '<') ?
        'assets/jquery/core/' . $GLOBALS['TL_ASSETS']['JQUERY'] . '/jquery.min.js' :
        'assets/jquery/js/jquery.min.js';

    $GLOBALS['TL_JAVASCRIPT']['jquery']            = $strJQueryPath;
    $GLOBALS['TL_JAVASCRIPT']['jquery-noconflict'] = 'system/modules/tagsinput/assets/js/jquery-noconflict.js';

    $GLOBALS['TL_JAVASCRIPT']['tagsinput']    = 'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js';
    $GLOBALS['TL_JAVASCRIPT']['sortable']     = 'system/modules/tagsinput/assets/vendor/Sortable/Sortable.min.js';
    $GLOBALS['TL_JAVASCRIPT']['typeahead']    = 'system/modules/tagsinput/assets/vendor/corejs-typeahead/dist/typeahead.bundle.min.js';
    $GLOBALS['TL_JAVASCRIPT']['tagsinput-be'] = 'system/modules/tagsinput/assets/js/jquery.tagsinput.min.js';

    $GLOBALS['TL_CSS']['tagsinput'] = 'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.css';

    $GLOBALS['TL_CSS']['tagsinput-be'] = 'system/modules/tagsinput/assets/css/bootstrap-tagsinput-be.css';
    $GLOBALS['TL_CSS']['typeahead-be'] = 'system/modules/tagsinput/assets/css/typeahead-be.css';
}


$GLOBALS['TL_COMPONENTS']['bs.tagsinput'] = [
    'js'  => [
        'files' => [
            'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js|static',
            'system/modules/tagsinput/assets/vendor/Sortable/Sortable.min.js|static',
            'system/modules/tagsinput/assets/vendor/corejs-typeahead/dist/typeahead.bundle.min.js|static',
            'system/modules/tagsinput/assets/js/jquery.tagsinput.min.js|static',
        ],
    ],
    'css' => [
        'files' => [
            'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.css|static',
            'system/modules/tagsinput/assets/css/bootstrap-tagsinput-fe.css|static',
            'system/modules/tagsinput/assets/css/typeahead-fe.css|static',
        ],
    ],
];

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['executePostActions']['tagsInput'] = ['TagsInput', 'generateAjax'];