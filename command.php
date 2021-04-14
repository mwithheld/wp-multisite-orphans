<?php

namespace mwithheld\Orphan_Tables\Commands;

// Internal API ref https://make.wordpress.org/cli/handbook/references/internal-api/
use WP_CLI;

if (!defined('WP_CLI') || !class_exists('WP_CLI') || empty(WP_CLI)) {
    echo 'WP_CLI is not defined';
    return;
}

class WP_Multisite_Orphans extends \WP_CLI_Command {

    protected $db;
    private $_nl = "\n";
    private $_rename_label;
    private $_flags = [
        'dryrun' => ['name' => 'dry-run', 'default' => false],
        'limit'  => ['name' => 'limit', 'default' => 0],
    ];
    private $wpuploadsdir = '';

    //==========================================================================
    // Methods that provide a user-accessible interface for this package.
    //==========================================================================

    /**
     * Set up.
     */
    public function __construct() {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        if (!is_multisite()) {
            WP_CLI::error('This is not a WP Multisite installation. This command is for Multisite only.');
        }

        $this->db = $GLOBALS['wpdb'];

        $this->_rename_label = \str_replace(__NAMESPACE__ . '\\', '', __CLASS__);
        $this->_flags = (object) $this->_flags;
        foreach ($this->_flags as $key => $value) {
            $this->_flags->$key = (object) $value;
        }
    }

    /**
     * If a class implements __invoke(), the command name will be registered to that method and no other methods of that class will be registered as commands.
     *
     * @param array $args
     * @param array $assoc_args
      public function __invoke($args, $assoc_args) {
      $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
      \WP_CLI::debug("{$fxn}::Started");
      }
     */
    /**
     * ## OPTIONS
     *
     * <slug>
     * : The internal name of the block.
     */
//    public function test() {
//        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
//        \WP_CLI::debug("{$fxn}::Started");
//    }

    /**
     * Prints the rename label; no changes are made. No parameters.
     *
     * ## EXAMPLE
     * wp-cli wp-multisite-orphans get_label
     *
     * @return void
     */
    public function get_label() {
        \WP_CLI::success("$this->_rename_label");
        return $this->_rename_label;
    }

    /**
     * Prints orphan table names in plain text; no changes are made. Renamed tables do not show up as orphaned tables. No parameters.
     *
     * ## EXAMPLE
     * wp-cli wp-multisite-orphans list_tables
     *
     * @return void
     */
    public function list_tables(): array {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        $items = $this->get_orphan_tables();
        if (\count($items) < 1) {
            \WP_CLI::success('No tables found');
            return [];
        }

        foreach ($items as &$i) {
            \WP_CLI::log("$i");
        }
        \WP_CLI::success(\count($items) . ' orphan tables names');
        return $items;
    }

    /**
     * Prints drop statements for orphan tables; no changes are made. Renamed tables do not show up as orphaned tables. No parameters.
     *
     * ## EXAMPLE
     * wp-cli wp-multisite-orphans list_drop_tables
     *
     * @return void
     */
    public function list_drop_tables(): array {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        $internal = false;
        $caller = \array_slice(\debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2), 1, 1)[0];
        //\WP_CLI::debug("{$fxn}::caller=".\print_r($caller, true));
        if (isset($caller['class']) && !empty($caller['class']) && $caller['class'] == __CLASS__) {
            $internal = true;
            \WP_CLI::debug("{$fxn}::This is an interal function call");
        }

        $items = $this->get_orphan_tables();
        $returnThis = [];
        if (\count($items) < 1) {
            !$internal && \WP_CLI::success('No tables found');
            return $returnThis;
        }

