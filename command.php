<?php

namespace mwithheld\Orphan_Tables\Commands;

// Internal API ref https://make.wordpress.org/cli/handbook/references/internal-api/
use WP_CLI;
use WP_CLI\Utils;

if (!defined('WP_CLI') || !class_exists('WP_CLI') || empty(WP_CLI)) {
    echo 'WP_CLI is not defined';
    return;
}

class Orphan_Tables extends \WP_CLI_Command {

    protected $db;
    private $_nl = "\n";
    private $_rename_label;
    private $_flags = [
        'dryrun' => ["name" => 'dry-run', 'default' => false],
        'limit'  => ["name" => 'limit', 'default' => 0],
    ];

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
            WP_CLI::error('This is not a multisite installation. This command is for multisite only.');
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
     * wp-cli orphan-tables get_label
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
     * wp-cli orphan-tables list_orphaned
     *
     * @return void
     */
    public function list_orphaned(): array {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        $tablenames = $this->get_orphan_tables();
        if (\count($tablenames) < 1) {
            \WP_CLI::error("No tables found");
            return [];
        }

        foreach ($tablenames as &$tablename) {
            \WP_CLI::log("$tablename");
        }
        \WP_CLI::success(\count($tablenames) . " orphan tables names");
        return $tablenames;
    }

    /**
     * Prints drop statements for orphan tables; no changes are made. Renamed tables do not show up as orphaned tables. No parameters.
     *
     * ## EXAMPLE
     * wp-cli orphan-tables list_drops
     *
     * @return void
     */
    public function list_drops(): array {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        $internal = false;
        $caller = \array_slice(\debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2), 1, 1)[0];
        //\WP_CLI::debug("{$fxn}::caller=".\print_r($caller, true));
        if (isset($caller['class']) && !empty($caller['class']) && $caller['class'] == __CLASS__) {
            $internal = true;
            \WP_CLI::debug("{$fxn}::This is an interal function call");
        }

        $tablenames = $this->get_orphan_tables();
        $returnThis = [];
        if (\count($tablenames) < 1) {
            !$internal && \WP_CLI::error("No tables found");
            return $returnThis;
        }

