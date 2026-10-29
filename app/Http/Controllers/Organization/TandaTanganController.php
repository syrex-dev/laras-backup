<?php

namespace App\Http\Controllers\Organization;

use App\Domain\Organization\Actions\SaveTandaTanganAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Organization\StoreTandaTanganRequest;
use Illuminate\Support\Facades\Auth;

class TandaTanganController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $tandaTangan = $user->tandaTangan;

        return view('organization.signature.index', compact('tandaTangan'));
    }

    public function store(StoreTandaTanganRequest $request, SaveTandaTanganAction $saveAction)
    {
        $saveAction->execute(Auth::id(), $request->file('tanda_tangan'));

        return back()->with('success', 'Tanda tangan Anda berhasil diunggah.');
    }
}
