<?php

use App\Controllers\UserGroupsController;
use App\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect('users'));

/*
|--------------------------------------------------------------------------
| Users
|--------------------------------------------------------------------------
|
| A full CRUD resource demonstrating the List, Filter, Toolbar, Form and
| ListStructure widgets. Routes are declared with match(['get','post']) so the
| same URL serves the initial page (GET) and the widget AJAX handlers (POST).
|
*/
Route::match(['get', 'post'], '/users', [UsersController::class, 'index'])->name('users.index');
Route::match(['get', 'post'], '/users/structure', [UsersController::class, 'structure'])->name('users.structure');
Route::match(['get', 'post'], '/users/create', [UsersController::class, 'create'])->name('users.create');
Route::match(['get', 'post'], '/users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');

/*
|--------------------------------------------------------------------------
| User Groups
|--------------------------------------------------------------------------
|
| A second, simpler resource (list + form, no filter) showing the widgets are
| configuration-driven and not tied to any one model.
|
*/
Route::match(['get', 'post'], '/user-groups', [UserGroupsController::class, 'index'])->name('user-groups.index');
Route::match(['get', 'post'], '/user-groups/create', [UserGroupsController::class, 'create'])->name('user-groups.create');
Route::match(['get', 'post'], '/user-groups/{userGroup}/edit', [UserGroupsController::class, 'edit'])->name('user-groups.edit');
