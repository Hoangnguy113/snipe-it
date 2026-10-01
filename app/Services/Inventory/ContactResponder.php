<?php

namespace App\Services\Inventory;

/**
 * Builds the CONTACT answer that makes the agent switch to the JSON protocol.
 *
 * Protocol/Contact.pm:31-40: the agent only treats the peer as a GLPI server
 * when the answer has 'status' AND 'expiration' > 0; otherwise it falls back
 * to the legacy XML protocol.
 */
class ContactResponder
{
    /**
     * @return array<string, mixed>
     */
    public function answer(): array
    {
        $inventoryHours = (int) config('inventory.inventory_interval_hours');
        $deployHours = (int) config('inventory.deploy_poll_hours');

        return [
            'status' => 'ok',
            'expiration' => $inventoryHours,
            'tasks' => [
                'inventory' => [
                    // Without 'server' + 'version' the agent assumes a legacy
                    // server and re-sends an XML PROLOG on every run
                    // (Target/Server.pm:126-144).
                    'server' => 'glpi',
                    'version' => '1.0',
                    'params' => [
                        ['content' => '', 'frequency' => $inventoryHours, 'unit' => 'hour'],
                    ],
                ],
                'deploy' => [
                    'params' => [
                        ['content' => '', 'frequency' => $deployHours, 'unit' => 'hour'],
                    ],
                ],
                // RB-6: 'collect' is deliberately NOT declared here. See spec 12.1.
            ],
        ];
    }
}
