<?php

namespace App\Http\Controllers;

use App\Models\Passkey;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Webauthn\AttestationStatement\AttestationStatementSupportManager;
use Webauthn\AuthenticatorAttestationResponse;
use Webauthn\AuthenticatorAttestationResponseValidator;
use Webauthn\CeremonyStep\CeremonyStepManagerFactory;
use Webauthn\Denormalizer\WebauthnSerializerFactory;
use Webauthn\PublicKeyCredential;

class PasskeyController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->validateWithBag('createPasskey', [
            'name' => ['required', 'string', 'max:255'],
            'passkey' => ['required'],
        ]);

        /** @var PublicKeyCredential $publicKeyCredential */
        $publicKeyCredential = (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
            ->create()
            ->deserialize($data['passkey'], PublicKeyCredential::class, 'json');

        if (!$publicKeyCredential->response instanceof AuthenticatorAttestationResponse) {
            return to_route('login');
        }

        try {
            $creationCSM = (new CeremonyStepManagerFactory)->creationCeremony();

            $data = Session::get('passkey-registration-options');
            $data->challenge = base64_decode($data->challenge);
            Session::flash('passkey-registration-options', $data);

            $publicKeyCredentialSource = AuthenticatorAttestationResponseValidator::create($creationCSM)->check(
                authenticatorAttestationResponse: $publicKeyCredential->response,
                publicKeyCredentialCreationOptions: Session::get('passkey-registration-options'),
                host: $request->getHost(),
            );
        } catch (\Exception $e) {
            throw ValidationException::withMessages([
                'name' => 'The given passkey is invalid',
                'passkey' => $e->getMessage(),
            ])->errorBag('createPasskey');
        }

        dd($publicKeyCredentialSource);

        $request->user()->passkeys()->create([
            'name' => $request->name,
            'credential_id' => base64_encode($publicKeyCredentialSource->publicKeyCredentialId),
            'data' => (new WebauthnSerializerFactory(AttestationStatementSupportManager::create()))
                ->create()
                ->serialize($publicKeyCredentialSource, 'json'),
        ]);

        return to_route('profile.edit')->withFragment('managePasskeys');

    }

    /**
     * Display the specified resource.
     */
    public function show(Passkey $passkey)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Passkey $passkey)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Passkey $passkey)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Passkey $passkey)
    {
        // delete the passkey with policy check, use Gate::authorize() to check the policy
        Gate::authorize('delete', $passkey);
        $passkey->delete();
        return back();
    }
}
