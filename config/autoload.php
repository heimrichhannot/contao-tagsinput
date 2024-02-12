<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */

//use Contao\ClassLoader;
//
///**
// * Register the classes
// */
//ClassLoader::addClasses(array
//(
//	// Forms
//	'FormTagsInput' => 'system/modules/tagsinput/forms/FormTagsInput.php',
//
//	// Widgets
//	'TagsInput'     => 'system/modules/tagsinput/widgets/TagsInput.php',
//));

use Contao\TemplateLoader;

/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'form_tagsinput' => 'system/modules/tagsinput/templates/forms',
));
