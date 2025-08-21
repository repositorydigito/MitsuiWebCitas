<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * Progressive Time Selector Component
 * Componente para cargar slots de tiempo con progressive loading
 */
class ProgressiveTimeSelector extends Component
{
    public string $centerId;
    public string $fecha;

    /**
     * Create a new component instance.
     */
    public function __construct(string $centerId, string $fecha)
    {
        $this->centerId = $centerId;
        $this->fecha = $fecha;
    }

    /**
     * Get the view / contents that represent the component.
     */
    public function render(): View
    {
        return view('components.progressive-time-selector');
    }
}