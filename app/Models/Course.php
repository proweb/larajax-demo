<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use October\Contracts\Database\SortableInterface;

/**
 * Course model for the courses content type.
 */
class Course extends Model implements SortableInterface
{
    /** In-memory list of existing IDs used by setSortableOrder. */
    protected static array $existingIds = [];

    /** Auto-incrementing ID counter (used while app is running). */
    protected static int $nextId = 1;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Track every created / found instance so setSortableOrder works.
        if (!$this->exists || !in_array($this->getKey(), self::$existingIds)) {
            self::$existingIds[] = (int) $this->getKey();
        }
    }

    /* ── SortableInterface (ListStructure reorder) ──────────────── */

    public function setSortableOrder($itemIds, $itemOrders = null): void
    {
        if (!is_array($itemIds)) {
            return;
        }
        foreach (array_values($itemIds) as $index => $id) {
            if (!is_numeric($id)) {
                continue;
            }
            $id = (int) $id;
            // Ensure this id is registered in our tracker
            if (!in_array($id, self::$existingIds)) {
                self::$existingIds[] = $id;
            }
            $this::where('id', $id)->update(['sort_order' => $index + 1]);
        }
    }
}
