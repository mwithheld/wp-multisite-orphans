mwithheld/orphan-tables
==================

WP-CLI command for cleaning orphan tables on Wordpress Multisite installations. List, generate drop or rename statements, or drop orphan tables.

Quick links: [Using](#using) | [Installing](#installing) | [Contributing](#contributing) | [Support](#support)

## Using

### Get info
help wp-cli orphan-tables
help wp-cli orphan-tables list_drops
help wp-cli orphan-tables rename_tables
help wp-cli orphan-tables drop_tables

wp-cli orphan-tables list_drops         Prints drop statements for orphan tables; no changes are made. Renamed tables do not show up as orphaned tables. No parameters.
wp-cli orphan-tables list_orphaned      Prints orphan table names in plain text; no changes are made. Renamed tables do not show up as orphaned tables. No parameters.
wp-cli orphan-tables list_renamed       Prints a list of orphaned tables renamed by this plugin; no changes are made. Renamed tables do not show up as orphaned tables. No parameters.
wp-cli orphan-tables list_renames       Prints rename statements for orphan tables using the standard label {get_label}; no changes are made. Renamed tables do not show up as orphaned tables. No parameters.


### Renaming tables
Rename orphaned tables with a standard label + hashed table name. 
wp-cli orphan-tables do_renames --limit=1

* wp-cli orphan-tables do_renames
* wp-cli orphan-tables do_renames --dry-run
* wp-cli orphan-tables do_renames --limit=1 --debug --dry-run --yes

### Dropping tables
Dry run of dropping the first 14 tables alphabetically, showing debug output and skipping the confirmation prompt.
wp-cli orphan-tables do_drops --limit=14 --debug --dry-run --yes

* wp-cli orphan-tables do_drops
* wp-cli orphan-tables do_drops --dry-run
* wp-cli orphan-tables do_drops --limit=14


## Installing

Installing this package requires WP-CLI v2.2 or greater. Update to the latest stable release with `wp cli update`.

Once you've done so, you can install this package with:

    wp package install git@github.com:mwithheld/orphan-tables.git

## Contributing

We appreciate you taking the initiative to contribute to this project.

Contributing isn’t limited to just code. We encourage you to contribute in the way that best fits your abilities, by writing tutorials, giving a demo at your local meetup, helping other users with their support questions, or revising our documentation.

For a more thorough introduction, [check out WP-CLI's guide to contributing](https://make.wordpress.org/cli/handbook/contributing/). This package follows those policy and guidelines.

### Reporting a bug

Think you’ve found a bug? We’d love for you to help us get it fixed.

Before you create a new issue, you should [search existing issues](https://github.com/mwithheld/orphan-tables/issues?q=label%3Abug%20) to see if there’s an existing resolution to it, or if it’s already been fixed in a newer version.

Once you’ve done a bit of searching and discovered there isn’t an open or fixed issue for your bug, please [create a new issue](https://github.com/mwithheld/orphan-tables/issues/new). Include as much detail as you can, and clear steps to reproduce if possible. For more guidance, [review our bug report documentation](https://make.wordpress.org/cli/handbook/bug-reports/).

### Creating a pull request

Want to contribute a new feature? Please first [open a new issue](https://github.com/mwithheld/orphan-tables/issues/new) to discuss whether the feature is a good fit for the project.

Once you've decided to commit the time to seeing your pull request through, [please follow our guidelines for creating a pull request](https://make.wordpress.org/cli/handbook/pull-requests/) to make sure it's a pleasant experience. See "[Setting up](https://make.wordpress.org/cli/handbook/pull-requests/#setting-up)" for details specific to working on this package locally.

## Support

Github issues aren't for general support questions, but there are other venues you can try: https://wp-cli.org/#support
