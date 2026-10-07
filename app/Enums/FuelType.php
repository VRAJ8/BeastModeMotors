<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

/**
 * Fuel type.
 */
enum FuelType: string implements HasColor, HasLabel
{
    case Gasoline = 'gasoline';
    case Diesel = 'diesel';
    case Hybrid = 'hybrid';
    case PluginHybrid = 'plugin_hybrid';
    case Electric = 'electric';

    public function getLabel(): string
    {
        return match ($this) {
            self::Gasoline => 'Gasoline',
            self::Diesel => 'Diesel',
            self::Hybrid => 'Hybrid',
            self::PluginHybrid => 'Plug-in hybrid',
            self::Electric => 'Electric',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Gasoline => 'gray',
            self::Diesel => 'gray',
            self::Hybrid => 'success',
            self::PluginHybrid => 'success',
            self::Electric => 'info',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->getLabel()])->all();
    }

    /**
     * Which default maintenance schedule applies.
     */
    public function schedule(): string
    {
        return $this === self::Electric ? 'electric' : 'combustion';
    }

    public static function fromNhtsa(?string $primary, ?string $electrification = null): self
    {
        $primary = strtolower((string) $primary);
        $electrification = strtolower((string) $electrification);

        return match (true) {
            str_contains($electrification, 'phev') || str_contains($electrification, 'plug-in') => self::PluginHybrid,
            str_contains($primary, 'electric') && ! str_contains($primary, 'gasoline') => self::Electric,
            str_contains($electrification, 'hev') || str_contains($electrification, 'hybrid') => self::Hybrid,
            str_contains($primary, 'diesel') => self::Diesel,
            default => self::Gasoline,
        };
    }
}
