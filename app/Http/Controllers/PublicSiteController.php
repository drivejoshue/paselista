<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PublicSiteController extends Controller
{
    public function home(): View
    {
        return view('public.home');
    }

    public function privacy(): View
    {
        return view('public.privacy');
    }

    public function dataDeletion(): View
    {
        return view('public.data-deletion');
    }

    public function support(): View
    {
        return view('public.support');
    }
}
