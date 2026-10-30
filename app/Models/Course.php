<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use October\Contracts\Database\SortableInterface;

/**
 * Course model.
 */
class Course implements SortableInterface
{
    use HasFactory;

    /** @var array<int, string> Static in-memory store (SQLite demo). */
    protected static array $records = [];

    /** @var int Auto-incrementing ID counter */
    protected static int $nextId = 1;

    public function __construct(
        public int|string|null $id = null,
        public ?string $title = null,
        public ?string $description = null,
        public ?float $price = null,
        public int $sort_order = 0,
    ) {}

    /** @return array<string, self> */
    public static function all(): array
    {
        return static::$records;
    }

    public static function find(int|string $id): ?self
    {
        return static::$records[$id] ?? null;
    }

    public function save(): bool
    {
        if (!$this->id) {
            $this->id = static::$nextId++;
        }
        static::$records[(int) $this->id] = $this;

        // Refresh sort order after save
        self::refreshSortOrders();

        return true;
    }

    public function delete(): bool
    {
        unset(static::$records[(int) $this->id]);
        return true;
    }

    /** @param array<string, mixed> $attributes */
    public static function create(array $attributes): self
    {
        $course = new self(...$attributes);
        $course->save();
        return $course;
    }

    /** @param array<string, mixed> $where */
    public static function where(array $where): array
    {
        return static::$records;
    }

    /** @return array<int, self> */
    public static function orderBy(string $column, string $direction = 'asc'): array
    {
        $records = array_values(static::$records);
        usort($records, function (self $a, self $b) use ($column, $direction) {
            $va = match ($column) {
                'title' => (string) $a->title,
                'price' => $a->price,
                default => null,
            };
            $vb = match ($column) {
                'title' => (string) $b->title,
                'price' => $b->price,
                default => null,
            };
            if ($va === null) {
                return 0;
            }
            return match ($direction) {
                'desc' => strnatcasecmp($vb ?? '', $va),
                default => strnatcasecmp($va, $vb ?? ''),
            };
        });
        return $records;
    }

    /** Update the sort_order on all records to be sequential */
    public static function refreshSortOrders(): void
    {
        $records = array_values(static::$records);
        usort($records, fn(self $a, self $b) => $a->sort_order <=> $b->sort_order);
        foreach ($records as $i => $rec) {
            $rec->sort_order = $i + 1;
        }
    }

    /** Persist a new record ordering. Called by the ListStructure widget when rows are dragged into a new order. */
    public function setSortableOrder($itemIds, $itemOrders = null): void
    {
        if (!is_array($itemIds)) {
            return;
        }
        foreach (array_values($itemIds) as $index => $id) {
            $record = static::$records[$id] ?? null;
            if ($record) {
                $record->sort_order = $index + 1;
            }
        }
    }
}
