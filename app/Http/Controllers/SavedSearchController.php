<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SavedSearchController extends Controller
{
    /**
     * Turn off buyer alert emails (saved searches and price drops) from the signed link in one. Opening the link asks first, so a mail scanner
     * following it changes nothing; the button, or a mail client's one-click POST, does it.
     */
    public function unsubscribe(Request $request, User $user): View
    {
        $done = $request->isMethod('post');

        if ($done) {
            $user->savedSearches()->update(['email_alerts' => false]);
            $user->update(['price_drop_alerts' => false]);
        }

        return view('saved-searches.unsubscribe', ['done' => $done]);
    }
}
