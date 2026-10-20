<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response;

class BrowserAuthentication
{
    private function csrf(string $context, string $token): string
    {
        return hash_hmac('sha256', $context.'|'.$token, (string) config('app.key'));
    }

    public function handle(Request $request, Closure $next): Response
    {
        $context = $request->is('api/v1/admin/*') ? 'admin' : 'user';
        $cookieName = 'mgs_'.$context.'_session';
        $csrfName = 'mgs_'.$context.'_csrf';
        $path = $context === 'admin' ? '/api/v1/admin' : '/api/v1';
        $cookieToken = $request->cookie($cookieName);
        $usedCookie = false;
        if (! $request->header('Authorization') && ! $request->header('X-API-Key') && ! $request->header('X-MGS-Public-Key') && is_string($cookieToken) && $cookieToken !== '') {
            if (! $request->isMethodSafe() && ! hash_equals($this->csrf($context, $cookieToken), (string) $request->header('X-MGS-CSRF'))) {
                return response()->json(['success' => false, 'message' => 'Refresh the page before continuing.', 'code' => 'CSRF_MISMATCH'], 419);
            }
            $request->headers->set('Authorization', 'Bearer '.$cookieToken);
            $usedCookie = true;
        }
        $browserRequest = $request->header('X-Browser-Auth') === '1';
        if ($browserRequest && $request->header('Origin') && rtrim($request->header('Origin'), '/') !== $request->getSchemeAndHttpHost()) {
            return response()->json(['success' => false, 'message' => 'Browser authentication requires the same origin.'], 403);
        }
        $response = $next($request);
        $secure = $request->isSecure() || app()->environment('production');
        $login = in_array($request->path(), ['api/v1/admin/auth/login', 'api/v1/auth/login', 'api/v1/auth/register', 'api/v1/auth/register/verify-otp'], true);
        if ($browserRequest && $login && $response instanceof JsonResponse && $response->isSuccessful()) {
            $body = $response->getData(true);
            $token = $body['data']['token'] ?? null;
            if (is_string($token) && $token !== '') {
                $expires = now()->addHours(12);
                $response->headers->setCookie(new Cookie($cookieName, $token, $expires, $path, null, $secure, true, false, Cookie::SAMESITE_STRICT));
                $response->headers->setCookie(new Cookie($csrfName, $this->csrf($context, $token), $expires, '/', null, $secure, false, false, Cookie::SAMESITE_STRICT));
                unset($body['data']['token']);
                $body['data']['browser_session'] = true;
                $body['data']['token_type'] = 'Cookie';
                $response->setData($body);
                $response->headers->set('Cache-Control', 'private, no-store');
            }
        }
        $logout = preg_match('#^api/v1/(?:admin/)?auth/logout(?:-all)?$#', $request->path());
        $mfaConfirmed = $request->path() === 'api/v1/admin/auth/mfa/confirm';
        if (($usedCookie && $response->getStatusCode() === 401) || (($logout || $mfaConfirmed) && $response->isSuccessful())) {
            $response->headers->clearCookie($cookieName, $path, null, $secure, true, Cookie::SAMESITE_STRICT);
            $response->headers->clearCookie($csrfName, '/', null, $secure, false, Cookie::SAMESITE_STRICT);
        }
        return $response;
    }
}
