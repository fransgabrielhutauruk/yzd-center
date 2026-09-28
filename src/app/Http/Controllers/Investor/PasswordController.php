<?php

namespace App\Http\Controllers\Investor;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PasswordController extends Controller
{
    /**
     * Halaman ganti password untuk Investor/Affiliator.
     */
    public function index(): Response
    {
        return Inertia::render('Investor/Account/Password');
    }
}
