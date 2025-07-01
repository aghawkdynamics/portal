<?php

namespace App\Core\Model;

use IteratorAggregate;

interface CollectionInterface extends IteratorAggregate
{
    public function getTable(): string;

    public function count(): int;

    public function isEmpty(): bool;

    public function setItemMode(int $mode): static;

    public function getItemMode(): int;

    public function addFilter(array $filter, string $operator = 'AND'): static;

    public function getFilters(): array;

    public function sort(string $field, string $direction = 'ASC'): static;

    public function getSort(): array;

    public function setPage(int $page): static;

    public function getPage(): int;

    public function setRawSql(string $sql): static;

    public function getRawSql(): ?string;

    public function getSelect(): string;


    public function setPageSize(int $size): static;

    public function getPageSize(): int;

    public function join(string|array $table, string $on, string $type = 'INNER'): static;

    public function applyPostFilters(array $filters): static;

    public function getIterator(): \Traversable;
}