<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Marks the original text columns as deprecated in favour of the translation tables (plan: Phase 15). Only the column COMMENT
 * changes: no column is dropped, renamed or altered in type, nullability, default or collation, and no data is touched. The
 * columns keep being written together with the Vietnamese translation during the transition.
 *
 * The ALTER is built from the live column definition (SHOW FULL COLUMNS), so it repeats exactly what the column is today.
 * MySQL / MariaDB only; on any other driver the migration does nothing.
 */
return new class extends Migration
{
    private const COLUMNS = [
        'posts' => ['title' => 'post_translations.title', 'slug' => 'post_translations.slug', 'excerpt' => 'post_translations.excerpt', 'content' => 'post_translations.content'],
        'terms' => ['name' => 'term_translations.name', 'slug' => 'term_translations.slug'],
        'menu_items' => ['title' => 'menu_item_translations.title', 'url' => 'menu_item_translations.custom_url'],
    ];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach ($columns as $column => $replacement) {
                $this->setComment($table, $column, 'DEPRECATED: mirror of the default-language translation; source of truth is '.$replacement);
            }
        }
    }

    public function down(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            foreach (array_keys($columns) as $column) {
                $this->setComment($table, $column, '');
            }
        }
    }

    private function setComment(string $table, string $column, string $comment): void
    {
        if (! in_array(DB::getDriverName(), ['mysql', 'mariadb'], true) || ! Schema::hasColumn($table, $column)) {
            return;
        }
        $definition = DB::selectOne("SHOW FULL COLUMNS FROM `{$table}` WHERE Field = ?", [$column]);
        if ($definition === null) {
            return;
        }
        $pdo = DB::getPdo();
        $sql = "ALTER TABLE `{$table}` MODIFY COLUMN `{$column}` {$definition->Type}"
            .($definition->Collation ? " COLLATE {$definition->Collation}" : '')
            .($definition->Null === 'YES' ? ' NULL' : ' NOT NULL')
            .($definition->Default !== null ? ' DEFAULT '.$pdo->quote((string) $definition->Default) : ($definition->Null === 'YES' ? ' DEFAULT NULL' : ''))
            .' COMMENT '.$pdo->quote($comment);
        DB::statement($sql);
    }
};
