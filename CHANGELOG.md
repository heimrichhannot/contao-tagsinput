# Changelog
All notable changes to this project will be documented in this file.

## [2.2.4] - 2017-12-21

### Fixed
- added `static` flag to javascript files

## [2.2.3] - 2017-12-14

### Fixed
- front end invocation, js and styles were not loaded due to `heimrichhannot/contao-components` changes

## [2.2.2] - 2017-11-27

### Changed
- extracted count from title to brackets

## [2.2.1] - 2017-11-09

### Added
- tag list css class -> hover

## [2.2.0] - 2017-11-09

### Changed
- tag list css class behavior

## [2.1.20] - 2017-11-08

### Fixed
- contao 4 back end styles

## [2.1.19] - 2017-11-08

### Fixed
- load back end tagsinput css and js only in back end mode
- `Request.Contao` does not exist in contao 4, check if `undefined`

## [2.1.18] - 2017-11-06

### Changed
- updated config.php for contao-components 2.0

## [2.1.17] - 2017-10-24

### Changed
- removed is is_numeric check when comparing value with tag in `TagsInput::setValuesByOptions` otherwise `de` for example in list of languages will never be set active

## [2.1.16] - 2017-10-24

### Fixed
- contao 4 back end look

## [2.1.15] - 2017-10-24

### Fixed
- contao 4 back end styles

## [2.1.14] - 2017-10-16

### Fixed
- fixed tagsinput empty width (placeholder)

## [2.1.13] - 2017-10-16

### Added
- tag list weights style

## [2.1.12] - 2017-09-08

### Added
- tag list weights

## [2.1.11] - 2017-09-04

### Added
- tag list

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
