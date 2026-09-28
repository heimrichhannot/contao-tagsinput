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
$GLOBALS['BE_FFL'][TagsInput::TYPE] = TagsInput::class;

/**
 * Front end form fields
 */
$GLOBALS['TL_FFL']['tagsinput'] = FormTagsInput::class;

/**
 * Hooks
 */
$GLOBALS['TL_HOOKS']['executePostActions']['tagsInput'] = [TagsInput::class, 'generateAjax'];
