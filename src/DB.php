<?php

namespace Arshwell\Monolith;

use Arshwell\Monolith\DevTool\DevToolDebug;
use Arshwell\Monolith\StaticHandler;
use PDOException;
use Exception;
use PDO;

/**
 * DB class for sending queries to MySQL server.

 * @package https://github.com/arshwell/monolith
*/
final class DB {
    private static $pdos        = array();
    private static $backups     = array();
    private static $tb_prefixes = array();

    /** @var string */
    private static $defaultDbConnKey = NULL;

    final static function connect (string $dbConnKey): void {
        if (self::$defaultDbConnKey == null) {
            // default dbConn key
            self::$defaultDbConnKey = array_key_first(StaticHandler::getEnvConfig('databases')['conn']);
        }

        if (!isset(self::$pdos[$dbConnKey])) {
            self::$tb_prefixes[$dbConnKey] = StaticHandler::getEnvConfig('databases.conn.'.$dbConnKey.'.prefix');
            self::$pdos[$dbConnKey] = new PDO(
                'mysql:host='.StaticHandler::getEnvConfig('databases.conn.'.$dbConnKey.'.host').';dbname='.StaticHandler::getEnvConfig('databases.conn.'.$dbConnKey.'.name').';charset='.StaticHandler::getEnvConfig('databases.conn.'.$dbConnKey.'.charset'),
                StaticHandler::getEnvConfig('databases.conn.'.$dbConnKey.'.username'),
                StaticHandler::getEnvConfig('databases.conn.'.$dbConnKey.'.password')
            );

            self::$pdos[$dbConnKey]->query(
                "SET SQL_MODE='NO_AUTO_CREATE_USER,NO_ENGINE_SUBSTITUTION'; SET NAMES '".StaticHandler::getEnvConfig('databases.conn.'.$dbConnKey.'.charset')."'; SET COLLATE '".StaticHandler::getEnvConfig('databases.conn.'.$dbConnKey.'.charset')."_general_ci';"
            );

            self::$backups = StaticHandler::getEnvConfig('databases.backups');

            // Supervisors are alerted if there are problems.
            if (StaticHandler::supervisor()) {
                self::$pdos[$dbConnKey]->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            }
        }
    }

    final static function key (): ?string {
        return self::$defaultDbConnKey;
    }

    /* TCL (Transaction Control Language) */

        final static function beginTransaction (): void {
            self::$pdos[self::$defaultDbConnKey]->beginTransaction();

            if (isset(self::$backups[self::$defaultDbConnKey])) {
                foreach (self::$backups[self::$defaultDbConnKey] as $db_key) {
                    self::$pdos[$db_key]->beginTransaction();
                }
            }
        }
        final static function rollback (): void {
            if (self::$pdos[self::$defaultDbConnKey]->inTransaction()) {
                self::$pdos[self::$defaultDbConnKey]->rollback();

                if (isset(self::$backups[self::$defaultDbConnKey])) {
                    foreach (self::$backups[self::$defaultDbConnKey] as $db_key) {
                        self::$pdos[$db_key]->rollback();
                    }
                }
            }
        }
        final static function commit (): void {
            if (self::$pdos[self::$defaultDbConnKey]->inTransaction()) {
                self::$pdos[self::$defaultDbConnKey]->commit();

                if (isset(self::$backups[self::$defaultDbConnKey])) {
                    foreach (self::$backups[self::$defaultDbConnKey] as $db_key) {
                        self::$pdos[$db_key]->commit();
                    }
                }
            }
        }

