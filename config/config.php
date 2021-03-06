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
$GLOBALS['TL_JAVASCRIPT']['tagsinput']    = 'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js|static';
$GLOBALS['TL_JAVASCRIPT']['sortable']     = 'system/modules/tagsinput/assets/vendor/Sortable/Sortable.min.js|static';
$GLOBALS['TL_JAVASCRIPT']['typeahead']    = 'system/modules/tagsinput/assets/vendor/corejs-typeahead/dist/typeahead.bundle.min.js|static';
$GLOBALS['TL_JAVASCRIPT']['tagsinput-be'] = 'system/modules/tagsinput/assets/js/jquery.tagsinput.min.js|static';

/**
 * CSS
 */
$GLOBALS['TL_CSS']['tagsinput'] = 'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.css';

if (\HeimrichHannot\Haste\Util\Container::isBackend()) {
    $GLOBALS['TL_CSS']['tagsinput-be'] = 'system/modules/tagsinput/assets/css/bootstrap-tagsinput-be.css';
    $GLOBALS['TL_CSS']['typeahead-be'] = 'system/modules/tagsinput/assets/css/typeahead-be.css';

    if (version_compare(VERSION, '4.0', '>=') && version_compare(VERSION, '5.0', '<')) {
        $GLOBALS['TL_CSS']['tagsinput-be-contao4'] = 'system/modules/tagsinput/assets/css/bootstrap-tagsinput-be-contao4.css';
    }
}

if (\HeimrichHannot\Haste\Util\Container::isFrontend()) {
    $GLOBALS['TL_CSS']['tagsinput-fe'] = 'system/modules/tagsinput/assets/css/bootstrap-tagsinput-fe.css|static';
    $GLOBALS['TL_CSS']['typeahead-fe'] = 'system/modules/tagsinput/assets/css/typeahead-fe.css|static';
}


$GLOBALS['TL_COMPONENTS']['bs.tagsinput'] = [
    'js'  => [
        'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js|static',
        'system/modules/tagsinput/assets/vendor/Sortable/Sortable.min.js|static',
        'system/modules/tagsinput/assets/vendor/corejs-typeahead/dist/typeahead.bundle.min.js|static',
        'system/modules/tagsinput/assets/js/jquery.tagsinput.min.js|static',
    ],
    'css' => [
        'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.css|static',
        'system/modules/tagsinput/assets/css/bootstrap-tagsinput-fe.css|static',
        'system/modules/tagsinput/assets/css/typeahead-fe.css|static',
    ],
];

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['executePostActions']['tagsInput'] = ['TagsInput', 'generateAjax'];
