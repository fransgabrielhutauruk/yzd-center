<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Halaman ganti password untuk Member.
     */
    public function index(): Response
    {
        return Inertia::render('Member/Account/Password');
    }
}
