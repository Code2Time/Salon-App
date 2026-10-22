<?php

namespace App\Services;

use Symfony\Component\Process\Process;
use Throwable;

class LocalNetworkAddress
{
    public function detect(): ?string
    {
        $configured = getenv('DEV_HOST');
        if ($this->usable($configured ?: null)) {
            return $configured;
        }

        try {
            $process = new Process(['ip', '-4', 'route', 'get', '1.1.1.1']);
            $process->setTimeout(1)->run();
            if ($process->isSuccessful() && preg_match('/\bsrc\s+([\d.]+)/', $process->getOutput(), $match)
                && $this->usable($match[1])) {
                return $match[1];
            }
        } catch (Throwable) {
            // Platforms without the ip command can resolve their local hostname.
        }

        foreach (gethostbynamel(gethostname() ?: 'localhost') ?: [] as $address) {
            if ($this->usable($address)) {
                return $address;
            }
        }

        return null;
    }

    private function usable(?string $address): bool
    {
        return $address !== null && filter_var($address, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)
            && ! str_starts_with($address, '127.') && $address !== '0.0.0.0';
    }
}
