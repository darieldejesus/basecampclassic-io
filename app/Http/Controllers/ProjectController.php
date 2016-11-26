<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function getActived(Request $request) {
        return view('actived');
    }

    public function getOnHold(Request $request) {
        return view('onhold');
    }

    public function getArchived(Request $request) {
        return view('archived');
    }
}
