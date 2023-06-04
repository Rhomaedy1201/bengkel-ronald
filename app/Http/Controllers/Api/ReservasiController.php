<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InsertReservasiRequest;
use App\Models\Reservasi;
use App\Models\Service;
use App\Traits\ApiResponse;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReservasiController extends Controller
{
    use ApiResponse;
    public function insert(InsertReservasiRequest $request)
    {
        $input = $request->validated();
        $reservasi = Reservasi::where('user_id', '=', $input['user_id'])->where('status', '=', 'pending')->count();
        if ($reservasi != 3) {
            Reservasi::create($input);
            return $this->successResponse("Reservasi berhasil didata");
        } else {
            return $this->internalErrorResponse("Reservasi hanya dibatasi 3 kali untuk setiap user", 422);
        }
    }

    public function index($status)
    {
        $reservasi = Reservasi::with(['kendaraan', 'user'])->where('status', '=', $status)->get();
        return $this->responseCollection("Data reservasi", $reservasi);
    }

    public function detail($id)
    {
        $reservasi = Reservasi::with(['kendaraan', 'user'])->where('id', '=', $id)->first();
        return $this->successResponseData("Data reservasi", $reservasi);
    }
    public function changeStatus($id)
    {
        $reservasi = Reservasi::find($id);
        $reservasi->status = 'onprocess';
        $reservasi->update();
        return $this->successResponse("Data berhasil diubah..");
    }
    public function changeStatusCancel($id)
    {
        $reservasi = Reservasi::find($id);
        $reservasi->status = 'cancelled';
        $reservasi->update();
        return $this->successResponse("Data berhasil diubah..");
    }
    public function setService(Request $request, $id)
    {
        $user = Auth::user();
        $input = $request->only('odometer', 'detail', 'part_pengganti');
        $reservasi = Reservasi::find($id);
        $input += array(
            'service_advisor' => $user->nama,
            'tipe_service' => $reservasi->detail_service,
            'tanggal' => Carbon::parse($reservasi->tanggal)->format('Y-m-d'),
            'id_kendaraan' => $reservasi->kendaraan_id
        );
        Service::create($input);
        $reservasi->status = 'done';
        $reservasi->update();
        return $this->successResponse("Data service berhasil didata");
    }
}