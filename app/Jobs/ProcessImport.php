<?php

namespace App\Jobs;

use App\Models\Import;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessImport implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Import $import,
        public array $offers,
    ) {
    }

    public function handle(): void
    {
        // Обработаем следующим фрагментом.
    }
}