        $returnThis = $this->create_drop_statements($tablenames);
        foreach ($returnThis as &$sql) {
            !$internal && \WP_CLI::log($sql);
        }
        !$internal && \WP_CLI::success(\count($tablenames) . " orphan tables drop statements");
        return $returnThis;
    }

    /**
     * Prints drop statements for renamed tables; no changes are made. No parameters.
     *
     * ## EXAMPLE
     * wp-cli orphan-tables list_drop_renamed
     *
     * @return void
     */
    public function list_drop_renamed(): array {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        $internal = false;
        $caller = \array_slice(\debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2), 1, 1)[0];
        //\WP_CLI::debug("{$fxn}::caller=".\print_r($caller, true));
        if (isset($caller['class']) && !empty($caller['class']) && $caller['class'] == __CLASS__) {
            $internal = true;
            \WP_CLI::debug("{$fxn}::This is an interal function call");
        }

        $tablenames = $this->get_renamed_tables();
        $returnThis = [];
        if (\count($tablenames) < 1) {
            !$internal && \WP_CLI::error("No tables found");
            return $returnThis;
        }

        $returnThis = $this->create_drop_statements($tablenames);
        foreach ($returnThis as &$sql) {
            !$internal && \WP_CLI::log($sql);
        }
        !$internal && \WP_CLI::success(\count($tablenames) . " orphan tables drop statements");
        return $returnThis;
    }

    /**
     * Prints rename statements for orphan tables using the standard label {get_label}; no changes are made. Renamed tables do not show up as orphaned tables. No parameters.
     *
     * ## EXAMPLE
     * wp-cli orphan-tables list_renames
     *
     * @return void
     */
    public function list_renames(): array {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        $internal = false;
        $caller = \array_slice(\debug_backtrace(DEBUG_BACKTRACE_PROVIDE_OBJECT | DEBUG_BACKTRACE_IGNORE_ARGS, 2), 1, 1)[0];
        //\WP_CLI::debug("{$fxn}::caller=".\print_r($caller, true));
        if (isset($caller['class']) && !empty($caller['class']) && $caller['class'] == __CLASS__) {
            $internal = true;
            \WP_CLI::debug("{$fxn}::This is an interal function call");
        }

        $tablenames = $this->get_orphan_tables();
        $returnThis = [];
        if (\count($tablenames) < 1) {
            !$internal && \WP_CLI::error("No tables found");
            return $returnThis;
        }

        foreach ($tablenames as &$tablename) {
            $tablename_new = \str_replace($tablename, "{$this->db->prefix}" . $this->_rename_label . '_' . \sha1("{$tablename}"), "{$tablename}");
            $sql = "RENAME TABLE {$tablename} TO {$tablename_new};";
            !$internal && \WP_CLI::log($sql);
            $returnThis[] = $sql;
        }
        !$internal && \WP_CLI::success(\count($tablenames) . " orphan tables rename statements");
        return $returnThis;
    }

    /**
     * Prints a list of orphaned tables renamed by this package; no changes are made. Renamed tables do not show up as orphaned tables. No parameters.
     *
     * ## EXAMPLE
     * wp-cli orphan-tables list_renamed
     *
     * @return void
     */
    public function list_renamed(): array {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        $tablenames = $this->get_renamed_tables();
        if (\count($tablenames) < 1) {
            \WP_CLI::error("No tables found");
            return[];
        }

        foreach ($tablenames as &$tablename) {
            \WP_CLI::log("{$tablename}");
        }
        \WP_CLI::success(\count($tablenames) . " orphan tables renamed by this package");
        return $tablenames;
    }

    /**
     * Rename orphaned tables with a standard label + hashed table name. Renamed tables do not show up as orphaned tables.
     *
     * ## PARAMETERS
     *      --limit=<int> Only attempt rename on this number of tables (when sorted alphabetically ASC).
     *      --dry-run Do not actually make any changes - just show what would be done.
     *
     * ## EXAMPLE
     * wp-cli orphan-tables do_renames
     * wp-cli orphan-tables do_renames --dry-run
     * wp-cli orphan-tables do_renames --limit=1 --debug --dry-run --yes
     *
     * @param array $args Command-line arguments array from WP-CLI. Unused here.
     * @param array $assoc_args Command line flags from WP-CLI. Flags specifically used by this package:
     *
     * @return object {changed=><int>, failed=><int>}
     */
    public function do_renames(array $args, array $assoc_args): \stdClass {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started with args=" . \print_r($args, true) . '; $assoc_args=' . \print_r($assoc_args, true));

        \WP_CLI::confirm('BE CAREFUL, this cannot be undone so please backup your database before proceeding. Are you sure you want to proceed?', $assoc_args);

        $dryrun = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->dryrun->name, false);
        $dryrun && \WP_CLI::log("{$fxn}::Dry run, so do not actually make any changes");

        $limit = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->limit->name, 0);
        $limit && \WP_CLI::log("{$fxn}::Limiting to {$limit} tables");

        $results = $this->execute_statements($this->list_renames(), $limit, $dryrun);

        \WP_CLI::success("Processed " . $results->changed + $results->failed . " tables: Changed={$results->changed}; Failed={$results->failed}");
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
     * wp-cli orphan-tables do_drops
     * wp-cli orphan-tables do_drops --dry-run
     * wp-cli orphan-tables do_drops --limit=14 --debug --dry-run --yes
     *
     * @param array $args Command-line arguments array from WP-CLI. Unused here.
     * @param array $assoc_args Command line flags from WP-CLI. Flags specifically used by this package:
     *
     * @return \stdClass Result tallies {changed=><int>, failed=><int>}
     */
    public function do_drops(array $args, array $assoc_args): \stdClass {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started with args=" . \print_r($args, true) . '; $assoc_args=' . \print_r($assoc_args, true));

        \WP_CLI::confirm('BE CAREFUL, this cannot be undone so please backup your database before proceeding. Are you sure you want to proceed?', $assoc_args);

        $dryrun = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->dryrun->name, false);
        $dryrun && \WP_CLI::log("{$fxn}::Dry run, so do not actually make any changes");

        $limit = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->limit->name, 0);
        $limit && \WP_CLI::log("{$fxn}::Limiting to {$limit} tables");

        $results = $this->execute_statements($this->list_drops(), $limit, $dryrun);

        \WP_CLI::success("Processed " . $results->changed + $results->failed . " tables: Changed={$results->changed}; Failed={$results->failed}");
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
     * wp-cli orphan-tables do_drop_renamed
     * wp-cli orphan-tables do_drop_renamed --dry-run
     * wp-cli orphan-tables do_drop_renamed --limit=2 --debug --dry-run --yes
     *
     * @param array $args Command-line arguments array from WP-CLI. Unused here.
     * @param array $assoc_args Command line flags from WP-CLI. Flags specifically used by this package:
     *
     * @return \stdClass Result tallies {changed=><int>, failed=><int>}
     */
    public function do_drop_renamed(array $args, array $assoc_args): \stdClass {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started with args=" . \print_r($args, true) . '; $assoc_args=' . \print_r($assoc_args, true));

        \WP_CLI::confirm('BE CAREFUL, this cannot be undone so please backup your database before proceeding. Are you sure you want to proceed?', $assoc_args);

        $dryrun = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->dryrun->name, false);
        $dryrun && \WP_CLI::log("{$fxn}::Dry run, so do not actually make any changes");

        $limit = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->limit->name, 0);
        $limit && \WP_CLI::log("{$fxn}::Limiting to {$limit} tables");

        $results = $this->execute_statements($this->list_drop_renamed(), $limit, $dryrun);

        \WP_CLI::success("Processed " . $results->changed + $results->failed . " tables: Changed={$results->changed}; Failed={$results->failed}");
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
        foreach ($tablenames as &$tablename) {
            $sql = "DROP TABLE IF EXISTS {$tablename};";
            $returnThis[] = $sql;
        }
        return $returnThis;
    }

    /**
     * Execute the list of passed-in SQL statements.
     * E.g. To rename each of the tables passed in with a standard label + hashed table name.
     *
     * @param array $statements SQL RENAME TABLE commands e.g. from list_renamed().
     * @param int $limit Only attempt rename on this number of tables (when sorted alphabetically ASC).
     * @param bool $dryrun True to not actually run the queries, just print them.
     * @return \stdClass Result tallies {changed=><int>, failed=><int>}
     */
    private function execute_statements(array $statements, int $limit = 0, bool $dryrun = false): \stdClass {
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
            $statements_targeted = \array_slice($statements, 0, $limit);
            \WP_CLI::debug("{$fxn}::Cut statements down to " . \count($statements_targeted) . " \$statements");
        } else {
            $statements_targeted = $statements;
        }

        foreach ($statements_targeted as &$statement) {
            //\WP_CLI::debug("{$fxn}::Looking at \$statement={$statement}");
            if ($dryrun) {
                $result = false;
                $returnThis->failed++;
                \WP_CLI::success("{$fxn}::Dry run: \$statement={$statement}");
                continue;
            }

            $result = $this->db->query($statement);
            // Table renames do not return a success result.
            if (stripos($statement, 'RENAME TABLE ') !== false || $result) {
                $returnThis->changed++;
                \WP_CLI::success("{$fxn}::\$statement={$statement}");
            } else {
                $returnThis->failed++;
                \WP_CLI::error("{$fxn}::\$statement={$statement}");
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

        $sql = "SELECT table_name "
                . "FROM information_schema.tables "
                . "WHERE table_schema='{$this->db->dbname}' "
                . "AND table_name LIKE '{$this->db->prefix}{$this->_rename_label}%' "
                . "ORDER BY table_name";
        \WP_CLI::debug("{$fxn}::About to run sql={$sql}");

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

        $sql = "SELECT table_name "
                . "FROM information_schema.tables "
                . "WHERE table_schema='{$this->db->dbname}' "
                /* Restricting to 0-9 skips tables for the network-level */
                . "AND table_name REGEXP '{$this->db->prefix}[0-9]+_' "
                . "ORDER BY table_name";
        \WP_CLI::debug("{$fxn}::About to run sql={$sql}");
        $all_tables = $this->db->get_col($sql);
        \WP_CLI::debug(__FUNCTION__ . '::Found ' . \count($all_tables) . " tables from sql={$sql}");

        //These  blogs_ids represent actual multisite child blogs we will want to keep.
        $existing_blog_ids = $this->db->get_col("SELECT blog_id FROM {$this->db->blogs} ORDER BY blog_id");
        //Gather the orphaned table names here.
        $orphan_tablenames = [];

        //Search tables with name prefix containing non-existing blog IDs.
        foreach ($all_tables as &$tablename) {
            \WP_CLI::debug(__FUNCTION__ . "::Looking at \$table_name={$tablename}");
            \WP_CLI::debug(__FUNCTION__ . "::Looking at \$table_name={$tablename}");
            $table_blog_id = $this->get_number_from_table_name($tablename);
            \WP_CLI::debug(__FUNCTION__ . "::From \$tablename={$tablename} extracted \$table_blog_id={$table_blog_id}");
            if (empty($table_blog_id)) {
                \WP_CLI::debug(__FUNCTION__ . "::The \$tablename={$tablename} is not a multisite child site table");
                continue;
            }
            if (!in_array($table_blog_id, $existing_blog_ids)) {
                \WP_CLI::debug(__FUNCTION__ . "::\$table_name={$tablename} does not represent an existing blog");
                $orphan_tablenames[] = $tablename;
            }
        }

        return $orphan_tablenames;
    }

    //==========================================================================
    // Utility methods not specific to this class.
    //==========================================================================

    /**
     * Get the child site number from a WP multisite DB table name like wp_3682_options.
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

\WP_CLI::add_command('orphan-tables', __NAMESPACE__ . '\\Orphan_Tables');
