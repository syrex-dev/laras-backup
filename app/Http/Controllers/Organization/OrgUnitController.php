<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Audit\Services\AuditLogger;
use App\Domain\Pengguna\Models\OrgUnit;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;

class OrgUnitController extends Controller
{
    public function index()
    {
        $units = OrgUnit::with(['parent', 'positions'])
            ->orderBy('order_index')
            ->get();

        return Inertia::render('Organisasi/UnitKerja', [
            'units' => $units,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:org_units,code',
            'type' => 'required|string|in:pimpinan,jurusan,prodi,bagian,unit_pelaksana',
            'parent_id' => 'nullable|exists:org_units,id',
            'order_index' => 'nullable|integer',
        ], [
            'name.required' => 'Nama unit kerja wajib diisi.',
            'code.required' => 'Kode unit kerja wajib diisi.',
            'code.unique' => 'Kode unit kerja sudah digunakan oleh unit lain.',
            'type.required' => 'Tipe unit kerja wajib dipilih.',
        ]);

        $unit = OrgUnit::create($validated);

        AuditLogger::log(
            action: 'org_unit.created',
            subject: $unit,
            description: "Unit kerja baru '{$unit->name}' ({$unit->code}) berhasil ditambahkan",
            properties: $unit->toArray()
        );

        return back()->with('success', "Unit kerja {$unit->name} berhasil ditambahkan!");
    }

    public function update(Request $request, OrgUnit $unit)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:org_units,code,'.$unit->id,
            'type' => 'required|string|in:pimpinan,jurusan,prodi,bagian,unit_pelaksana',
            'parent_id' => 'nullable|exists:org_units,id',
            'order_index' => 'nullable|integer',
        ], [
            'name.required' => 'Nama unit kerja wajib diisi.',
            'code.required' => 'Kode unit kerja wajib diisi.',
            'code.unique' => 'Kode unit kerja sudah digunakan oleh unit lain.',
        ]);

        $oldData = $unit->toArray();
        $unit->update($validated);

        AuditLogger::log(
            action: 'org_unit.updated',
            subject: $unit,
            description: "Unit kerja '{$unit->name}' berhasil diperbarui",
            properties: ['old' => $oldData, 'new' => $unit->toArray()]
        );

        return back()->with('success', "Unit kerja {$unit->name} berhasil diperbarui!");
    }

    public function destroy(OrgUnit $unit)
    {
        $name = $unit->name;
        $unitData = $unit->toArray();
        $unit->delete();

        AuditLogger::log(
            action: 'org_unit.deleted',
            subject: 'OrgUnit#'.($unitData['id'] ?? 0),
            description: "Unit kerja '{$name}' telah dihapus dari sistem",
            properties: $unitData
        );

        return back()->with('success', "Unit kerja {$name} berhasil dihapus!");
    }
}