        final private static function prefix (string $tb_prefix, string $query, bool $dml_dql = false, bool $ddl = false): string {
            // table.*          => pr_table.*
            // table.column     => pr_table.column
            // table.`column`   => pr_table.`column`
            $query = preg_replace(
                "/(^|\(|\s|,) ((?:\w+\.\*) | (?:\w+\.\w+(?::lg)?) | (?:\w+\.`\w+(?::lg)?`)) (,|\s|\)|;|$)/x",
                '$1'.$tb_prefix.'$2$3',
                $query
            );

            // `table`.*        => `pr_table`.*
            // `table`.column   => `pr_table`.column
            // `table`.`column` => `pr_table`.`column`
            $query = preg_replace(
                "/(^|\(|\s|,) (`) ((?:\w+`\.\*) | (?:\w+`\.\w+(?::lg)?) | (?:\w+`\.`\w+(?::lg)?`)) (,|\s|\)|;|$)/x",
                '$1$2'.$tb_prefix.'$3$4',
                $query
            );

            if ($dml_dql || $ddl) {
                $commands = array();

                // Data Manipulation Language (DML) && Data Query Language (DQL)
                if ($dml_dql) {
                    $commands = array_merge($commands, array(
                        'INSERT INTO', 'UPDATE', 'FROM'
                    ));
                }

                // Data Definition Language (DDL)
                if ($ddl) {
                    $commands = array_merge($commands, array(
                        'CREATE TABLE', 'DROP TABLE', 'ALTER TABLE', 'TRUNCATE TABLE'
                    ));
                }

                // table => pr_table
                $query = preg_replace(
                    // "/(^|\(|\s|,) ((?:INSERT INTO) | (?:UPDATE) | (?:FROM) | (?:CREATE TABLE) | (?:DROP TABLE) | (?:ALTER TABLE) | (?:TRUNCATE TABLE)) (\s+) (\w+) (,|\s|\)|;|$)/x",
                    "/(^|\(|\s|,) (".implode(' | ', array_map(function($command) {
                        return "(?:$command)";
                    }, $commands)).") (\s+) (\w+) (,|\s|\)|;|$)/x",
                    '$1$2$3'.$tb_prefix.'$4$5',
                    $query
                );

                // `table` => `pr_table`
                $query = preg_replace(
                    // "/(^|\(|\s|,) ((?:INSERT INTO) | (?:UPDATE) | (?:FROM) | (?:CREATE TABLE) | (?:DROP TABLE) | (?:ALTER TABLE) | (?:TRUNCATE TABLE)) (\s+`) (\w+`) (,|\s|\)|;|$)/x",
                    "/(^|\(|\s|,) (".implode(' | ', array_map(function($command) {
                        return "(?:$command)";
                    }, $commands)).") (\s+`) (\w+`) (,|\s|\)|;|$)/x",
                    '$1$2$3'.$tb_prefix.'$4$5',
                    $query
                );
            }

            return $query;
        }

        final private static function languages (string $dbConnKey, string $query, string $class, array &$params = NULL): string {
            $regex = "/(^|\(|\s|,|`)((".self::$tb_prefixes[$dbConnKey].")(\w+)\.)?(\w+)(:lg)((\s+AS\s+\w+)(:lg))?(`|,|\s|\)|;|$)/i";

            if (preg_match($regex, $query, $matches)) {
                $nr_langs_per_column = array(); // for replacing ?:lg with real count of placeholders

                $languages = NULL;

                if (empty($params[':lg'])) {
                    if (($class)::translationTimes() > 0) {
                        $languages = array((($class)::TRANSLATOR)::get());
                    }
                    else {
                        throw new Exception(
                            "|Arshwell| ".static::class."::languages() query can't replace :lg placeholders;
                            Should be send as params or fetched from your class const TRANSLATOR"
                        );
                    }
                }

                $query = preg_replace_callback(
                    $regex,
                    function ($matches) use ($languages, $params, &$nr_langs_per_column) {
                        /***************** example ******************
                        $matches (
                            [0] =>  br_services.title:lg AS anaaremere:lg,
                            [1] =>
                            [2] => br_services.
                            [3] => br_
                            [4] => services
                            [5] => title
                            [6] => :lg
                            [7] =>  AS anaaremere:lg
                            [8] =>  AS anaaremere
                            [9] => :lg
                            [10] => ,
                        )
                        ********************************************/

                        if (!empty($params[':lg'])) {
                            $languages = (array)($params[':lg'][$matches[4].'.'.$matches[5]] ?? $params[':lg']);
                        }

                        $nr_langs_per_column[] = count($languages); // record languages mentioned for this column

                        return $matches[1].implode(',', array_map(function (string $lg) use ($matches) {
                            return $matches[2].$matches[5].'_'.$lg.($matches[7] ? $matches[8].'_'.$lg : '');
                        }, $languages)).$matches[10];
                    },
                    $query
                );

                // Create all necessary placeholders
                // according to every mentioned languages of the column.
                foreach ($nr_langs_per_column as $nr) {
                    $query = preg_replace('/\?\:lg/', rtrim(str_repeat('?, ', $nr), ", "), $query, 1);
                }
                foreach ($nr_langs_per_column as $nr) {
                    $query = preg_replace('/(\:\w+)\:lg/', rtrim(str_repeat('$1, ', $nr), ", "), $query, 1);
                }

                unset($params[':lg']); // destroy if exists
            }

            return $query;
        }


