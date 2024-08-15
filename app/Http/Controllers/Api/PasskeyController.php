<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Webauthn\PublicKeyCredentialRpEntity;
use Webauthn\PublicKeyCredentialUserEntity;
use Webauthn\AuthenticatorSelectionCriteria;
use Webauthn\PublicKeyCredentialCreationOptions;
use Webauthn\PublicKeyCredentialOptions;
use Webauthn\PublicKeyCredentialRequestOptions;

class PasskeyController extends Controller
{
    public function registerOptions(Request $request)
    {
        $request->validate(['name' => ['required', 'string', 'max:255']]);

        $options = new PublicKeyCredentialCreationOptions(
            rp: new PublicKeyCredentialRpEntity(
                name: config('app.name'),
                id: parse_url(config('app.url'), PHP_URL_HOST),
            ),
            challenge: base64_encode(Str::random()),
            user: new PublicKeyCredentialUserEntity(
                name: $request->user()->email,
                id: base64_encode($request->user()->id),
                displayName: $request->user()->name,
            ),
            authenticatorSelection: new AuthenticatorSelectionCriteria(
                authenticatorAttachment: AuthenticatorSelectionCriteria::AUTHENTICATOR_ATTACHMENT_NO_PREFERENCE,
                residentKey: AuthenticatorSelectionCriteria::RESIDENT_KEY_REQUIREMENT_REQUIRED,
            ),
        );

        Session::flash('passkey-registration-options', $options);

        return json_encode($options);
    }

    public function authenticateOptions()
    {
        $options = new PublicKeyCredentialRequestOptions(
            challenge: Str::random(),
            rpId: parse_url(config('app.url'), PHP_URL_HOST),
        );

        Session::flash('passkey-authentication-options', $options);

        return json_encode($options);
    }
}


