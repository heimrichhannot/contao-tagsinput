<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2015 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'HeimrichHannot',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Forms
	'FormTagsInput'                            => 'system/modules/tagsinput/forms/FormTagsInput.php',

	// Widgets
	'TagsInput'                                => 'system/modules/tagsinput/widgets/TagsInput.php',

	// Classes
	'HeimrichHannot\TagsInput\TagsInputHelper' => 'system/modules/tagsinput/classes/TagsInputHelper.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	'form_tagsinput' => 'system/modules/tagsinput/templates/forms',
));
