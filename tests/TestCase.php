<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

/**
 * @property \App\Models\User $user
 * @property \App\Models\Utility\Business $business
 * @property \App\Models\Master\Menu $menu
 * @property \App\Models\Master\MenuCategory $category
 * @property \App\Models\Master\MenuFoodType $foodType
 * @property \App\Models\Master\MenuItem $menuItem
 */
abstract class TestCase extends BaseTestCase
{
    public $user;
    public $business;
    public $menu;
    public $category;
    public $foodType;
    public $menuItem;
}
