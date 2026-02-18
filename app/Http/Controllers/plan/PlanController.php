<?php

namespace App\Http\Controllers\plan;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class PlanController extends Controller
{
    public function verplanes(){
      $planes = DB::table('planes')->get();
      /*$planes = DB::select('SELECT * FROM planes ORDER BY created_at DESC');
      $planes = null;*/


      return response()->json($planes);
    }
}
