<?php

namespace App\Core\Model;

use App\Core\Database;
use App\Core\Model;
use PDO;
use Traversable;

/**
 * Class Collection
 *
 * Handles database collections for models.
 */
class Collection implements CollectionInterface
{
    // Constants
    const ITEM_MODE_ARRAY = 0;
    const ITEM_MODE_OBJECT = 1;
    const DEFAULT_PAGE_SIZE = 25;

    // Properties

    /**
     * @var PDO
     */
    private PDO $db;

    /**
     * @var string|null
     */
    private ?string $rawSql = null;

    /**
     * @var string|null
     */
    private ?string $table = null;

    /**
     * @var array
     */
    private array $params = [];

    /**
     * @var string
     */
    private string $modelClass;

    /**
     * @var int
     */
    private int $itemMode = self::ITEM_MODE_OBJECT;

    /**
     * @var array
     */
    private array $columns = [];

    /**
     * @var array
     */
    private array $filters = [];

    /**
     * @var array
     */
    private array $joins = [];

    /**
     * @var array
     */
    private array $sort = [];

    /**
     * @var string|null
     */
    private ?string $groupBy = null;

    /**
     * @var string|null
     */
    private ?string $having = null;

    /**
     * @var int
     */
    private int $page = 1;

    /**
     * @var int
     */
    private int $pageSize = self::DEFAULT_PAGE_SIZE;

    /**
     * @var int|null
     */
    private ?int $count = null;

    // Constructor

    /**
     * Collection constructor.
     *
     * @param string|Model $model Model class name or instance
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    public function __construct(string|Model $model)
    {
        if (is_string($model)) {
            if (!class_exists($model)) {
                throw new \InvalidArgumentException("Model class $model does not exist");
            }
            if (!is_subclass_of($model, Model::class)) {
                throw new \InvalidArgumentException("Model class $model must extend App\\Core\\Model");
            }

            $this->modelClass = $model;
            $this->table = (new $model())->getTable();
        } else {
            if (!is_subclass_of($model, Model::class)) {
                throw new \InvalidArgumentException("Model class $model does not extend App\\Core\\Model");
            }
            $this->modelClass = get_class($model);
            $this->table = $model->getTable();
        }

        if (!$this->table) {
            throw new \RuntimeException("Model class $model does not have a table defined");
        }

        $this->db = Database::connect();
    }

    // Model and Table

    /**
     * Get the table name.
     *
     * @return string
     */
    public function getTable(): string
    {
        return $this->table;
    }

    /**
     * Get the model class name.
     *
     * @return string
     */
    protected function getModelClass(): string
    {
        return $this->modelClass;
    }

    /**
     * Create a new model instance with optional data.
     *
     * @param array $data
     * @return Model
     */
    protected function createModel(array $data = []): Model
    {
        $model  = new $this->modelClass();
        if ($data) {
            $model->setData($data);
        }

        return $model;
    }

    // Raw SQL

    /**
     * Set raw SQL query to be used instead of generated query.
     *
     * @param string $sql
     * @return static
     */
    public function setRawSql(string $sql): static
    {
        $this->rawSql = $sql;
        return $this;
    }

    /**
     * Get the raw SQL query if set.
     *
     * @return string|null
     */
    public function getRawSql(): ?string
    {
        return $this->rawSql;
    }

    // Columns

    /**
     * Add a column to select.
     *
     * @param string $column
     * @return static
     * @throws \InvalidArgumentException
     */
    public function column(string $column): static
    {
        if (empty($column)) {
            throw new \InvalidArgumentException("Column name cannot be empty");
        }

        $this->columns[] = $column;
        return $this;
    }

    /**
     * Render the columns for the SQL SELECT statement.
     *
     * @return string
     */
    protected function renderColumns(): string
    {
        if (empty($this->columns)) {
            return 'main.*';
        }

        return implode(', ', array_map(function ($column) {
            if (is_array($column)) {
                return sprintf("`%s` AS `%s`", $column[0], $column[1]);
            }
            return "`$column`";
        }, $this->columns));
    }

    // Filters

    /**
     * Add a filter to the collection.
     *
     * @param array $filter Associative array of field => value pairs
     * @param string $operator The operator to use for combining filters (AND or OR)
     * @return static
     * @throws \InvalidArgumentException
     */
    public function addFilter(array $filter, string $operator = 'AND'): static
    {
        if (!in_array($operator, ['AND', 'OR'])) {
            throw new \InvalidArgumentException("Operator must be 'AND' or 'OR'");
        }

        foreach ($filter as $key => $value) {
            $placeholder = str_replace(['.', ' '], '_', $key);
            $placeholder = str_replace('`', '', $placeholder);

            if (is_array($value)) {
                $this->filters[] = [
                    'filter' => "$key IN (" . implode(',', array_fill(0, count($value), ":$placeholder")) . ")",
                    'operator' => $operator
                ];
            } elseif (is_null($value)) {
                $this->filters[] = [
                    'filter' => "$key IS NULL",
                    'operator' => $operator
                ];
            } elseif (str_contains($value, '%')) {
                $this->filters[] = [
                    'filter' => "$key LIKE :$placeholder",
                    'operator' => $operator
                ];
            } else {
                $this->filters[] = [
                    'filter' => "$key = :$placeholder",
                    'operator' => $operator
                ];
            }

            $this->params[$placeholder] = $value;
        }

        return $this;
    }

