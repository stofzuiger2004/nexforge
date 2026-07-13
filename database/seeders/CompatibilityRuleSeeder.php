<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\CompatibilityRuleType;
use App\Enums\CompatibilitySeverity;
use App\Enums\ComponentSlot;
use App\Models\CompatibilityRule;
use App\Models\Specification;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CompatibilityRuleSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            /** @var Collection<string, int> $specifications */
            $specifications = Specification::query()
                ->pluck('id', 'key');

            $this->seedRequiredSlotRules();
            $this->seedPairRules($specifications);
        });
    }

    private function seedRequiredSlotRules(): void
    {
        $definitions = [
            'required_cpu' => [
                'Processor is required',
                ComponentSlot::Cpu,
                'Select a processor before validating this configuration.',
            ],

            'required_motherboard' => [
                'Motherboard is required',
                ComponentSlot::Motherboard,
                'Select a motherboard before validating this configuration.',
            ],

            'required_graphics_card' => [
                'Graphics card is required',
                ComponentSlot::GraphicsCard,
                'Select a graphics card before validating this configuration.',
            ],

            'required_memory' => [
                'Memory is required',
                ComponentSlot::Memory,
                'Select memory before validating this configuration.',
            ],

            'required_primary_storage' => [
                'Primary storage is required',
                ComponentSlot::PrimaryStorage,
                'Select a primary storage drive before validating this configuration.',
            ],

            'required_power_supply' => [
                'Power supply is required',
                ComponentSlot::PowerSupply,
                'Select a power supply before validating this configuration.',
            ],

            'required_case' => [
                'Case is required',
                ComponentSlot::Case,
                'Select a case before validating this configuration.',
            ],

            'required_cpu_cooler' => [
                'CPU cooler is required',
                ComponentSlot::CpuCooler,
                'Select a CPU cooler before validating this configuration.',
            ],
        ];

        $sortOrder = 10;

        foreach (
            $definitions as $key => [$name, $slot, $message]
        ) {
            $this->upsertRule([
                'key' => $key,
                'name' => $name,

                'rule_type' => CompatibilityRuleType::RequiredSlot,

                'severity' => CompatibilitySeverity::Error,

                'source_slot' => $slot,
                'source_specification_id' => null,

                'target_slot' => null,
                'target_specification_id' => null,

                'failure_message' => $message,
                'sort_order' => $sortOrder,
            ]);

            $sortOrder += 10;
        }
    }

    /**
     * @param  Collection<string, int>  $specifications
     */
    private function seedPairRules(
        Collection $specifications,
    ): void {
        $definitions = [
            [
                'key' => 'cpu_motherboard_socket',
                'name' => 'CPU and motherboard socket',

                'rule_type' => CompatibilityRuleType::SameOption,

                'source_slot' => ComponentSlot::Cpu,
                'source_specification' => 'socket',

                'target_slot' => ComponentSlot::Motherboard,

                'target_specification' => 'socket',

                'failure_message' => '{source_name} uses {source_value}, but {target_name} uses {target_value}.',
            ],

            [
                'key' => 'memory_motherboard_type',
                'name' => 'Memory and motherboard type',

                'rule_type' => CompatibilityRuleType::SameOption,

                'source_slot' => ComponentSlot::Memory,
                'source_specification' => 'memory_type',

                'target_slot' => ComponentSlot::Motherboard,

                'target_specification' => 'memory_type',

                'failure_message' => '{source_name} uses {source_value}, but {target_name} supports {target_value}.',
            ],

            [
                'key' => 'motherboard_case_form_factor',

                'name' => 'Motherboard and case form factor',

                'rule_type' => CompatibilityRuleType::OptionContainedInTarget,

                'source_slot' => ComponentSlot::Motherboard,

                'source_specification' => 'motherboard_form_factor',

                'target_slot' => ComponentSlot::Case,

                'target_specification' => 'supported_motherboard_form_factors',

                'failure_message' => '{source_name} has form factor {source_value}, which is not supported by {target_name}.',
            ],

            [
                'key' => 'cpu_cooler_socket',
                'name' => 'CPU and cooler socket',

                'rule_type' => CompatibilityRuleType::OptionContainedInTarget,

                'source_slot' => ComponentSlot::Cpu,
                'source_specification' => 'socket',

                'target_slot' => ComponentSlot::CpuCooler,

                'target_specification' => 'cooler_sockets',

                'failure_message' => '{target_name} does not support the {source_value} socket used by {source_name}.',
            ],

            [
                'key' => 'gpu_case_length',

                'name' => 'Graphics card length and case clearance',

                'rule_type' => CompatibilityRuleType::NumericLessThanOrEqual,

                'source_slot' => ComponentSlot::GraphicsCard,

                'source_specification' => 'gpu_length_mm',

                'target_slot' => ComponentSlot::Case,

                'target_specification' => 'case_max_gpu_length_mm',

                'failure_message' => '{source_name} is {source_value} mm long, but {target_name} allows at most {target_value} mm.',
            ],

            [
                'key' => 'cooler_case_height',

                'name' => 'CPU cooler height and case clearance',

                'rule_type' => CompatibilityRuleType::NumericLessThanOrEqual,

                'source_slot' => ComponentSlot::CpuCooler,

                'source_specification' => 'cooler_height_mm',

                'target_slot' => ComponentSlot::Case,

                'target_specification' => 'case_max_cooler_height_mm',

                'failure_message' => '{source_name} is {source_value} mm tall, but {target_name} allows at most {target_value} mm.',
            ],

            [
                'key' => 'gpu_psu_requirement',

                'name' => 'Graphics card power requirement',

                'rule_type' => CompatibilityRuleType::NumericLessThanOrEqual,

                'source_slot' => ComponentSlot::GraphicsCard,

                'source_specification' => 'recommended_psu_watts',

                'target_slot' => ComponentSlot::PowerSupply,

                'target_specification' => 'psu_wattage',

                'failure_message' => '{source_name} recommends at least {source_value} W, but {target_name} provides {target_value} W.',
            ],
        ];

        $sortOrder = 100;

        foreach ($definitions as $definition) {
            $this->upsertRule([
                'key' => $definition['key'],
                'name' => $definition['name'],

                'rule_type' => $definition['rule_type'],

                'severity' => CompatibilitySeverity::Error,

                'source_slot' => $definition['source_slot'],

                'source_specification_id' => $this->specificationId(
                    $specifications,
                    $definition[
                        'source_specification'
                    ],
                ),

                'target_slot' => $definition['target_slot'],

                'target_specification_id' => $this->specificationId(
                    $specifications,
                    $definition[
                        'target_specification'
                    ],
                ),

                'failure_message' => $definition['failure_message'],

                'sort_order' => $sortOrder,
            ]);

            $sortOrder += 10;
        }
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function upsertRule(
        array $attributes,
    ): void {
        $rule = CompatibilityRule::withTrashed()
            ->firstOrNew([
                'key' => $attributes['key'],
            ]);

        $rule->fill([
            ...$attributes,
            'description' => null,
            'settings' => null,
            'revision' => 1,
            'is_active' => true,
        ]);

        $rule->deleted_at = null;
        $rule->save();
    }

    /**
     * @param  Collection<string, int>  $specifications
     */
    private function specificationId(
        Collection $specifications,
        string $key,
    ): int {
        $id = $specifications->get($key);

        if (! is_int($id)) {
            throw new RuntimeException(sprintf(
                'Specification "%s" must be seeded before compatibility rules.',
                $key,
            ));
        }

        return $id;
    }
}
