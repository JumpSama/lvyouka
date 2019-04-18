<?php

namespace App\Http\Middleware;

use App\Member;
use Closure;

class CheckNewUser
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
        $member = session('wechat.oauth_user.default');
        $openid = $member['original']['openid'];

        $wechat = app('wechat.official_account');

        $user = $wechat->user->get($openid);

        if ($user['subscribe'] == 0) {
            return redirect(config('app.guide_url'));
        }

        $info = Member::where('openid', $openid)->first();

        $path = $request->path() === 'wechat/login';

        if (!$info) {
            if (!$path) return redirect('/wechat/login');
        } else {
            if ($path) return redirect('/wechat/me');
        }

        return $next($request);
    }
}