        $returnThis = $this->create_drop_statements($items);
        foreach ($returnThis as &$sql) {
            !$internal && \WP_CLI::log($sql);
        }
        !$internal && \WP_CLI::success(\count($items) . ' orphan tables drop statements');
        return $returnThis;
    }

    /**
     * Prints drop statements for renamed tables; no changes are made. No parameters.
     *
     * ## EXAMPLE
     * wp-cli wp-multisite-orphans list_drop_renamed_tables
     *
     * @return void
     */
    public function list_drop_renamed_tables(): array {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        $internal = false;
        $caller = \array_slice(\debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2), 1, 1)[0];
        //\WP_CLI::debug("{$fxn}::caller=".\print_r($caller, true));
        if (isset($caller['class']) && !empty($caller['class']) && $caller['class'] == __CLASS__) {
            $internal = true;
            \WP_CLI::debug("{$fxn}::This is an interal function call");
        }

        $items = $this->get_renamed_tables();
        $returnThis = [];
        if (\count($items) < 1) {
            !$internal && \WP_CLI::success('No tables found');
            return $returnThis;
        }

        $returnThis = $this->create_drop_statements($items);
        foreach ($returnThis as &$sql) {
            !$internal && \WP_CLI::log($sql);
        }
        !$internal && \WP_CLI::success(\count($items) . ' orphan tables drop statements');
        return $returnThis;
    }

    /**
     * Prints rename statements for orphan tables using the standard label {get_label}; no changes are made. Renamed tables do not show up as orphaned tables. No parameters.
     *
     * ## EXAMPLE
     * wp-cli wp-multisite-orphans list_rename_tables
     *
     * @return void
     */
    public function list_rename_tables(): array {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");
        $internal = false;
        $caller = \array_slice(\debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2), 1, 1)[0];
        //\WP_CLI::debug("{$fxn}::caller=".\print_r($caller, true));
        if (isset($caller['class']) && !empty($caller['class']) && $caller['class'] == __CLASS__) {
            $internal = true;
            \WP_CLI::debug("{$fxn}::This is an interal function call");
        }

        $items = $this->get_orphan_tables();
        $returnThis = [];
        if (\count($items) < 1) {
            !$internal && \WP_CLI::success('No tables found');
            return $returnThis;
        }

        $tablename_new = '';
        $sql = '';
        foreach ($items as &$i) {
            $tablename_new = \str_replace($i, "{$this->db->prefix}" . $this->_rename_label . '_' . \sha1("{$i}"), "{$i}");
            $sql = "RENAME TABLE {$i} TO {$tablename_new};";
            !$internal && \WP_CLI::log($sql);
            $returnThis[] = $sql;
        }
        !$internal && \WP_CLI::success(\count($items) . ' orphan tables rename statements');
        return $returnThis;
    }

    /**
     * Prints a list of orphaned tables renamed by this package; no changes are made. Renamed tables do not show up as orphaned tables. No parameters.
     *
     * ## EXAMPLE
     * wp-cli wp-multisite-orphans list_already_renamed_tables
     *
     * @return void
     */
    public function list_already_renamed_tables(): array {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        $items = $this->get_renamed_tables();
        if (\count($items) < 1) {
            \WP_CLI::success('No tables found');
            return[];
        }

        foreach ($items as &$i) {
            \WP_CLI::log("{$i}");
        }
        \WP_CLI::success(\count($items) . ' orphan tables renamed by this package');
        return $items;
    }

    /**
     * Prints a list of orphaned folders in wp-content/uploads/sites/<n> and wp-content/blogs.dir/<n>. No parameters.
     *
     * ## EXAMPLE
     * wp-cli wp-multisite-orphans list_folders
     *
     * @return void
     */
    public function list_folders(): array {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        $items = $this->get_orphan_folders();
        if (\count($items) < 1) {
            \WP_CLI::success("No orphaned folders found");
            return[];
        }

        foreach ($items as &$i) {
            \WP_CLI::log("{$i}");
        }
        \WP_CLI::success(\count($items) . ' orphaned folders');
        return $items;
    }

    /**
     * Rename orphaned tables with a standard label + hashed table name. Renamed tables do not show up as orphaned tables.
     *
     * ## PARAMETERS
     *      --limit=<int> Only attempt rename on this number of tables (when sorted alphabetically ASC).
     *      --dry-run Do not actually make any changes - just show what would be done.
     *
     * ## EXAMPLE
     * wp-cli wp-multisite-orphans do_rename_tables
     * wp-cli wp-multisite-orphans do_rename_tables --dry-run
     * wp-cli wp-multisite-orphans do_rename_tables --limit=1 --debug --dry-run --yes
     *
     * @param array $args Command-line arguments array from WP-CLI. Unused here.
     * @param array $assoc_args Command line flags from WP-CLI. Flags specifically used by this package:
     *
     * @return object {changed=><int>, failed=><int>}
     */
    public function do_rename_tables(array $args, array $assoc_args): \stdClass {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started with args=" . \print_r($args, true) . '; $assoc_args=' . \print_r($assoc_args, true));

        \WP_CLI::confirm('BE CAREFUL, this cannot be easily undone so please backup your database before proceeding. Are you sure you want to proceed?', $assoc_args);

        $dryrun = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->dryrun->name, false);
        $dryrun && \WP_CLI::log("{$fxn}::Dry run, so do not actually make any changes");

        $limit = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->limit->name, 0);
        $limit && \WP_CLI::log("{$fxn}::Limiting to {$limit} tables");

        $results = $this->execute_ddl($this->list_rename_tables(), $limit, $dryrun);

        \WP_CLI::success("Processed " . ($results->changed + $results->failed) . " tables: Changed={$results->changed}; Failed={$results->failed}");
        return $results;
    }

    /**
     * Drop orphaned tables. Renamed tables do not show up as orphaned tables.
     *
     * ## PARAMETERS
     *      --limit=<int> Only attempt rename on this number of tables (when sorted alphabetically ASC).
     *      --dry-run Do not actually make any changes - just show what would be done.
     *
     * ## EXAMPLE
     * wp-cli wp-multisite-orphans do_drop_tables
     * wp-cli wp-multisite-orphans do_drop_tables --dry-run
     * wp-cli wp-multisite-orphans do_drop_tables --limit=14 --debug --dry-run --yes
     *
     * @param array $args Command-line arguments array from WP-CLI. Unused here.
     * @param array $assoc_args Command line flags from WP-CLI. Flags specifically used by this package:
     *
     * @return \stdClass Result tallies {changed=><int>, failed=><int>}
     */
    public function do_drop_tables(array $args, array $assoc_args): \stdClass {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started with args=" . \print_r($args, true) . '; $assoc_args=' . \print_r($assoc_args, true));

        \WP_CLI::confirm('BE CAREFUL, this cannot be undone so please backup your database before proceeding. Are you sure you want to proceed?', $assoc_args);

        $dryrun = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->dryrun->name, false);
        $dryrun && \WP_CLI::log("{$fxn}::Dry run, so do not actually make any changes");

        $limit = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->limit->name, 0);
        $limit && \WP_CLI::log("{$fxn}::Limiting to {$limit} tables");

        $results = $this->execute_ddl($this->list_drop_tables(), $limit, $dryrun);

        \WP_CLI::success("Processed " . ($results->changed + $results->failed) . " tables: Changed={$results->changed}; Failed={$results->failed}");
        return $results;
    }

    /**
     * Drop tables that were renamed by this plugin.
     *
     * ## PARAMETERS
     *      --limit=<int> Only attempt rename on this number of tables (when sorted alphabetically ASC).
     *      --dry-run Do not actually make any changes - just show what would be done.
     *
     * ## EXAMPLE
     * wp-cli wp-multisite-orphans do_drop_renamed_tables
     * wp-cli wp-multisite-orphans do_drop_renamed_tables --dry-run
     * wp-cli wp-multisite-orphans do_drop_renamed_tables --limit=2 --debug --dry-run --yes
     *
     * @param array $args Command-line arguments array from WP-CLI. Unused here.
     * @param array $assoc_args Command line flags from WP-CLI. Flags specifically used by this package:
     *
     * @return \stdClass Result tallies {changed=><int>, failed=><int>}
     */
    public function do_drop_renamed_tables(array $args, array $assoc_args): \stdClass {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started with args=" . \print_r($args, true) . '; $assoc_args=' . \print_r($assoc_args, true));

        \WP_CLI::confirm('BE CAREFUL, this cannot be undone so please backup your database before proceeding. Are you sure you want to proceed?', $assoc_args);

        $dryrun = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->dryrun->name, false);
        $dryrun && \WP_CLI::log("{$fxn}::Dry run, so do not actually make any changes");

        $limit = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->limit->name, 0);
        $limit && \WP_CLI::log("{$fxn}::Limiting to {$limit} tables");

        $results = $this->execute_ddl($this->list_drop_renamed_tables(), $limit, $dryrun);

        \WP_CLI::success("Processed " . ($results->changed + $results->failed) . " tables: Changed={$results->changed}; Failed={$results->failed}");
        return $results;
    }

    /**
     * Move orpahned folders into a wp uploads folder named for this plugin.
     *
     * ## PARAMETERS
     *      --limit=<int> Only attempt rename on this number of tables (when sorted alphabetically ASC).
     *      --dry-run Do not actually make any changes - just show what would be done.
     *
     * ## EXAMPLE
     * wp-cli wp-multisite-orphans do_move_folders
     * wp-cli wp-multisite-orphans do_move_folders --dry-run
     * wp-cli wp-multisite-orphans do_move_folders --limit=2 --debug --dry-run --yes
     *
     * @param array $args Command-line arguments array from WP-CLI. Unused here.
     * @param array $assoc_args Command line flags from WP-CLI. Flags specifically used by this package:
     *
     * @return \stdClass Result tallies {changed=><int>, failed=><int>}
     */
    public function do_move_folders(array $args, array $assoc_args): \stdClass {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started with args=" . \print_r($args, true) . '; $assoc_args=' . \print_r($assoc_args, true));

        \WP_CLI::confirm('BE CAREFUL, this cannot be easily undone so please backup your database before proceeding. Are you sure you want to proceed?', $assoc_args);

        $dryrun = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->dryrun->name, false);
        $dryrun && \WP_CLI::log("{$fxn}::Dry run, so do not actually make any changes");

        $limit = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->limit->name, 0);
        $limit && \WP_CLI::log("{$fxn}::Limiting to {$limit} tables");

        $results = $this->move_folders($this->get_orphan_folders(), $limit, $dryrun);

        \WP_CLI::success("Processed " . ($results->changed + $results->failed) . " folders: Changed={$results->changed}; Failed={$results->failed}");
        return $results;
    }

    //==========================================================================
    // Methods specific to this class.
    //==========================================================================

    /**
     * Create SQL drop statements for each of the list of table names.
     *
     * @param array $tablenames Array of table names to create drop statements for, each name including the DB prefix.
     * @return array List of SQL drop statements.
     */
    private function create_drop_statements(array $tablenames) {
        $returnThis = [];
        $sql = '';
        foreach ($tablenames as &$t) {
            $sql = "DROP TABLE IF EXISTS {$t};";
            $returnThis[] = $sql;
        }
        return $returnThis;
    }

    /**
     * Execute the list of passed-in SQL statements.
     * E.g. To rename each of the tables passed in with a standard label + hashed table name.
     *
     * @param array $statements SQL RENAME TABLE commands e.g. from list_already_renamed_tables().
     * @param int $limit Only attempt rename on this number of tables (when sorted alphabetically ASC).
     * @param bool $dryrun True to not actually run the queries, just print them.
     * @return \stdClass Result tallies {changed=><int>, failed=><int>}
     */
    private function execute_ddl(array $statements, int $limit = 0, bool $dryrun = false): \stdClass {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started with " . \count($statements) . " statements; \$limit={$limit}; \$dryrun={$dryrun}");

        $dryrun && \WP_CLI::debug("{$fxn}::Dry run, so do not actually make any changes");
        $limit && \WP_CLI::debug("{$fxn}::Limiting to {$limit} tables");

        $returnThis = (object) ['changed' => 0, 'failed' => 0];
        if (empty($statements) || $limit < 0) {
            \WP_CLI::debug("{$fxn}::No statements or invalid limit");
            return $returnThis;
        }

        if ($limit > 0) {
            $targets = \array_slice($statements, 0, $limit);
            \WP_CLI::debug("{$fxn}::Cut statements down to " . \count($targets) . ' $statements');
        } else {
            $targets = $statements;
        }

        $result = false;
        foreach ($targets as &$t) {
            //\WP_CLI::debug("{$fxn}::Looking at \$t={$t}");
            if ($dryrun) {
                $result = false;
                $returnThis->failed++;
                \WP_CLI::success("(Dry run) {$t}");
                continue;
            }

            $result = $this->db->query($t);
            // Table renames do not return a success result.
            if (\stripos($t, 'RENAME TABLE ') !== false || $result) {
                $returnThis->changed++;
                \WP_CLI::success("{$fxn}::\$t={$t}");
            } else {
                $returnThis->failed++;
                \WP_CLI::warning("{$fxn}::\$t={$t}");
            }
            //\WP_CLI::debug("{$fxn}::Got \$result=" . \print_r($result, true));
        }

        return $returnThis;
    }

    /**
     * Get a list of orphaned tables renamed by this package. Renamed tables do not show up as orphaned tables.
     *
     * @return array List of DB table names that have been renamed by this tool.
     */
    private function get_renamed_tables(): array {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        $sql = 'SELECT table_name '
                . 'FROM information_schema.tables '
                . "WHERE table_schema='{$this->db->dbname}' "
                . "AND table_name LIKE '{$this->db->prefix}{$this->_rename_label}%' "
                . 'ORDER BY table_name';
        \WP_CLI::debug("{$fxn}::About to run sql={$sql}");

        return $this->db->get_col($sql);
    }

    /**
     * Get a list of integers representing existing Multisite blog ids from the prefix_blogs table->blog_id field.
     *
     * @return array See the description.
     */
    private function get_existing_blog_ids(): array {
        return $this->db->get_col("SELECT blog_id FROM {$this->db->blogs} ORDER BY blog_id");
    }

    /**
     * Get a list of child site table names in the database schema including the prefix. These are tables like prefix_<int>_<name> e.g. wp_22_options for blog_id=22.
     *
     * @return array See the description.
     */
    private function get_child_tablenames() {
        $sql = 'SELECT table_name '
                . 'FROM information_schema.tables '
                . "WHERE table_schema='{$this->db->dbname}' "
                /* Restricting to 0-9 skips tables for the network-level */
                . "AND table_name REGEXP '{$this->db->prefix}[0-9]+_' "
                . 'ORDER BY table_name';
        //\WP_CLI::debug("{$fxn}::About to run sql={$sql}");
        return $this->db->get_col($sql);
    }

    /**
     * Get a list of tables that do not belong to a WP blog. Renamed tables do not show up as orphaned tables.
     *
     * @return array List of DB table names that do not have a matching entry in the WP Multisite wp_blogs table.
     */
    private function get_orphan_tables(): array {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        $child_tablenames = $this->get_child_tablenames();
        \WP_CLI::debug(__FUNCTION__ . '::Found ' . \count($child_tablenames) . ' tables');

        //These  blogs_ids represent actual Multisite child blogs we want to keep.
        $existing_blog_ids = $this->get_existing_blog_ids();
        \WP_CLI::debug(__FUNCTION__ . '::Found ' . \count($existing_blog_ids) . " \$existing_blog_ids");

        //Gather the orphaned table names here.
        $orphan_tablenames = [];

        //Search tables with name prefix containing non-existing blog IDs.
        $table_blog_id = 0;
        foreach ($child_tablenames as &$t) {
            \WP_CLI::debug(__FUNCTION__ . "::Looking at \$table_name={$t}");
            $table_blog_id = $this->get_number_from_table_name($t);
            \WP_CLI::debug(__FUNCTION__ . "::From \$t={$t} extracted \$table_blog_id={$table_blog_id}");
            if (empty($table_blog_id)) {
                \WP_CLI::debug(__FUNCTION__ . "::The \$t={$t} is not a WP Multisite child site table");
                continue;
            }
            if (!\in_array($table_blog_id, $existing_blog_ids)) {
                \WP_CLI::debug(__FUNCTION__ . "::Table \$t={$t} does not represent an existing blog");
                $orphan_tablenames[] = $t;
            }
        }

        return $orphan_tablenames;
    }

    /**
     * Get a list of child site folders in wp-content/uploads/sites/<n> and wp-content/blogs.dir/<n> that do not belong to a WP blog.
     *
     * @return array List of DB table names that do not have a matching entry in the WP Multisite wp_blogs table.
     */
    private function get_orphan_folders(): array {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        $wpuploadsdir = $this->get_wpuploads_dir();
        $targetdirs = [$wpuploadsdir, $wpuploadsdir . DIRECTORY_SEPARATOR . 'sites'];

        //Gather the orphaned folder names here.
        $orphan_folders = [];

        //These  blogs_ids represent actual Multisite child blogs we want to keep.
        $existing_blog_ids = $this->get_existing_blog_ids();
        \WP_CLI::debug(__FUNCTION__ . '::Found ' . \count($existing_blog_ids) . ' $existing_blog_ids');

        $path = null;
        $diritems = null;
        foreach ($targetdirs as $targetdir) {
            \WP_CLI::debug("Looking at upload dir={$targetdir}");

            $diritems = \scandir($targetdir);
            foreach ($diritems as &$i) {
                \WP_CLI::debug("Looking at subfolder \$i={$i}");
                if (\is_numeric($i) && \in_array($i, $existing_blog_ids) && \is_dir($path = $targetdir . DIRECTORY_SEPARATOR . $i)) {
                    \WP_CLI::debug(__FUNCTION__ . "::Folder {$path} does not represent an existing blog");
                    $orphan_folders[] = $path;
                }
            }
        }

        return $orphan_folders;
    }

    /**
     * Execute the list of passed-in SQL statements.
     * E.g. To rename each of the tables passed in with a standard label + hashed table name.
     *
     * @param array $folders Full paths of files/folders to move.
     * @param int $limit Only attempt rename on this number of tables (when sorted alphabetically ASC).
     * @param bool $dryrun True to not actually run the queries, just print them.
     * @return \stdClass Result tallies {changed=><int>, failed=><int>}
     */
    private function move_folders(array $folders, int $limit = 0, bool $dryrun = false): \stdClass {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started with " . \count($folders) . " folders; \$limit={$limit}; \$dryrun={$dryrun}");

        $dryrun && \WP_CLI::debug("{$fxn}::Dry run, so do not actually make any changes");
        $limit && \WP_CLI::debug("{$fxn}::Limiting to {$limit} tables");

        $returnThis = (object) ['changed' => 0, 'failed' => 0];
        if (empty($folders) || $limit < 0) {
            \WP_CLI::debug("{$fxn}::No statements or invalid limit");
            return $returnThis;
        }

        if ($limit > 0) {
            $targets = \array_slice($folders, 0, $limit);
            \WP_CLI::debug("{$fxn}::Cut folders down to " . \count($targets) . ' $folders');
        } else {
            $targets = $folders;
        }

        // Where to put all the moved folders.
        $wpuploadsdir = $this->get_wpuploads_dir();
        $target_basedir = $wpuploadsdir . DIRECTORY_SEPARATOR . (new \ReflectionClass(__CLASS__))->getShortName();
        \WP_CLI::debug("{$fxn}::Built \$target_basedir={$target_basedir}");

        if (!$this->dir_present_writable($target_basedir)) {
            \WP_CLI::error("{$fxn}::The folder {$target_basedir} could not be created");
        }
        \WP_CLI::success("{$fxn}::Created directory {$target_basedir}");

        $htaccess_location = $target_basedir . DIRECTORY_SEPARATOR . '.htaccess';
        if (!file_exists($htaccess_location)) {
            $htaccess_content = <<<EOF
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>
EOF;
            $success = file_put_contents($htaccess_location, $htaccess_content);
            if (!$success) {
                \WP_CLI::error("{$fxn}::Failed to create .htaccess file in {$htaccess_location}");
            }
        }

        $result = false;
        foreach ($targets as &$t) {
            \WP_CLI::debug("{$fxn}::Looking at \$t={$t}");

            switch (true) {
                case (realpath($t) == realpath($wpuploadsdir)):
                    //The path must not be the wp_uploads folder itself.
                    \WP_CLI::warning("{$fxn}::Skipping invalid request to move the wp uploads folder itself");
                    continue 2;
                case(\stripos(dirname($t), $wpuploadsdir) === false):
                    //Security: the path must be under the WP uploads dir.
                    \WP_CLI::warning("{$fxn}::Skipping invalid request to move file bc its parent dir " . dirname($t) . "is not under the wp uploads folder={$wpuploadsdir}");
                    continue 2;
                case($dryrun):
                    $result = false;
                    $returnThis->failed++;
                    \WP_CLI::success("(Dry run) Move \$t={$t} to ");
                    continue 2;
            }

            //Get the target location relative to the base dir.
            $target_relativedir = str_replace($wpuploadsdir . DIRECTORY_SEPARATOR, '', $t);
            \WP_CLI::debug("{$fxn}::Built \$target_relativedir={$target_relativedir}");
            $target_new_subparentdir = dirname($target_basedir . DIRECTORY_SEPARATOR . $target_relativedir);
            \WP_CLI::debug("{$fxn}::Built \$target_new_subdir={$target_new_subparentdir}");

            //Re-create the wp_uploads folder structure in this plugin's labelled dir.
            if (!$this->dir_present_writable($target_new_subparentdir)) {
                \WP_CLI::error("{$fxn}::The folder {$target_basedir} could not be created");
            }
            \WP_CLI::success("{$fxn}::Created directory {$target_basedir}");

            //Move target folders.
//            //            $result = rename($t, $target_relativedir.DIRECTORY_SEPARATOR.basename($t));
//            if ($result) {
//                $returnThis->changed++;
//                \WP_CLI::success("{$fxn}::\$t={$t}");
//            } else {
//                $returnThis->failed++;
//                \WP_CLI::warning("{$fxn}::\$t={$t}");
//            }
            //\WP_CLI::debug("{$fxn}::Got \$result=" . \print_r($result, true));
        }
        return $returnThis;
    }

    //==========================================================================
    // Utility methods specific to WordPress
    //==========================================================================
    private function get_wpuploads_dir(): string {
        if (empty($this->wpuploadsdir)) {
            $this->wpuploadsdir = \wp_upload_dir()['basedir'];
        }
        return $this->wpuploadsdir;
    }

    //==========================================================================
    // Utility methods not specific to this class.
    //==========================================================================

    private function dir_present_writable(string $dir_to_check, $perms = 0755): bool {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started with \$dir_to_check={$dir_to_check}; \$perms={$perms}");

        $success = true;

        if (\is_file($dir_to_check)) {
            \WP_CLI::debug("{$fxn}::is_file=true for \$dir_to_check={$dir_to_check}; \$perms={$perms}");
            throw new InvalidArgumentException("Cannot create a directory overwriting an existing file {$dir_to_check}");
        }
        if (!\is_dir($dir_to_check)) {
            \WP_CLI::debug("{$fxn}::is_dir=false for \$dir_to_check={$dir_to_check}, so try to make it");
            $success = mkdir($dir_to_check, $perms);
            \WP_CLI::debug("{$fxn}::mkdir={$success} for \$dir_to_check={$dir_to_check}");
            if (!$success) {
                return false;
            }
        }
        if (!\is_writable($dir_to_check)) {
            \WP_CLI::debug("{$fxn}::is_writable={$success} for \$dir_to_check={$dir_to_check}, so try to chmod");
            $success = chmod($dir_to_check, $perms);
            \WP_CLI::debug("{$fxn}::chmod={$success} for \$dir_to_check={$dir_to_check}");
        }

        \WP_CLI::debug("{$fxn}::About to return \$success={$success}");
        return $success;
    }

    /**
     * Get the child site number from a WP Multisite DB table name like wp_3682_options.
     * If no such number is found, returns zero 0.
     *
     * @ref https://github.com/shawnhooper/delete-orphaned-multisite-tables/blob/master/wp-cli.php
     * @param type $tablename String DB table name.
     * @return int The child site number, or zero if nothing found.
     */
    private function get_number_from_table_name($tablename): int {
        $noPrefix = \preg_replace('/^' . $this->db->prefix . '/', '', $tablename);
        return (int) \substr($noPrefix, 0, \strpos($noPrefix, '_'));
    }

}

\WP_CLI::add_command('wp-multisite-orphans', __NAMESPACE__ . '\\WP_Multisite_Orphans');
