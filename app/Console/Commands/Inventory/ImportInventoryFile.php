<?php

namespace App\Console\Commands\Inventory;

use App\Jobs\Inventory\ProcessSnapshot;
use App\Models\Inventory\InvAgent;
use App\Models\Inventory\InvSnapshot;
use App\Services\Inventory\SectionReader;
use Illuminate\Console\Command;

/**
 * Imports an inventory from a file, without a real agent.
 *
 * For development and troubleshooting: the agent can export a file with
 * `glpi-agent --local=<dir> --json`, then it is loaded here by hand.
 */
class ImportInventoryFile extends Command
{
    protected $signature = 'inv:import {file : Path to the inventory .json file}';

    protected $description = 'Import an inventory JSON file (no agent needed)';

    public function handle(): int
    {
        $file = $this->argument('file');

        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $message = json_decode((string) file_get_contents($file), true);

        if (! is_array($message)) {
            $this->error('Not valid JSON: '.json_last_error_msg());

            return self::FAILURE;
        }

        $deviceid = SectionReader::value($message, 'deviceid');

        if (! is_string($deviceid) || $deviceid === '') {
            $this->error('Missing deviceid');

            return self::FAILURE;
        }

        $agent = InvAgent::updateOrCreate(
            ['deviceid' => $deviceid],
            ['last_contact_at' => now(), 'state' => 'active']
        );

        $content = json_encode($message);

        $snapshot = InvSnapshot::create([
            'inv_agent_id' => $agent->id,
            'payload' => gzcompress($content),
            'content_hash' => hash('sha256', $content),
            'protocol' => 'json',
            'received_at' => now(),
        ]);

        ProcessSnapshot::dispatchSync($snapshot->id);

        $agent->refresh();

        $this->info("Imported inventory of {$deviceid}");
        $this->line('  Matched asset id : '.($agent->asset_id ?? 'NOT MATCHED'));
        $this->line('  RustDesk ID      : '.($agent->rustdesk_id ?? 'none'));

        return self::SUCCESS;
    }
}
