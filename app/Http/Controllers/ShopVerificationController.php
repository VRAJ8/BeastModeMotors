<?php

namespace App\Http\Controllers;

use App\Models\ShopVerification;
use App\Services\ShopVerifier;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The page a repair shop lands on from its verification email.
 */
class ShopVerificationController extends Controller
{
    public function show(ShopVerification $verification): View
    {
        $verification->load('record.vehicle', 'record.documents', 'requester');

        return view('verify.show', ['verification' => $verification]);
    }

    public function store(Request $request, ShopVerification $verification, ShopVerifier $verifier): View
    {
        $data = $request->validate([
            'decision' => ['required', 'in:confirm,dispute'],
            'responder_name' => ['required', 'string', 'max:120'],
            'response_note' => ['nullable', 'string', 'max:1000', 'required_if:decision,dispute'],
        ], [
            'response_note.required_if' => 'Tell the owner what doesn\'t match your records.',
        ]);

        $verifier->answer($verification, $data['decision'] === 'confirm', $data['responder_name'], $data['response_note'] ?? null, $request->ip());

        return view('verify.done', ['verification' => $verification->fresh('record.vehicle')]);
    }
}
