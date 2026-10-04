# Changes

## v0.1.1

- Declare Moodle 5.3 support.
- Fixed: bulk import now validates each tour JSON file and reports a malformed one
  as a failure instead of passing it to core (which emitted PHP warnings).
