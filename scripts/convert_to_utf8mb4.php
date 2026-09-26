#!/usr/bin/php
<?php
// LibreForum — https://github.com/fbastin/LibreForum
// Copyright 2026 Fabian Bastin and the LibreForum contributors
// SPDX-License-Identifier: Apache-2.0
//
// Convert the forum tables from MySQL "utf8" (3 bytes, utf8mb3) to utf8mb4.
//
// With "utf8", a message containing an emoji or any character outside the
// Basic Multilingual Plane is rejected by MySQL (error 1366) and the poster
// gets a database error page. utf8mb4 stores every Unicode character.
//
// Usage:
//     php scripts/convert_to_utf8mb4.php            show what would be done
//     php scripts/convert_to_utf8mb4.php --apply    do it
//     option --prefix=<p>                           tables <p>_* instead of
//                                                   the configured prefix
//
// Back up the database first. Each table is locked while it is rebuilt
// (seconds for tens of thousands of messages).
//
// What it does, table by table (tables named <prefix>_*):
//   - columns in utf8 / utf8mb3 are converted to utf8mb4, keeping the
//     collation family (utf8mb3_bin -> utf8mb4_bin, ..._general_ci ->
//     ..._general_ci): comparisons of user names and the like do not change;
//   - an index that would exceed the key length limit of the table's engine
//     once in utf8mb4 is rebuilt with a 191-character prefix on its long
//     text columns; a primary key cannot take a prefix, so its column is
//     shortened to 191 characters if no stored value is longer;
//   - a fingerprint of the whole table content is compared before and after.
// It refuses, and leaves alone:
//   - tables with latin1 (or other non-UTF-8) text columns: such columns
//     often hold UTF-8 bytes stored as latin1, and a conversion would garble
//     them. They need a manual, data-aware conversion;
//   - tables whose text columns do not share one collation, and over-long
//     UNIQUE indexes (a prefix would change what "unique" means).
//
// After a successful run, set 'charset' => 'utf8mb4' in include/db/config.php.

if ('cli' != php_sapi_name()) {
    echo "This script cannot be run from a browser.";
    return;
}

define('phorum_page', 'convert_to_utf8mb4');
define('PHORUM_ADMIN', 1);
chdir(dirname(__FILE__) . '/..');
require_once './common.php';

$apply  = in_array('--apply', $argv);
$prefix = $PHORUM['DBCONFIG']['table_prefix'];
foreach ($argv as $arg) {
    if (strpos($arg, '--prefix=') === 0) $prefix = substr($arg, 9);
}

$cfg = $PHORUM['DBCONFIG'];
$dsn = "mysql:host={$cfg['server']};dbname={$cfg['name']};charset=utf8mb4";
if (!empty($cfg['port'])) $dsn .= ";port={$cfg['port']}";
$pdo = new PDO($dsn, $cfg['user'], $cfg['password'],
               array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));

define('LIBREFORUM_UTF8MB4_PREFIX', 191);

