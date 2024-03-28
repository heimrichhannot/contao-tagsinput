<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\TagsInput\Asset;

use HeimrichHannot\EncoreContracts\EncoreEntry;
use HeimrichHannot\EncoreContracts\EncoreExtensionInterface;
use HeimrichHannot\TagsInput\HeimrichHannotContaoTagsInput;

class EncoreExtension implements EncoreExtensionInterface
{
    /**
     * {@inheritDoc}
     */
    public function getBundle(): string
    {
        return HeimrichHannotContaoTagsInput::class;
    }

    /**
     * {@inheritDoc}
     */
    public function getEntries(): array
    {
        return [
            EncoreEntry::create('contao-tagsinput', 'assets/js/contao-tagsinput.js')
                ->addJsEntryToRemoveFromGlobals('tagsinput')
                ->addJsEntryToRemoveFromGlobals('sortable')
                ->addJsEntryToRemoveFromGlobals('typeahead'),
            EncoreEntry::create('contao-tagsinput-bootstrap-theme', 'assets/js/contao-tagsinput-bootstrap-theme.js')
                ->setRequiresCss(true)
                ->addCssEntryToRemoveFromGlobals('tagsinput')
                ->addCssEntryToRemoveFromGlobals('tagsinput-fe')
                ->addCssEntryToRemoveFromGlobals('typeahead-fe')
        ];
    }
}