<?php

namespace Tests\Unit;

use App\Services\Google\GoogleServiceAccount;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class GoogleServiceAccountTest extends TestCase
{
    public function test_credentials_is_null_when_no_service_account_is_configured(): void
    {
        config(['services.firebase.credentials_path' => null]);

        $this->assertNull((new GoogleServiceAccount)->credentials());
    }

    public function test_access_token_is_null_when_no_service_account_is_configured(): void
    {
        config(['services.firebase.credentials_path' => null]);

        $this->assertNull((new GoogleServiceAccount)->accessToken('some-scope'));
    }

    public function test_access_token_exchanges_a_signed_jwt_for_an_oauth_token(): void
    {
        $path = $this->fakeServiceAccountFile();
        config(['services.firebase.credentials_path' => $path]);

        Http::fake([
            'https://oauth2.googleapis.com/token' => Http::response(['access_token' => 'fake-access-token'], 200),
        ]);

        $token = (new GoogleServiceAccount)->accessToken('https://example.test/scope');

        $this->assertSame('fake-access-token', $token);
        Http::assertSent(fn ($request) => $request->url() === 'https://oauth2.googleapis.com/token'
            && $request['grant_type'] === 'urn:ietf:params:oauth:grant-type:jwt-bearer');
    }

    public function test_sign_custom_token_embeds_the_uid_and_claims(): void
    {
        $path = $this->fakeServiceAccountFile();
        config(['services.firebase.credentials_path' => $path]);

        $jwt = (new GoogleServiceAccount)->signCustomToken('user_42', ['role' => 'buyer']);

        [$header, $payload] = explode('.', $jwt);
        $claims = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);

        $this->assertSame('RS256', json_decode(base64_decode(strtr($header, '-_', '+/')), true)['alg']);
        $this->assertSame('user_42', $claims['uid']);
        $this->assertSame(['role' => 'buyer'], $claims['claims']);
    }

    private function fakeServiceAccountFile(): string
    {
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $privateKey);

        $path = tempnam(sys_get_temp_dir(), 'fake-service-account').'.json';
        file_put_contents($path, json_encode([
            'client_email' => 'test@fake-project.iam.gserviceaccount.com',
            'private_key' => $privateKey,
            'project_id' => 'fake-project',
        ]));

        $this->beforeApplicationDestroyed(fn () => @unlink($path));

        return $path;
    }
}
