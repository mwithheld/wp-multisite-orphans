<?php

namespace uvic\Orphan_Tables\Commands;

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
        'dryrun' => ["name"=> 'dry-run', 'default'=>false],
        'limit' => ["name"=> 'limit', 'default'=>0],
        ];

    //==========================================================================
    // Methods that provide a user-accessible interface for this plugin.
    //==========================================================================

    public function __construct() {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started");

        if (!is_multisite()) {
            WP_CLI::error('This is not a multisite installation. This command is for multisite only.');
        }

        $this->db = $GLOBALS['wpdb'];
        $this->_rename_label = \str_replace(__NAMESPACE__ . '\\', '', __CLASS__);
        $this->_flags = (object)$this->_flags;
        foreach($this->_flags as $key=>$value) {
            $this->_flags->$key = (object)$value;
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
     * Prints the rename label; no changes are made.
     * No parameters.
     * 
     * @return void
     */
    public function get_label() {
        \WP_CLI::success("$this->_rename_label");
        return $this->_rename_label;
    }

    /**
     * Prints orphan table names in plain text; no changes are made.
     * No parameters.
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
     * Prints drop statements for orphan tables; no changes are made.
     * No parameters.
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

        foreach ($tablenames as &$tablename) {
            $sql = "DROP TABLE IF EXISTS {$tablename};";
            !$internal && \WP_CLI::log("$sql");
            $returnThis[] = $sql;
        }
        !$internal && \WP_CLI::success(\count($tablenames) . " orphan tables drop statements");
        return $returnThis;
    }

    /**
     * Prints rename statements for orphan tables using the standard label {get_label}; no changes are made.
     * No parameters.
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
     * Prints a list of orphaned tables renamed by this plugin; no changes are made.
     * No parameters.
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
        \WP_CLI::success(\count($tablenames) . " orphan tables renamed by this plugin");
        return $tablenames;
    }

    /**
     * Rename orphaned tables with a standard label + hashed table name.
     * @param int $limit Only attempt rename on this number of tables (when sorted alphabetically ASC).
     * 
     * ## Example
     * wp-cli orphan-tables do_renames --limit=1 --debug --dry-run --yes
     * 
     * @return object {renamed=><int>, failed=><int>}
     */
    public function do_renames(array $args, array $assoc_args): \stdClass {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started with args=" . \print_r($args, true) . '; $assoc_args=' . \print_r($assoc_args, true));

        $tablenames = $this->get_renamed_tables();
        if (\count($tablenames) < 1) {
            \WP_CLI::error("No tables found");
            return [];
        }

        WP_CLI::confirm('BE CAREFUL, this cannot be undone so please backup your database before proceeding. Are you sure you want to proceed?', $assoc_args);

        $dryrun = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->dryrun->name, false);
        $dryrun && \WP_CLI::log("{$fxn}::Dry run, so do not actually make any changes");

        $limit = \WP_CLI\Utils\get_flag_value($assoc_args, $this->_flags->limit->name, 0);
        $limit && \WP_CLI::log("{$fxn}::Limiting to {$limit} tables");

        $results = $this->rename_tables($this->list_drops(), $limit, $dryrun);

        \WP_CLI::success("Total " . \count($tablenames) . " orphan tables; Renamed={$results->renamed}; Failed={$results->failed}");
        return $results;
    }

    //==========================================================================
    // Methods specific to this class.
    //==========================================================================

    /**
     * Rename each of the tables passed in with a standard label + hashed table name.
     * 
     * @param array $statements SQL RENAME TABLE commands e.g. from list_renamed().
     * @param int $limit Only attempt rename on this number of tables (when sorted alphabetically ASC).
     * @param bool $dryrun True to not actually run the queries, just print them.
     * @return \stdClass
     */
    private function rename_tables(array $statements, int $limit = 0, bool $dryrun = false): \stdClass {
        $fxn = \implode('::', [__CLASS__, __FUNCTION__]);
        \WP_CLI::debug("{$fxn}::Started with " . \count($statements) . " statements; \$limit={$limit}; \$dryrun={$dryrun}");

        $dryrun && \WP_CLI::debug("{$fxn}::Dry run, so do not actually make any changes");
        $limit && \WP_CLI::debug("{$fxn}::Limiting to {$limit} tables");

        $returnThis = (object) ['renamed' => 0, 'failed' => 0];
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

            if ($result = $this->db->query($statement)) {
                $returnThis->renamed++;
                \WP_CLI::success("{$fxn}::\$statement={$statement}");
            } else {
                $returnThis->failed++;
                \WP_CLI::error("{$fxn}::\$statement={$statement}");
            }
            //\WP_CLI::debug("{$fxn}::Got \$result=" . \print_r($result, true));
        }

        return $returnThis;

//            if ($this->db->query("DROP TABLE IF EXISTS $table")) {
//                $i++;
//                \WP_CLI::success("Succesfully dropped table " . $table);
//            } else {
//                $j++;
//                \WP_CLI::success("Could not drop table " . $table);
//            }
    }

    /**
     * Get a list of orphaned tables renamed by this plugin.
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
     * Get a list of tables that do not belong to a WP blog.
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

    private function get_number_from_table_name($tablename): int {
        $noPrefix = \preg_replace('/^' . $this->db->prefix . '/', '', $tablename);
        return (int) \substr($noPrefix, 0, \strpos($noPrefix, '_'));
    }

}

\WP_CLI::add_command('orphan-tables', __NAMESPACE__ . '\\Orphan_Tables');
