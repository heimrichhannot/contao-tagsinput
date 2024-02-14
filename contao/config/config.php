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

use Contao\System;
use HeimrichHannot\TagsInput\Widget\TagsInput;
use HeimrichHannot\TagsInput\Widget\FormTagsInput;

/**
 * Constants
 */
define('TAGSINPUT_NEW_TAG_PREFIX', '#!nt&_');

/**
 * Back end form fields
 */
$GLOBALS['BE_FFL']['tagsinput'] = TagsInput::class;

/**
 * Front end form fields
 */
$GLOBALS['TL_FFL']['tagsinput'] = FormTagsInput::class;

/**
 * Javascript
 */
// $GLOBALS['TL_JAVASCRIPT']['tagsinput']    = 'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js|static';
// $GLOBALS['TL_JAVASCRIPT']['sortable']     = 'system/modules/tagsinput/assets/vendor/Sortable/Sortable.min.js|static';
// $GLOBALS['TL_JAVASCRIPT']['typeahead']    = 'system/modules/tagsinput/assets/vendor/corejs-typeahead/dist/typeahead.bundle.min.js|static';
// $GLOBALS['TL_JAVASCRIPT']['tagsinput-be'] = 'system/modules/tagsinput/assets/js/jquery.tagsinput.min.js|static';

/**
 * CSS
 */
// $GLOBALS['TL_CSS']['tagsinput'] = 'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.css';

$utils = System::getContainer()->get('huh.utils.container');

if ($utils->isBackend())
{
    $GLOBALS['TL_JAVASCRIPT']['tagsinput-be'] = 'bundles/heimrichhannottagsinput/assets/contao-tagsinput-be.js|static';
    $GLOBALS['TL_CSS']['tagsinput-be'] = 'bundles/heimrichhannottagsinput/assets/bootstrap-tagsinput-be.css';

    // $GLOBALS['TL_CSS']['tagsinput-be'] = 'system/modules/tagsinput/assets/css/bootstrap-tagsinput-be.css';
    // $GLOBALS['TL_CSS']['typeahead-be'] = 'system/modules/tagsinput/assets/css/typeahead-be.css';

    if (version_compare(VERSION, '4.0', '>=') && version_compare(VERSION, '5.0', '<')) {
        $GLOBALS['TL_JAVASCRIPT']['tagsinput-be'] = 'bundles/heimrichhannottagsinput/assets/contao-tagsinput-be-contao4.js|static';
        $GLOBALS['TL_CSS']['tagsinput-be'] = 'bundles/heimrichhannottagsinput/assets/bootstrap-tagsinput-be-contao4.css';
    }
}

// if ($utils->isFrontend()) {
//     $GLOBALS['TL_CSS']['tagsinput-fe'] = 'system/modules/tagsinput/assets/css/bootstrap-tagsinput-fe.css|static';
//     $GLOBALS['TL_CSS']['typeahead-fe'] = 'system/modules/tagsinput/assets/css/typeahead-fe.css|static';
// }

// $GLOBALS['TL_COMPONENTS']['bs.tagsinput'] = [
//     'js'  => [
//         'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.min.js|static',
//         'system/modules/tagsinput/assets/vendor/Sortable/Sortable.min.js|static',
//         'system/modules/tagsinput/assets/vendor/corejs-typeahead/dist/typeahead.bundle.min.js|static',
//         'system/modules/tagsinput/assets/js/jquery.tagsinput.min.js|static',
//     ],
//     'css' => [
//         'system/modules/tagsinput/assets/vendor/bootstrap-tagsinput/dist/bootstrap-tagsinput.css|static',
//         'system/modules/tagsinput/assets/css/bootstrap-tagsinput-fe.css|static',
//         'system/modules/tagsinput/assets/css/typeahead-fe.css|static',
//     ],
// ];

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['executePostActions']['tagsInput'] = ['TagsInput', 'generateAjax'];
