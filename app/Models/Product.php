<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 *
 * @property string $unique_key
 * @property string $product_title
 * @property string|null $product_description
 * @property string|null $style_number
 * @property string|null $sanmar_mainframe_color
 * @property string|null $size
 * @property string|null $color_name
 * @property float $piece_price
 * @property int $inventory_count
 */

class Product extends Model
{
    protected $fillable = [
        'unique_key',
        'product_title',
        'product_description',
        'style_number',
        'sanmar_mainframe_color',
        'size',
        'color_name',
        'piece_price',
        'inventory_count',
        'created_at',
        'updated_at',
    ];
}
