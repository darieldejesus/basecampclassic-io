<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class ProjectController extends Controller
{
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