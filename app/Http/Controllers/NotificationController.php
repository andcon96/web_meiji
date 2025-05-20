<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function markAsRead(Request $req)
    {
        auth()->user()
        ->unreadNotifications
        ->when($req->input('id'), function ($query) use ($req) {
            return $query->where('id', $req->input('id'));
        })
        ->markAsRead();

        // DB::table('notifications')->where('id', '=', $req->input('id'))->delete();

        return response()->noContent();
    }

    public function markAllAsRead()
    {
        auth()->user()->unreadNotifications()->update(['read_at' => now()]);
        // DB::table('notifications')->where('id', '=', $req->input('id'))->delete();

        return response()->noContent();
    }
}
