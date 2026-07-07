# local_bulktourmanager

A Moodle local plugin scaffold for bulk importing and managing the core
`tool_usertours` guided tours from one admin page, instead of importing
each tour export one at a time via *Site administration > Appearance >
User tours > Import tour*.

## Status

Scaffold only — `index.php` is a placeholder. The bulk import/export logic
has not been implemented yet.

## Installation

Copy (or symlink) this repository to `local/bulktourmanager` in your Moodle
installation, then visit *Site administration > Notifications* to complete
the install.

## Capabilities

- `local/bulktourmanager:manage` — required to view and use the bulk tour
  manager admin page. Allowed by default for the `manager` archetype.

## License

GNU GPL v3 or later. See [LICENSE](LICENSE).

## Author

Adam Jenkins <adam@wisecat.net>
