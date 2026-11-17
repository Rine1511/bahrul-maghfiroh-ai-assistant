<?php

namespace App\Chatbot\Http;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mengizinkan website lain (yang memasang widget) memanggil endpoint chatbot.
 * Daftar website yang diizinkan diatur di config('chatbot.allowed_origins').
 */
class CorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $request->isMethod('OPTIONS') ? response('', 204) : $next($request);

        $allow = $this->allowedOrigin($request->headers->get('Origin'));

        if ($allow !== null) {
            $response->headers->set('Access-Control-Allow-Origin', $allow);
            $response->headers->set('Access-Control-Allow-Methods', 'POST, OPTIONS');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, X-Requested-With');
            $response->headers->set('Access-Control-Max-Age', '86400');
            $response->headers->set('Vary', 'Origin');
        }

        return $response;
    }

    private function allowedOrigin(?string $origin): ?string
    {
        $list = array_filter(array_map(
            fn ($o) => rtrim(trim($o), '/'),
            explode(',', (string) config('chatbot.allowed_origins', '*'))
        ));

        if (in_array('*', $list, true)) {
            return '*';
        }

        if ($origin && in_array(rtrim($origin, '/'), $list, true)) {
            return $origin;
        }

        return null;
    }
}
