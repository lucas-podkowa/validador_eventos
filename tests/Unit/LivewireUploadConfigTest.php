<?php

namespace Tests\Unit;

use Tests\TestCase;

class LivewireUploadConfigTest extends TestCase
{
    /**
     * Test that Livewire temporary upload rules allow 30MB.
     */
    public function test_livewire_temporary_upload_rules_allow_30mb(): void
    {
        $config = config('livewire.temporary_file_upload');

        $this->assertIsArray($config['rules']);
        $this->assertContains('max:30720', $config['rules']);
    }
}
