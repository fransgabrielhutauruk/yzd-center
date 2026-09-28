<?php

namespace App\Http\Controllers\Admin\Settings;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Halaman ganti password untuk Admin.
     */
    public function index(): Response
    {
        return Inertia::render('Admin/Settings/Password');
    }
}
