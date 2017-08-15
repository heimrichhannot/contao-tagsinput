# Changelog
All notable changes to this project will be documented in this file.

## [2.1.10] - 2017-08-15

### Changed
- margin-bottom removed from tt-input to make work with bootstrap 4.0.0-beta

## [2.1.9] - 2017-07-24

### Changed
- jquery is provided within back end mode by `heimrichhannot/contao-haste_plus`, removed from tagsinput

## [2.1.8] - 2017-07-24

### Added
- contao 4 backend styles

## [2.1.7] - 2017-06-21
- fixed `jquery` js path for contao 4

## [2.1.6] - 2017-06-19
- fixed `remote` handling 

## [2.1.5] - 2017-06-14
- fixed ja inclusion for contao 4

## [2.1.4] - 2017-05-17
- fixed composer.json for contao 4

## [2.1.3] - 2017-05-02
- reset typeahead dropdown preselection on blur()

## [2.1.2] - 2017-04-12
- created new tag

## [2.1.1] - 2017-04-06

### Changed

- added php7 support, fixed contao-core dependency

## [2.1.0] - 2017-03-22

### Added

- DCA eval array now supports maxTags, maxChars, trimValue, allowDuplicates, highlight, highlightOptions, limit

### Changed
- Updated bootstrap-tagsinput vendor package to version 0.8
- invoke assets in front end with `heimrichhannot/contao-components`
