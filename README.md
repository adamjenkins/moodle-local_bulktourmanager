# local_bulktourmanager

Adds bulk import/export/delete for `tool_usertours` guided tours directly
onto the existing *Site administration > Appearance > User tours* tour list
page (`/admin/tool/usertours/configure.php`) — no separate admin page.

## What it adds to that page

- **Bulk import (zip)** — upload a zip file containing any number of tour
  export JSON files (the same format produced by User tours' own single-tour
  "Export" action); every JSON file found is imported as a new tour.
- **Checkboxes** on each row of the existing tour list, plus a **select all**.
- **Export selected** — download a single zip containing the export JSON for
  every checked tour.
- **Delete selected** — delete every checked tour in one confirmed operation.

None of this touches Moodle core: the controls are injected client-side via
a callback on the core `before_footer_html_generation` hook (the same hook
`tool_usertours` itself uses), and the underlying bulk import/export/delete
logic is built entirely from `tool_usertours`'s own public API
(`tour::to_record()`, `tour::get_steps()`, `step::to_record()`,
`tour::instance()`, `tour::remove()`, and
`manager::import_tour_from_json()`).

## Permissions

Gated on the existing `tool/usertours:managetours` capability — no new
capability to configure. Anyone who can already manage tours can use the
bulk actions.

## Installation

Copy (or symlink) this repository to `local/bulktourmanager` in your Moodle
installation, then visit *Site administration > Notifications* to complete
the install.

## License

GNU GPL v3 or later. See [LICENSE](LICENSE).

## Author

Adam Jenkins <adam@wisecat.net>
