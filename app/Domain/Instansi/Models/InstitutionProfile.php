<?php

namespace App\Domain\Instansi\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstitutionProfile extends Model
{
    use HasFactory;

    protected $table = 'institution_profiles';

    protected $fillable = [
        'name',
        'alias',
        'address',
        'email',
        'phone',
        'website',
        'logo_path',
        'header_image_path',
        'timezone',
        'default_theme',
        'terminology_dict',
        'is_configured',
    ];

    protected $casts = [
        'terminology_dict' => 'array',
        'is_configured' => 'boolean',
    ];

    public static function current(): self
    {
        return static::firstOrCreate([], [
            'name' => 'Politeknik Negeri Demo',
            'alias' => 'POLTEK',
            'timezone' => 'Asia/Jakarta',
            'default_theme' => 'emerald',
            'terminology_dict' => [
                'unit_level_1' => 'Jurusan',
                'unit_level_2' => 'Program Studi',
                'lead_level_1' => 'Direktur',
                'lead_level_2' => 'Ketua Jurusan',
                'lead_level_3' => 'Ketua Program Studi',
                'admin_office' => 'Tata Usaha',
            ],
            'is_configured' => false,
        ]);
    }
}
