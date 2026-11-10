<?php

declare(strict_types=1);

namespace App\Support\SchoolCore;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

final class AcademicPdfBranding
{
    public static function forSchool(int $schoolId): array
    {
        $school = DB::table('schools')
            ->where('id', $schoolId)
            ->first([
                'id',
                'name',
                'legal_name',
                'address',
                'logo_path',
                'primary_color',
                'secondary_color',
                'timezone',
            ]);

        abort_unless(
            $school,
            404,
            'Escuela no encontrada.'
        );

        return [
            'school_name' => (string) $school->name,

            'legal_name' =>
                (string) ($school->legal_name ?? ''),

            'address' =>
                (string) ($school->address ?? ''),

            'primary' => self::color(
                $school->primary_color,
                '#2563eb'
            ),

            'secondary' => self::color(
                $school->secondary_color,
                '#18243a'
            ),

            'logo' => self::logo(
                $school->logo_path
            ),

            // Valor de APP_NAME mediante config/app.php.
            'app_name' => (string) config('app.name'),

            'generated_at' => now(
                (string) (
                    $school->timezone
                    ?: config('app.timezone')
                )
            )->format('d/m/Y H:i'),
        ];
    }

    private static function color(
        ?string $color,
        string $fallback
    ): string {
        return is_string($color)
            && preg_match(
                '/^#[0-9a-fA-F]{6}$/D',
                $color
            )
                ? $color
                : $fallback;
    }

    private static function logo(
        ?string $storedPath
    ): ?string {
        $path = ltrim(
            str_replace(
                '\\',
                '/',
                trim((string) $storedPath)
            ),
            '/'
        );

        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, 8);
        }

        // No aceptar URLs ni rutas inseguras.
        if (
            $path === '' ||
            str_contains($path, '..') ||
            str_contains($path, '://')
        ) {
            return null;
        }

        $disk = Storage::disk('public');

        if (
            !$disk->exists($path) ||
            $disk->size($path) > 2_000_000
        ) {
            return null;
        }

        $mime = $disk->mimeType($path);

        if (!in_array(
            $mime,
            ['image/png', 'image/jpeg'],
            true
        )) {
            return null;
        }

        // DomPDF recibe los bytes directamente.
        return 'data:' . $mime . ';base64,'
            . base64_encode($disk->get($path));
    }
}
