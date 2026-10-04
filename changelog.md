# Changelog

All notable changes to this plugin are documented in this file, following
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/).

## [0.1.2] - 2026-10-04

### Changed

- Maturity raised from Alpha to Beta.
- Continuous integration tests against the released Moodle 5.3
  (MOODLE_503_STABLE) instead of Moodle's development branch.
- composer.json: the moodle/moodle requirement uses a caret constraint, so later
  Moodle 5.x releases are no longer excluded.

## [0.1.1] - 2026-10-04

First tagged release.

### Added

- Bulk import, export and delete for user tours on the existing
  *Site administration > Appearance > User tours* page.
- Bulk enable/disable of selected tours, and a bulk "Edit filters" dialog that
  changes only the filters you tick, on every selected tour.
- Moodle 5.3 support declared.
- Camp registry release workflow and listing manifest.

### Fixed

- Bulk import validates each tour JSON file and reports a malformed one as a
  failure instead of passing it to core (which emitted PHP warnings).
- The bulk Import button now appears even when no tours exist.
- Toolbar buttons use the Bootstrap 5 `me-2` margin class instead of the
  deprecated `mr-2`.