    /**
     * Apply post-filters to the collection.
     * This method is used to apply filters after the initial query has been built.
     *
     * @param array $filters Associative array of field => value pairs
     * @return static
     */
    public function applyPostFilters(array $filters): static
    {
        foreach ($filters as $key => $value) {
            if (empty($value)) {
                continue; // Skip empty filters
            }
            if (is_array($value)) {
                $this->addFilter([$key => $value]);
            } else {
                $this->addFilter([$key => '%' . $value . '%']);
            }
        }

        return $this;
    }

    /**
     * Get the filters applied to the collection.
     *
     * @return array
     */
    public function getFilters(): array
    {
        return $this->filters;
    }

    /**
     * Add filters to the SQL query.
     *
     * @param string $sql The SQL query to modify
     * @return string
     */
    protected function renderWhere($sql): string
    {
        if ($this->filters) {
            $hasWhere = strpos($sql, 'WHERE') !== false;
            if (!$hasWhere) {
                $sql .= ' WHERE 1=1 ';
            }
            $sql .= implode(' ', array_map(function ($filter) {
                return $filter['operator'] . ' ' . $filter['filter'];
            }, $this->filters));
        }

        return $sql;
    }

    // Joins

    /**
     * Join another table to the collection.
     *
     * @param string|array $table The table to join
     * @param string $on The ON condition for the join
     * @param string $type The type of join (INNER, LEFT, etc.)
     * @return static
     */
    public function join(string|array $table, string $on, string $type = 'INNER'): static
    {
        $this->joins[] = [
            'table' => $table,
            'on' => $on,
            'type' => $type
        ];

        return $this;
    }

    /**
     * Render JOIN clauses for the SQL query.
     *
     * @param string $sql
     * @return string
     */
    public function renderJoins(string $sql): string
    {
        if ($this->joins) {
            foreach ($this->joins as $join) {
                $sql .= sprintf(
                    " %s JOIN `%s` %s ON %s",
                    strtoupper($join['type']),
                    !is_array($join['table']) ? $join['table'] : array_values($join['table'])[0],
                    !is_array($join['table']) ? '' : 'AS ' . array_keys($join['table'])[0],
                    is_array($join['on']) ? implode(' AND ', $join['on']) : $join['on']
                );
            }
        }

        return $sql;
    }

    // Group By & Having

    /**
     * Set the GROUP BY field for the collection.
     *
     * @param string $field
     * @return static
     */
    public function groupBy(string $field): static
    {
        $this->groupBy = $field;
        return $this;
    }

    /**
     * Get the GROUP BY field for the collection.
     *
     * @return string|null
     */
    public function getGroupBy(): ?string
    {
        return $this->groupBy;
    }

    /**
     * Render the GROUP BY clause for the SQL query.
     *
     * @param string $sql The SQL query to modify
     * @return string
     */
    protected function renderGroup(string $sql): string
    {
        if ($this->groupBy) {
            $sql .= " GROUP BY `{$this->groupBy}`";
        }

        return $sql;
    }

    /**
     * Set the HAVING condition for the collection.
     *
     * @param string $condition
     * @return static
     */
    public function having(string $condition): static
    {
        $this->having = $condition;
        return $this;
    }

    /**
     * Get the HAVING condition for the collection.
     *
     * @return string|null
     */
    public function getHaving(): ?string
    {
        return $this->having;
    }

    // Sorting

    /**
     * Set the sorting criteria for the collection.
     *
     * @param string $field The field to sort by
     * @param string $direction The direction of sorting (ASC or DESC)
     * @return static
     * @throws \InvalidArgumentException
     */
    public function sort(string $field, string $direction = 'ASC'): static
    {
        if (!in_array($direction, ['ASC', 'DESC'])) {
            throw new \InvalidArgumentException("Direction must be 'ASC' or 'DESC'");
        }

        $this->sort[$field] = $direction;
        return $this;
    }

    /**
     * Get the sorting criteria for the collection.
     *
     * @return array
     */
    public function getSort(): array
    {
        return $this->sort;
    }

    /**
     * Render the ORDER BY clause for the SQL query.
     *
     * @param string $sql The SQL query to modify
     * @return string
     */
    protected function renderOrder(string $sql): string
    {
        if ($this->sort) {
            $sql .= ' ORDER BY ' . implode(', ', array_map(function ($field, $direction) {
                return "`$field` $direction";
            }, array_keys($this->sort), $this->sort));
        }

        return $sql;
    }

