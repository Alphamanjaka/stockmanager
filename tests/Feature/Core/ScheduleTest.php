<?php

namespace Tests\Feature\Core;

use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ScheduleTest extends TestCase
{
    /** @test */
    public function backups_and_stock_monitoring_are_scheduled()
    {
        $events = collect(app(Schedule::class)->events())
            ->mapWithKeys(fn ($event) => [$event->command => $event->expression]);

        $find = fn (string $command) => $events->first(fn ($expr, $cmd) => str_contains($cmd, $command));

        $this->assertSame('0 1 * * *', $find('app:run-backup'), 'La sauvegarde quotidienne doit être planifiée à 01:00.');
        $this->assertSame('0 2 * * 0', $find('backup:clean'), 'Le nettoyage des sauvegardes doit être planifié le dimanche à 02:00.');
        $this->assertSame('0 18 * * *', $find('stock:monitor-drop'));
    }
}
