<?php

namespace Workbench\App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Workbench\Database\Factories\MenuItemFactory;

class MenuItem extends Model
{
    /** @use HasFactory<\Workbench\Workbench\Database\Factories\MenuItemFactory> */
    use HasFactory;

    protected static $factory = MenuItemFactory::class;

    protected $guarded = [];
}
