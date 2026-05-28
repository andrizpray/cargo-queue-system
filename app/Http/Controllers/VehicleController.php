<?php

namespace App\Http\Controllers;

use App\Models\Vehicle;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class VehicleController extends Controller
{
    public function scan(Request $request): JsonResponse
    {
        $data = $request->validate([
            'barcode_code128' => 'required|string|exists:vehicles,barcode_code128',
        ]);

        $vehicle = Vehicle::with('vehicleType', 'location')
            ->where('barcode_code128', $data['barcode_code128'])
            ->first();

        return response()->json(['data' => $vehicle]);
    }

    public function show(string $barcode): JsonResponse
    {
        $validator = Validator::make(['barcode' => $barcode], [
            'barcode' => 'required|string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => $validator->errors()->first()], 422);
        }

        $vehicle = Vehicle::with('vehicleType', 'location')
            ->where('barcode_code128', $barcode)
            ->first();

        if (!$vehicle) {
            return response()->json(['message' => 'Vehicle not found'], 404);
        }

        return response()->json(['data' => $vehicle]);
    }
}
