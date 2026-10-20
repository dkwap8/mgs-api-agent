<p>Hello,</p>
<p>Use the following token to reset your MGS password:</p>
<p><strong>{{ $token }}</strong></p>
<p>This token expires in {{ $expiresInMinutes }} minutes and can only be used once.</p>
<p><a href="{{ rtrim(config('app.url'), '/') }}/user/reset-password?{{ http_build_query(['token' => $token, 'email' => $email]) }}">Reset your password</a></p>
<p>If you did not request a password reset, you can ignore this message.</p>
