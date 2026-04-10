<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Menu;

class DashboardController extends Controller
{
    public function index()
    {
        $menus = auth()->user()->getAdminMenus();

        return view('admin.dashboard', compact('menus'));
    }
}
