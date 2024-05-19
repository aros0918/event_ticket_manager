<?php 

namespace App\Http\Middleware;
use Closure;
use Carbon\Carbon;

class CommonMiddleware {

    public function handle($request, Closure $next)
    {
        // if user lang
        if(session('my_lang'))
        {
            \App::setLocale(session('my_lang'));
            
            Carbon::setLocale(session('my_lang'));

        }    

        return $next($request);
    }
}