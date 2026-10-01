<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LicenseClientRequest;
use App\Services\LicenseActivationService;
use App\Support\LicenseResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LicenseController extends Controller
{
    public function __construct(private readonly LicenseActivationService $service) {}

    public function activate(LicenseClientRequest $request): JsonResponse
    {
        return DB::transaction(fn (): JsonResponse => LicenseResponder::respond(
            $this->service->activate($request->validated(), $request->ip()),
            $request->validated('device_id'),
            $request->validated('nonce'),
        ), attempts: 3);
    }

    public function check(LicenseClientRequest $request): JsonResponse
    {
        return DB::transaction(fn (): JsonResponse => LicenseResponder::respond(
            $this->service->check($request->validated(), $request->ip()),
            $request->validated('device_id'),
            $request->validated('nonce'),
        ), attempts: 3);
    }
}