    /* DLQ (Data Query Language) */

        final static function get (string $class, int $id, string $columns): ?array {
            $dbConnKey = (defined("{$class}::DB_CONN_KEY") ? ($class)::DB_CONN_KEY : self::$defaultDbConnKey);

            $query = "SELECT ".$columns." FROM ".self::$tb_prefixes[$dbConnKey].($class)::TABLE ." WHERE ". ($class)::PRIMARY_KEY ." = ". $id;

            $query = self::languages($dbConnKey, $query.';', $class, $params);

            try {
                $result = self::$pdos[$dbConnKey]->prepare($query);
                $result->execute();
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $query);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }

            return ($result->fetch(PDO::FETCH_ASSOC) ?: array());
        }

        final static function column (string $class, string $column, string $where = NULL, array $params = NULL): array {
            $dbConnKey = (defined("{$class}::DB_CONN_KEY") ? ($class)::DB_CONN_KEY : self::$defaultDbConnKey);

            $query = "SELECT ". $column ." FROM ".self::$tb_prefixes[$dbConnKey].($class)::TABLE;

            if ($where) {
                $query .= " WHERE ". self::prefix(self::$tb_prefixes[$dbConnKey], $where, true);
            }

            $query = self::languages($dbConnKey, $query.';', $class, $params);

            try {
                $result = self::$pdos[$dbConnKey]->prepare($query);
                $result->execute($params);
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $query, $params);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }

            return array_column($result->fetchAll(PDO::FETCH_NUM), 0);
        }

        final static function field (string $class, string $column, string $where = NULL, array $params = NULL): ?string {
            $dbConnKey = (defined("{$class}::DB_CONN_KEY") ? ($class)::DB_CONN_KEY : self::$defaultDbConnKey);

            $query = "SELECT ". $column ." FROM ".self::$tb_prefixes[$dbConnKey].($class)::TABLE;

            if ($where) {
                $query .= " WHERE ". self::prefix(self::$tb_prefixes[$dbConnKey], $where, true);
            }
            $query .= " LIMIT 1;";

            $query = self::languages($dbConnKey, $query, $class, $params);

            try {
                $result = self::$pdos[$dbConnKey]->prepare($query);
                $result->execute($params);
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $query, $params);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }

            $result = $result->fetch(PDO::FETCH_NUM);

            return ($result[0] ?? NULL);
        }

        final static function first (array $sql, array $params = NULL): ?array {
            $dbConnKey = (defined("{$sql['class']}::DB_CONN_KEY") ? ($sql['class'])::DB_CONN_KEY : self::$defaultDbConnKey);

            $sql['columns'] = self::prefix(self::$tb_prefixes[$dbConnKey], $sql['columns']);

            $query = "SELECT ".$sql['columns']." FROM ".self::$tb_prefixes[$dbConnKey].($sql['class'])::TABLE;

            if (!empty($sql['join'])) {
                $join = $sql;
                while (isset($join['join'])) {
                    $join = $join['join'];
                    $query .= " ".$join[0]." JOIN ". self::$tb_prefixes[$dbConnKey] . $join[1]." ON ".preg_replace("/(\w+[.]\w+)/", self::$tb_prefixes[$dbConnKey]."$1", $join[2]);
                }
            }
            else if (!empty($sql['joins'])) {
                foreach ($sql['joins'] as $join) {
                    $query .= " ".$join['type']." JOIN ". self::$tb_prefixes[$dbConnKey] . $join['table']." ON ".self::prefix(self::$tb_prefixes[$dbConnKey], $join['on']);
                }
            }

            if (isset($sql['where'])) {
                $query .= " WHERE ". self::prefix(self::$tb_prefixes[$dbConnKey], $sql['where'], true);
            }

            if (isset($sql['order'])) {
                $query .= " ORDER BY ". self::prefix(self::$tb_prefixes[$dbConnKey], $sql['order']);
            }
            $query .= " LIMIT 1;";

            $query = self::languages($dbConnKey, $query, $sql['class'], $params);

            try {
                $result = self::$pdos[$dbConnKey]->prepare($query);
                $result->execute($params);
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $query, $params);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }

            return ($result->fetch(PDO::FETCH_ASSOC) ?: NULL);
        }

        final static function count (string $class, string $where = NULL, array $params = NULL): int {
            $dbConnKey = (defined("{$class}::DB_CONN_KEY") ? ($class)::DB_CONN_KEY : self::$defaultDbConnKey);

            $query = "SELECT COUNT(*) FROM ". self::$tb_prefixes[$dbConnKey].($class)::TABLE;

            if ($where) {
                $query .= " WHERE ". self::prefix(self::$tb_prefixes[$dbConnKey], $where, true);
            }

            $query = self::languages($dbConnKey, $query.';', $class, $params);

            try {
                $result = self::$pdos[$dbConnKey]->prepare($query);
                $result->execute($params);
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $query, $params);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }

            $result = $result->fetch(PDO::FETCH_ASSOC);

            return ($result['COUNT(*)'] ?? 0);
        }

        final static function all (string $class, $columns, string $order = NULL): array {
            $dbConnKey = (defined("{$class}::DB_CONN_KEY") ? ($class)::DB_CONN_KEY : self::$defaultDbConnKey);

            $query = "SELECT ".$columns." FROM ".self::$tb_prefixes[$dbConnKey].($class)::TABLE;

            if ($order) {
                $query .= " ORDER BY ". $order;
            }

            $query = self::languages($dbConnKey, $query.';', $class, $params);

            try {
                $result = self::$pdos[$dbConnKey]->prepare($query);
                $result->execute();
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $query);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }

            return $result->fetchAll(PDO::FETCH_ASSOC);
        }

        final static function select (array $sql, array $params = NULL): array {
            $dbConnKey = (defined("{$sql['class']}::DB_CONN_KEY") ? ($sql['class'])::DB_CONN_KEY : self::$defaultDbConnKey);

            if (trim($sql['columns']) != '*' && isset($sql['sort']) && !preg_match("/(^(\s+)?|.+,(\s+)?)". $sql['sort'] ."((\s+)?,.+|$)/", $sql['columns'])) {
                $sql['columns'] = $sql['columns'] .', '. $sql['sort'];
            }

            $sql['columns'] = self::prefix(self::$tb_prefixes[$dbConnKey], $sql['columns']);

            $query = "SELECT ".$sql['columns']." FROM ".self::$tb_prefixes[$dbConnKey].($sql['class'])::TABLE;

            if (!empty($sql['join'])) {
                $join = $sql;
                while (isset($join['join'])) {
                    $join = $join['join'];

                    $query .= " ".$join[0]." JOIN ". self::$tb_prefixes[$dbConnKey] . $join[1]." ON ".self::prefix(self::$tb_prefixes[$dbConnKey], $join[2]);
                }
            }
            else if (!empty($sql['joins'])) {
                foreach ($sql['joins'] as $join) {
                    $query .= " ".$join['type']." JOIN ". self::$tb_prefixes[$dbConnKey] . $join['table']." ON ".self::prefix(self::$tb_prefixes[$dbConnKey], $join['on']);
                }
            }

            if (isset($sql['where'])) {
                $query .= " WHERE ". self::prefix(self::$tb_prefixes[$dbConnKey], $sql['where'], true);
            }
            if (isset($sql['group'])) {
                $query .= " GROUP BY ".self::prefix(self::$tb_prefixes[$dbConnKey], $sql['group']);
            }
            if (isset($sql['order'])) {
                $query .= " ORDER BY ". self::prefix(self::$tb_prefixes[$dbConnKey], $sql['order']);
            }
            if (isset($sql['limit'])) {
                $query .= " LIMIT ".$sql['limit'];
            }
            if (isset($sql['offset'])) {
                $query .= " OFFSET ".$sql['offset'];
            }

            $query = self::languages($dbConnKey, $query.';', $sql['class'], $params);

            try {
                $result = self::$pdos[$dbConnKey]->prepare($query);
                $result->execute($params);
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $query, $params);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }

            if (!isset($sql['sort'])) {
                return $result->fetchAll(PDO::FETCH_ASSOC);
            }
            else {
                $results = array();
                foreach ($result->fetchAll(PDO::FETCH_ASSOC) as $row) {
                    $results[$row[$sql['sort']]][] = $row;
                }
                return $results;
            }
        }

    /* DML (Data Manipulation Language) */

        final static function insert (string $class, string $columns, $values, array $params = NULL): int {
            $dbConnKey = (defined("{$class}::DB_CONN_KEY") ? ($class)::DB_CONN_KEY : self::$defaultDbConnKey);

            $query = "INSERT INTO ". self::$tb_prefixes[$dbConnKey].($class)::TABLE ." (". $columns .") VALUES (". (is_string($values) ? $values : implode('),(', array_map(function ($columns) {
                return (is_array($columns) ? implode(', ', $columns) : $columns);
            }, $values))) .");";

            $query = self::languages($dbConnKey, $query, $class, $params);

            try {
                self::$pdos[$dbConnKey]->prepare($query)->execute($params);

                    if (isset(self::$backups[$dbConnKey])) {
                        foreach (self::$backups[$dbConnKey] as $db_key) {
                            self::$pdos[$db_key]->prepare($query)->execute($params);
                        }
                    }

                return self::$pdos[$dbConnKey]->lastInsertId();
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $query, $params);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }
        }

        final static function update (array $sql, array $params = NULL): int {
            $dbConnKey = (defined("{$sql['class']}::DB_CONN_KEY") ? ($sql['class'])::DB_CONN_KEY : self::$defaultDbConnKey);

            $query = "UPDATE ". self::$tb_prefixes[$dbConnKey].($sql['class'])::TABLE ." SET ". preg_replace("/(?<=^|,)(\s*\w+(\:lg)?)\s*(?=(,|$))/x", "$1 = ?", $sql['set']);

            /* Back up for preg_replace() regex.
                Add to unassigned columns, from $sql['set'], an " = ?".

                1. preg_replace(['/([a-zA-Z]+)\s*(?:(,)|$)/i', '/ +,/i'], ['\1 = ?\2', ','], $sql['set']);
                2. preg_replace("/ (?<= ^|,|,\s)  ([a-z]+)  (?=\s*(,|$)) /x", "$1 = ?", $sql['set']);
                3. preg_replace("/(?<=^|,)(\s*\w+(\:lg)?)\s*(?=(,|$))/x", "$1 = ?", $sql['set']);

            TODO: Don't delete this comment 'till you are sure that used preg_replace() is perfect always */

            if (isset($sql['where'])) {
                $query .= " WHERE ". self::prefix(self::$tb_prefixes[$dbConnKey], $sql['where'], true);
            }

            $query = self::languages($dbConnKey, $query.';', $sql['class'], $params);

            try {
                $result = self::$pdos[$dbConnKey]->prepare($query);

                if ($params) {
                    foreach ($params as $dbConnKey => $param) {
                        $result->bindValue(
                            is_int($dbConnKey) ? ($dbConnKey)+1 : $dbConnKey,
                            $param,
                            // really check if integer, because very long digits crash
                            (is_numeric($param) && $param == (int)$param) ? PDO::PARAM_INT : PDO::PARAM_STR
                        );
                    }
                }

                $result->execute();

                    if (isset(self::$backups[$dbConnKey])) {
                        foreach (self::$backups[$dbConnKey] as $db_key) {
                            self::$pdos[$db_key]->prepare($query)->execute($params);
                        }
                    }

                return $result->rowCount();
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $query, $params);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }
        }

        final static function delete (string $class, string $where = NULL, array $params = NULL): int {
            $dbConnKey = (defined("{$class}::DB_CONN_KEY") ? ($class)::DB_CONN_KEY : self::$defaultDbConnKey);

            $query = "DELETE FROM ". self::$tb_prefixes[$dbConnKey].($class)::TABLE;

            if ($where) {
                $query .= " WHERE ". $where;
            }

            $query = self::languages($dbConnKey, $query.';', $class, $params);

            try {
                $result = self::$pdos[$dbConnKey]->prepare($query);
                $result->execute($params);

                    if (isset(self::$backups[$dbConnKey])) {
                        foreach (self::$backups[$dbConnKey] as $db_key) {
                            self::$pdos[$db_key]->prepare($query)->execute($params);
                        }
                    }

                return $result->rowCount();
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $query, $params);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }
        }

    /* DDL (Data Definition Language) */

        final static function tables (): array {
            $query = "SELECT * FROM information_schema.TABLES WHERE TABLE_SCHEMA = '". StaticHandler::getEnvConfig('databases.conn.'.self::$defaultDbConnKey.'.name') ."';";

            try {
                $result = self::$pdos[self::$defaultDbConnKey]->prepare($query);
                $result->execute();
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $query);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }

            return $result->fetchAll(PDO::FETCH_ASSOC);
        }

        final static function existsTable (string $tb_name): bool {
            $query = "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?;";
            $params = array(
                StaticHandler::getEnvConfig('databases.conn.'.self::$defaultDbConnKey.'.name'), self::$tb_prefixes[self::$defaultDbConnKey].$tb_name
            );

            try {
                $result = self::$pdos[self::$defaultDbConnKey]->prepare($query);
                $result->execute($params);
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $query, $params);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }

            $result = $result->fetch(PDO::FETCH_ASSOC);

            return ($result['COUNT(*)'] == 1);
        }

        final static function createTable (string $tb_name, array $columns): void {
            $sql = "CREATE TABLE IF NOT EXISTS ". self::$tb_prefixes[self::$defaultDbConnKey].$tb_name ." (". implode(', ', $columns) .");";

            try {
                self::$pdos[self::$defaultDbConnKey]->exec($sql);

                if (isset(self::$backups[self::$defaultDbConnKey])) {
                    foreach (self::$backups[self::$defaultDbConnKey] as $db_key) {
                        self::$pdos[$db_key]->exec($sql);
                    }
                }
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $sql);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }
        }

        final static function columnsTable (string $tb_name, bool $add_primary_key = false): array {
            $query = "SELECT * FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ?";
            if (!$add_primary_key) {
                $query .= " AND (COLUMN_KEY != 'PRI' OR EXTRA != 'auto_increment')";
            }
            $query .= ';';

            $params = array(
                StaticHandler::getEnvConfig('databases.conn.'.self::$defaultDbConnKey.'.name'), self::$tb_prefixes[self::$defaultDbConnKey].$tb_name
            );

            try {
                $result = self::$pdos[self::$defaultDbConnKey]->prepare($query);
                $result->execute($params);
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $query, $params);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }

            return $result->fetchAll(PDO::FETCH_ASSOC);
        }

        final static function alterTable (string $tb_name, string $action = NULL, string $column, string $value = NULL): bool {
            $exists = self::$pdos[self::$defaultDbConnKey]->query('SHOW COLUMNS FROM ' . self::$tb_prefixes[self::$defaultDbConnKey].$tb_name . " LIKE '". $column ."'")->fetch(PDO::FETCH_NUM);
            $action = ($action ? trim(strtoupper($action)) : NULL);

            if ($exists) {
                if ($action == 'ADD') {
                    return false;
                }
                if (is_null($action)) {
                    $action = 'MODIFY COLUMN';
                }
            }
            else {
                if (str_replace(' ', '', $action) == 'DROPCOLUMN') {
                    return false;
                }
                if (is_null($action)) {
                    $action = 'ADD';
                }
            }

            $sql = ("ALTER TABLE ". self::$tb_prefixes[self::$defaultDbConnKey].$tb_name .' '. $action ." `". $column ."` ". str_replace('()', '', $value) .';');

            try {
                self::$pdos[self::$defaultDbConnKey]->exec($sql);

                if (isset(self::$backups[self::$defaultDbConnKey])) {
                    foreach (self::$backups[self::$defaultDbConnKey] as $db_key) {
                        self::$pdos[$db_key]->exec($sql);
                    }
                }

                return true;
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $sql);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }
        }

        final static function dropTable (string $tb_name): void {
            $sql = "DROP TABLE IF EXISTS ". self::$tb_prefixes[self::$defaultDbConnKey].$tb_name .";";

            try {
                self::$pdos[self::$defaultDbConnKey]->exec($sql);

                if (isset(self::$backups[self::$defaultDbConnKey])) {
                    foreach (self::$backups[self::$defaultDbConnKey] as $db_key) {
                        self::$pdos[$db_key]->exec($sql);
                    }
                }
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $sql);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }
        }

        final static function truncateTable (string $tb_name): void {
            $sql = "TRUNCATE TABLE ". self::$tb_prefixes[self::$defaultDbConnKey].$tb_name .";";

            try {
                self::$pdos[self::$defaultDbConnKey]->exec($sql);

                if (isset(self::$backups[self::$defaultDbConnKey])) {
                    foreach (self::$backups[self::$defaultDbConnKey] as $db_key) {
                        self::$pdos[$db_key]->exec($sql);
                    }
                }
            }
            catch (PDOException $e) {
                if (StaticHandler::isCRON() == false) {
                    DevToolDebug::print_pdo_exception($e, $sql);
                }
                else {
                    throw new Exception($e->getMessage(), $e->getCode(), $e);
                }
            }
        }

    /* Import SQL file */
        final static function importSqlFile (string $sql_filepath): bool {
        	$sql = ''; // SQL variable, used to store current query.

    		$lines = file($sql_filepath); // Read in entire file.

    		// Loop through each line
    		foreach ($lines as $line) {
    			// Skip it if it's a comment
    			if (substr($line, 0, 2) == '--' || trim($line) == '') {
    				continue;
    			}

    			$sql .= $line; // Add this line to the current segment.

    			// if it has a semicolon at the end,
                // it's the end of the query.
    			if (substr(trim($line), -1, 1) == ';') {
                    $sql = self::prefix(self::$tb_prefixes[self::$defaultDbConnKey], $sql, true, true);

                    self::$pdos[self::$defaultDbConnKey]->exec($sql);

                    if (isset(self::$backups[self::$defaultDbConnKey])) {
                        foreach (self::$backups[self::$defaultDbConnKey] as $db_key) {
                            self::$pdos[$db_key]->exec($sql);
                        }
                    }

                    // we don't catch errors because we let DevPanel to do that

    				$sql = ''; // Reset sql variable to empty.
    			}
    		}

        	return true;
        }
}