    // Pagination

    /**
     * Set the current page number for pagination.
     *
     * @param int $page
     * @return static
     * @throws \InvalidArgumentException
     */
    public function setPage(int $page): static
    {
        if ($page < 1) {
            throw new \InvalidArgumentException("Page must be greater than 0");
        }

        if ($this->getPages() > 0 && $page > $this->getPages()) {
            $page = $this->getPages();
        }

        $this->page = $page;
        return $this;
    }

    /**
     * Get the current page number.
     *
     * @return int
     */
    public function getPage(): int
    {
        return $this->page;
    }

    /**
     * Set the page size for pagination.
     *
     * @param int $size
     * @return static
     * @throws \InvalidArgumentException
     */
    public function setPageSize(int $size): static
    {
        if ($size < 1) {
            throw new \InvalidArgumentException("Page size must be greater than 0");
        }

        $this->pageSize = $size;
        return $this;
    }

    /**
     * Get the page size for pagination.
     *
     * @return int
     */
    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    /**
     * Get the number of pages.
     *
     * @return int
     */
    public function getPages(): int
    {
        if ($this->pageSize <= 0) {
            return 1;
        }

        $totalCount = $this->count();
        return (int) ceil($totalCount / $this->pageSize);
    }

    /**
     * Get the SQL LIMIT clause for pagination.
     *
     * @return string
     */
    public function getLimitSql(): string
    {
        if ($this->pageSize > 0 ) {
            $offset = ($this->page - 1) * $this->pageSize;
            $limitSql = " LIMIT :lim OFFSET :off";
            $this->params['lim'] = $this->pageSize;
            $this->params['off'] = $offset;
        }
        return $limitSql ?? '';
    }

    // Item Mode

    /**
     * Set the item mode for fetching results.
     *
     * @param int $mode
     * @return static
     * @throws \InvalidArgumentException
     */
    public function setItemMode(int $mode): static
    {
        if (!in_array($mode, [self::ITEM_MODE_ARRAY, self::ITEM_MODE_OBJECT])) {
            throw new \InvalidArgumentException("Item mode must be " . self::ITEM_MODE_ARRAY . " or " . self::ITEM_MODE_OBJECT);
        }

        $this->itemMode = $mode;
        return $this;
    }

    /**
     * Get the current item mode.
     *
     * @return int
     */
    public function getItemMode(): int
    {
        return $this->itemMode;
    }

    // Params

    /**
     * Add a parameter to the collection.
     *
     * @param string $key The parameter key
     * @param mixed $value The parameter value
     * @return static
     */
    public function addParam(string $key, mixed $value): static
    {
        $this->params[$key] = $value;
        return $this;
    }

    /**
     * Get the parameters for the SQL query.
     *
     * @return array
     */
    protected function getParams(): array
    {
        return $this->params;
    }

    // SQL Generation

    /**
     * Get the SQL SELECT statement for the collection.
     *
     * @param bool $skipLimit
     * @return string
     */
    public function getSelect(bool $skipLimit = false): string
    {
        if ($this->rawSql) {
            return $this->rawSql . ($skipLimit ? '' : $this->getLimitSql());
        }

        $sql = "SELECT `main`.* FROM `{$this->table}` AS `main`";
        $sql = $this->renderJoins($sql);
        $sql = $this->renderWhere($sql);
        $sql = $this->renderGroup($sql);
        $sql = $this->renderOrder($sql);

        if (!$skipLimit) {
            $sql .= $this->getLimitSql();
        }

        return $sql;
    }

    // Fetching & Counting

    /**
     * Count the number of items in the collection.
     *
     * @return int
     */
    public function count(): int
    {
        if ($this->count !== null) {
            return $this->count;
        }

        $sql = $this->getSelect(true);
        $sql = "SELECT COUNT(*) FROM ($sql) AS count_query";
        $stmt = $this->db->prepare($sql);
        foreach ($this->params as $key => $value) {
            $stmt->bindValue(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        return $this->count = (int) $stmt->fetchColumn();
    }

    /**
     * Check if the collection is empty.
     *
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() < 1;
    }

    /**
     * Fetch the results as an iterable collection.
     *
     * @return Traversable
     */
    public function fetch(): Traversable
    {
        $sql = $this->getSelect();
        $stmt = $this->db->prepare($sql);
        foreach ($this->params as $key => $value) {
            $stmt->bindValue(":$key", $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        if ($this->itemMode === self::ITEM_MODE_ARRAY) {
            return new \ArrayIterator($stmt->fetchAll(PDO::FETCH_ASSOC));
        }

        return new \ArrayIterator(array_map([$this, 'createModel'], $stmt->fetchAll(PDO::FETCH_ASSOC)));
    }

    /**
     * Get an iterator for the collection.
     *
     * @return Traversable
     */
    public function getIterator(): Traversable
    {
        return $this->fetch();
    }
}