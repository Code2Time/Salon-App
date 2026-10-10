<?php

namespace App\Services;

use App\Models\Salon;
use LogicException;

class CurrentSalon
{
    private ?Salon $salon = null;

    public function set(Salon $salon): void
    {
        $this->salon = $salon;
    }

    public function get(): Salon
    {
        return $this->salon ?? throw new LogicException('Salon context is not resolved.');
    }
}
