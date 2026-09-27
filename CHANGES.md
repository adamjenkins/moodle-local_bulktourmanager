# Changes

## Unreleased

- Declare Moodle 5.3 support.
- Fixed: bulk import now validates each tour JSON file and reports a malformed one
  as a failure instead of passing it to core (which emitted PHP warnings).

## v0.1.0

Initial release. Not yet tagged.

- Adds bulk import, export and delete for `tool_usertours` guided tours
  directly onto the existing *Site administration > Appearance > User tours*
  list page (`/admin/tool/usertours/configure.php`) — no separate admin page.
- Bulk enable/disable and bulk edit of tour filters, via a modal.
- Fixed: the Bulk import button was missing when the site had zero tours.
