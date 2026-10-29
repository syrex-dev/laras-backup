<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Domain\Pengguna\Models\Position;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class PositionController extends Controller
{
    public function index()
    {
        $positions = Position::with(['orgUnit', 'users'])
            ->orderBy('level')
            ->orderBy('org_unit_id')
            ->get();

        $units = OrgUnit::orderBy('order_index')->get();

        return Inertia::render('Organisasi/Jabatan', [
            'positions' => $positions,
            'units' => $units,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'org_unit_id' => 'required|exists:org_units,id',
            'name' => 'required|string|max:255',
            'level' => 'required|integer|min:1|max:10',
            'is_penandatangan' => 'boolean',
        ], [
            'org_unit_id.required' => 'Unit kerja wajib dipilih.',
            'org_unit_id.exists' => 'Unit kerja tidak valid.',
            'name.required' => 'Nama jabatan wajib diisi.',
            'level.required' => 'Tingkat eselon/level wajib diisi.',
            'level.integer' => 'Tingkat eselon/level harus berupa angka.',
        ]);

        $validated['is_penandatangan'] = $request->boolean('is_penandatangan');

        $position = Position::create($validated);

        AuditLogger::log(
            action: 'position.created',
            subject: $position,
            description: "Jabatan baru '{$position->name}' berhasil ditambahkan ke unit kerja #{$position->org_unit_id}",
            properties: $position->toArray()
        );

        return back()->with('success', "Jabatan {$position->name} berhasil ditambahkan!");
    }

    public function update(Request $request, Position $position)
    {
        $validated = $request->validate([
            'org_unit_id' => 'required|exists:org_units,id',
            'name' => 'required|string|max:255',
            'level' => 'required|integer|min:1|max:10',
            'is_penandatangan' => 'boolean',
        ], [
            'org_unit_id.required' => 'Unit kerja wajib dipilih.',
            'name.required' => 'Nama jabatan wajib diisi.',
            'level.required' => 'Tingkat eselon/level wajib diisi.',
        ]);

        $validated['is_penandatangan'] = $request->boolean('is_penandatangan');

        $oldData = $position->toArray();
        $position->update($validated);

        AuditLogger::log(
            action: 'position.updated',
            subject: $position,
            description: "Jabatan '{$position->name}' berhasil diperbarui",
            properties: ['old' => $oldData, 'new' => $position->toArray()]
        );

        return back()->with('success', "Jabatan {$position->name} berhasil diperbarui!");
    }

    public function destroy(Position $position)
    {
        $name = $position->name;
        $data = $position->toArray();

        // Cek jika masih ada pengguna aktif yang menduduki jabatan
        if ($position->users()->where('is_active', true)->exists()) {
            return back()->withErrors([
                'message' => "Jabatan '{$name}' tidak dapat dihapus karena masih ada pengguna aktif yang menduduki jabatan ini.",
            ]);
        }

        $position->delete();

        AuditLogger::log(
            action: 'position.deleted',
            subject: 'Position#'.($data['id'] ?? 0),
            description: "Jabatan '{$name}' telah dihapus dari sistem",
            properties: $data
        );

        return back()->with('success', "Jabatan {$name} berhasil dihapus!");
    }
}
