<?php

namespace App\Http\Controllers\Api\Panel;

use App\Http\Controllers\Controller;
use App\Services\CurrentSalon;
use App\Services\LocalNetworkAddress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AccessLinkController extends Controller
{
    public function __invoke(Request $request, CurrentSalon $context, LocalNetworkAddress $network): JsonResponse
    {
        $origin = $request->getSchemeAndHttpHost();
        if (! $this->shareable($origin)) {
            // Replace only the local host; the port belongs to the server handling this request.
            $host = $network->detect();
            $port = $request->getPort();
            $defaultPort = $request->isSecure() ? 443 : 80;
            $origin = $host ? $request->getScheme().'://'.$host.($port === $defaultPort ? '' : ':'.$port) : '';
        }

        return response()->json(['data' => [
            'salon_name' => $context->get()->name,
            'url' => $this->shareable($origin) ? $origin.'/app' : null,
        ]]);
    }

    private function shareable(string $url): bool
    {
        $host = strtolower(trim((string) parse_url($url, PHP_URL_HOST), '[]'));

        return in_array(parse_url($url, PHP_URL_SCHEME), ['http', 'https'], true)
            && $host !== ''
            && ! in_array($host, ['localhost', '0.0.0.0', '::', '::1'], true)
            && ! str_ends_with($host, '.localhost')
            && ! str_starts_with($host, '127.');
    }
}
