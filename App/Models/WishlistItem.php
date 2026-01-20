<?php

namespace App\Models;

/**
 * Lightweight DTO for representing an item in a wishlist.
 *
 * The app mostly works with array rows today; this class exists primarily to avoid an empty/stub file
 * and to provide a typed shape if you want to migrate to objects later.
 */
class WishlistItem
{
    public function __construct(
        public int $bookId,
        public ?int $position = null
    ) {
    }
}

