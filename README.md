mwithheld/wp-multisite-orphans
==================

When a WordPress Multisite child site is permanently deleted, WordPress does not delete the database tables or the site's user files folder. This tool helps clean those up using [WP-CLI](https://wp-cli.org/)

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)


## Using

### Get help

|Command|Help|
|--- | --- |
|`wp-cli help wp-multisite-orphans`|Shows help for the wp-multisite-orphans package|
|`wp-cli help wp-multisite-orphans list_tables`|Shows help for the list_tables sub-command|
|`wp-cli help wp-multisite-orphans [sub-command]`|Shows help for this sub-command|

### Get info

|Command|Help|
|--- | --- |
|`wp-cli wp-multisite-orphans list_already_renamed_tables`|Prints a list of orphaned tables renamed by this package; no changes are made. Renamed tables do not show up as orphaned tables. No parameters.|
|`wp-cli wp-multisite-orphans list_drop_renamed_tables`|Prints drop statements for renamed tables; no changes are made. No parameters.|
|`wp-cli wp-multisite-orphans list_drop_tables`|Prints drop statements for orphan tables; no changes are made. Renamed tables do not show up as orphaned tables. No parameters.|
|`wp-cli wp-multisite-orphans list_folders`|Prints rename statements for orphan tables using the standard label {show_label}; no changes are made. Renamed tables do not show up as orphaned tables. No parameters.|
|`wp-cli wp-multisite-orphans list_moved_folders`|Prints a list of orphaned folders that were moved by this package. No parameters.|
|`wp-cli wp-multisite-orphans list_rename_tables`|Prints rename statements for orphan tables using the standard label {show_label}. No changes are made. Renamed tables do not show up as orphaned tables. No parameters.|
|`wp-cli wp-multisite-orphans list_tables`|Prints orphan table names in plain text. Renamed tables do not show up as orphaned tables. No parameters.|
|--- | --- |
|`wp-cli wp-multisite-orphans show_label`|Prints the rename label. No parameters.|
|`wp-cli wp-multisite-orphans show_source_dirs`|Prints the folders we look into for orphaned folders. No parameters.|
|`wp-cli wp-multisite-orphans show_target_dir`|Prints the destination directory when we move orphaned folders. No parameters.|

### Rename orphaned tables
Rename orphaned tables with a standard label + hashed table name (after a confirmation prompt).

    wp-cli wp-multisite-orphans do_rename_tables --limit=1

Other examples of this command:

* `wp-cli wp-multisite-orphans do_rename_tables`
* `wp-cli wp-multisite-orphans do_rename_tables --dry-run`
* `wp-cli wp-multisite-orphans do_rename_tables --limit=1 --debug --dry-run --yes`

### Drop orphaned tables
Dry run of dropping the first 14 tables alphabetically, showing debug output and skipping the confirmation prompt.

    wp-cli wp-multisite-orphans do_drop_tables --limit=14 --debug --dry-run --yes

Other examples of this command:

* `wp-cli wp-multisite-orphans do_drop_tables`
* `wp-cli wp-multisite-orphans do_drop_tables --dry-run`
* `wp-cli wp-multisite-orphans do_drop_tables --limit=14`

### Drop renamed tables
Drop the first 10 renamed tables alphabetically (after a confirmation prompt).

    wp-cli wp-multisite-orphans do_drop_renamed_tables --limit=10

Other examples of this command:

* `wp-cli wp-multisite-orphans do_drop_renamed_tables`
* `wp-cli wp-multisite-orphans do_drop_renamed_tables --dry-run`
* `wp-cli wp-multisite-orphans do_drop_renamed_tables --limit=14 --debug --dry-run --yes`

### Move orphaned folders
Move the first 3 orphaned folders into a wp uploads folder named for this package (after a confirmation prompt).

    wp-cli wp-multisite-orphans do_move_folders --limit=3

See also `show_source_dirs` and `show_target_dir`


## Installing

Installing this package requires WP-CLI v2.2 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with:

    wp package install git@github.com:mwithheld/wp-multisite-orphans.git


## Updating

    wp package update git@github.com:mwithheld/wp-multisite-orphans.git


## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/mwithheld/wp-multisite-orphans/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/mwithheld/wp-multisite-orphans/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/mwithheld/wp-multisite-orphans/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.


## Support

Github issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support