function libreforum_utf8mb4_rows($pdo, $sql, $args = array())
{
    $st = $pdo->prepare($sql);
    $st->execute($args);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

// "utf8_bin" or "utf8mb3_bin" -> "utf8mb4_bin", if the server knows it.
function libreforum_utf8mb4_collation($pdo, $collation)
{
    $target = preg_replace('/^utf8(mb3)?_/', 'utf8mb4_', $collation);
    $known = libreforum_utf8mb4_rows($pdo,
        "SELECT 1 FROM information_schema.collations WHERE collation_name = ?",
        array($target));
    return $known ? $target : 'utf8mb4_unicode_ci';
}

// Row count plus XOR and sum of per-row hashes: order-independent and covers
// every row. The bytes of a UTF-8 string do not change in the conversion,
// so equal fingerprints mean the content came through untouched.
function libreforum_utf8mb4_fingerprint($pdo, $table)
{
    $cols = array();
    foreach (libreforum_utf8mb4_rows($pdo,
        "SELECT column_name AS c FROM information_schema.columns
          WHERE table_schema = DATABASE() AND table_name = ?
          ORDER BY ordinal_position", array($table)) as $r) {
        $cols[] = "`{$r['c']}`";
    }
    $c = implode(',', $cols);
    $r = libreforum_utf8mb4_rows($pdo,
        "SELECT CONCAT(COUNT(*), '-',
                BIT_XOR(CAST(CONV(SUBSTR(MD5(CONCAT_WS('|', $c)), 1, 16), 16, 10) AS UNSIGNED)), '-',
                SUM(CRC32(CONCAT_WS('|', $c)))) AS f FROM `$table`");
    return $r[0]['f'];
}

// Plan the statements for one table, or return a reason to leave it alone.
// (information_schema columns are aliased: MySQL 8 returns them in upper case.)
function libreforum_utf8mb4_plan($pdo, $table)
{
    $info = libreforum_utf8mb4_rows($pdo,
        "SELECT engine AS engine, row_format AS row_format FROM information_schema.tables
          WHERE table_schema = DATABASE() AND table_name = ?", array($table));
    $engine = strtolower($info[0]['engine']);
    $row_format = strtolower($info[0]['row_format']);

    $columns = libreforum_utf8mb4_rows($pdo,
        "SELECT column_name AS column_name, data_type AS data_type,
                character_maximum_length AS len, character_set_name AS cs,
                collation_name AS co, is_nullable AS is_nullable,
                column_default AS column_default
           FROM information_schema.columns
          WHERE table_schema = DATABASE() AND table_name = ?
            AND character_set_name IS NOT NULL", array($table));
    $by_name = array();
    $todo = FALSE;
    $collations = array();
    foreach ($columns as $col) {
        $by_name[$col['column_name']] = $col;
        if ($col['cs'] == 'utf8mb4') continue;
        if (!in_array($col['cs'], array('utf8', 'utf8mb3'))) {
            return "column {$col['column_name']} is {$col['cs']}: convert it by hand";
        }
        $todo = TRUE;
        $collations[libreforum_utf8mb4_collation($pdo, $col['co'])] = TRUE;
    }
    if (!$todo) return array();
    if (count($collations) > 1) {
        return "text columns use several collations (" .
               implode(', ', array_keys($collations)) . "): convert by hand";
    }
    $collation = key($collations);

    // Key length limits: MyISAM 1000 bytes per key; InnoDB 3072 per key,
    // and 767 per column with the old COMPACT and REDUNDANT row formats.
    $key_limit = ($engine == 'myisam') ? 1000 : 3072;
    $col_limit = in_array($row_format, array('compact', 'redundant')) ? 767 : $key_limit;

    $indexes = array();
    foreach (libreforum_utf8mb4_rows($pdo,
        "SELECT index_name AS index_name, non_unique AS non_unique,
                index_type AS index_type, column_name AS column_name,
                sub_part AS sub_part
           FROM information_schema.statistics
          WHERE table_schema = DATABASE() AND table_name = ?
          ORDER BY index_name, seq_in_index", array($table)) as $r) {
        $indexes[$r['index_name']][] = $r;
    }

    $statements = array();
    foreach ($indexes as $name => $parts) {
        if ($parts[0]['index_type'] != 'BTREE') continue;
        $bytes = 0;
        $too_wide_column = FALSE;
        foreach ($parts as $p) {
            $col = isset($by_name[$p['column_name']]) ? $by_name[$p['column_name']] : NULL;
            if ($col === NULL) { $bytes += 8; continue; }
            $b = 4 * ($p['sub_part'] ? $p['sub_part'] : $col['len']) + 2;
            if ($b > $col_limit) $too_wide_column = TRUE;
            $bytes += $b;
        }
        if ($bytes <= $key_limit && !$too_wide_column) continue;

        if ($name == 'PRIMARY') {
            foreach ($parts as $p) {
                $col = isset($by_name[$p['column_name']]) ? $by_name[$p['column_name']] : NULL;
                if ($col === NULL || $col['data_type'] != 'varchar' ||
                    $col['len'] <= LIBREFORUM_UTF8MB4_PREFIX) continue;
                $longest = libreforum_utf8mb4_rows($pdo,
                    "SELECT COALESCE(MAX(CHAR_LENGTH(`{$col['column_name']}`)), 0) AS m FROM `$table`");
                if ($longest[0]['m'] > LIBREFORUM_UTF8MB4_PREFIX) {
                    return "primary key column {$col['column_name']} holds values longer than " .
                           LIBREFORUM_UTF8MB4_PREFIX . " characters";
                }
                $default = $col['column_default'] === NULL
                         ? ($col['is_nullable'] == 'YES' ? ' DEFAULT NULL' : '')
                         : ' DEFAULT ' . $pdo->quote(trim($col['column_default'], "'"));
                $statements[] = "MODIFY `{$col['column_name']}` VARCHAR(" . LIBREFORUM_UTF8MB4_PREFIX .
                    ") CHARACTER SET {$col['cs']} COLLATE {$col['co']}" .
                    ($col['is_nullable'] == 'YES' ? ' NULL' : ' NOT NULL') . $default;
            }
            continue;
        }
        if (!$parts[0]['non_unique']) {
            return "unique index $name would be too long in utf8mb4: convert by hand";
        }
        $keyparts = array();
        foreach ($parts as $p) {
            $col = isset($by_name[$p['column_name']]) ? $by_name[$p['column_name']] : NULL;
            $len = $p['sub_part'];
            if ($col !== NULL && $col['data_type'] == 'varchar' &&
                ($len ? $len : $col['len']) > LIBREFORUM_UTF8MB4_PREFIX) {
                $len = LIBREFORUM_UTF8MB4_PREFIX;
            }
            $keyparts[] = "`{$p['column_name']}`" . ($len ? "($len)" : '');
        }
        $statements[] = "DROP INDEX `$name`, ADD INDEX `$name` (" . implode(', ', $keyparts) . ")";
    }

    $statements[] = "CONVERT TO CHARACTER SET utf8mb4 COLLATE $collation";
    return $statements;
}

$tables = libreforum_utf8mb4_rows($pdo,
    "SELECT table_name AS t FROM information_schema.tables
      WHERE table_schema = DATABASE() AND table_type = 'BASE TABLE'
        AND table_name LIKE ? ORDER BY table_name",
    array(str_replace('_', '\\_', $prefix) . '\\_%'));

echo $apply ? "Converting" : "Dry run (add --apply to convert)",
     ": tables {$prefix}_* in database {$cfg['name']}\n\n";

$skipped = 0;
$failed = FALSE;
foreach ($tables as $row) {
    $table = $row['t'];
    $plan = libreforum_utf8mb4_plan($pdo, $table);
    if (is_string($plan)) {
        printf("%-34s LEFT ALONE: %s\n", $table, $plan);
        $skipped++;
        continue;
    }
    if (!$plan) continue;

    if (!$apply) {
        foreach ($plan as $sql) printf("%-34s would run: %s\n", $table, $sql);
        continue;
    }
    $before = libreforum_utf8mb4_fingerprint($pdo, $table);
    foreach ($plan as $sql) {
        $t0 = microtime(TRUE);
        try {
            $pdo->exec("ALTER TABLE `$table` $sql");
        } catch (PDOException $e) {
            printf("%-34s FAILED: %s\n    %s\n", $table, $sql, $e->getMessage());
            exit(1);
        }
        printf("%-34s done in %.1f s: %s\n", $table, microtime(TRUE) - $t0, $sql);
    }
    $after = libreforum_utf8mb4_fingerprint($pdo, $table);
    if ($before !== $after) {
        printf("%-34s CONTENT CHANGED (before %s, after %s): restore the backup\n",
               $table, $before, $after);
        $failed = TRUE;
    }
}

echo "\n";
if ($failed) {
    echo "The content of at least one table changed: see above.\n";
    exit(2);
}
if ($skipped) echo "$skipped table(s) left alone: see above.\n";
echo $apply
   ? "Done. Now set 'charset' => 'utf8mb4' in include/db/config.php.\n"
   : "Nothing was changed.\n";
exit(0);
