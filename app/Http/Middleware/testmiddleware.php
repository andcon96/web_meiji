<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class testmiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $this->insertuser();
        return $next($request);
    }
    public function insertuser(){
        DB::table('users')
        ->where('username','=',session::get('username'))
        ->update(['userdate'=> Carbon::now('ASIA/JAKARTA')->toDateTimeString()]);
    }
}
