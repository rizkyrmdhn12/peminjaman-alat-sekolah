<?php
/** Base model: helper query PDO (selalu prepared statement, parameter posisi). */
abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::pdo();
    }

    protected function run(string $sql, array $params = []): PDOStatement
    {
        $st = $this->db->prepare($sql);
        $i = 1;
        foreach ($params as $v) {
            if (is_int($v)) {
                $st->bindValue($i, $v, PDO::PARAM_INT);
            } elseif ($v === null) {
                $st->bindValue($i, null, PDO::PARAM_NULL);
            } else {
                $st->bindValue($i, (string)$v, PDO::PARAM_STR);
            }
            $i++;
        }
        $st->execute();
        return $st;
    }

    protected function all(string $sql, array $params = []): array
    {
        return $this->run($sql, $params)->fetchAll();
    }

    protected function one(string $sql, array $params = []): ?array
    {
        $r = $this->run($sql, $params)->fetch();
        return $r === false ? null : $r;
    }

    protected function val(string $sql, array $params = [])
    {
        $r = $this->run($sql, $params)->fetchColumn();
        return $r === false ? null : $r;
    }

    protected function exec(string $sql, array $params = []): int
    {
        return $this->run($sql, $params)->rowCount();
    }

    protected function insertId(): int
    {
        return (int)$this->db->lastInsertId();
    }

    /** Pagination: $where = daftar kondisi (AND), $order sudah di-whitelist oleh pemanggil. */
    protected function paged(string $select, string $from, array $where, array $params, string $order, int $page, int $per = PER_PAGE): array
    {
        $w = $where ? ' WHERE ' . implode(' AND ', $where) : '';
        $total = (int)$this->val("SELECT COUNT(*) FROM $from$w", $params);
        $pages = max(1, (int)ceil($total / $per));
        $page = min(max(1, $page), $pages);
        $rows = $this->all("SELECT $select FROM $from$w ORDER BY $order LIMIT ? OFFSET ?", array_merge($params, [$per, ($page - 1) * $per]));
        return ['rows' => $rows, 'total' => $total, 'page' => $page, 'pages' => $pages, 'per' => $per];
    }

    /** Jalankan blok dalam transaksi: COMMIT jika sukses, ROLLBACK jika ada error. */
    protected function tx(callable $fn)
    {
        $this->db->beginTransaction();
        try {
            $r = $fn();
            $this->db->commit();
            return $r;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /** Escape wildcard LIKE lalu bungkus %...% */
    protected function like(string $s): string
    {
        return '%' . addcslashes($s, '%_\\') . '%';
    }
}
