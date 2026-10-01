<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LicenseClientRequest;
use App\Services\LicenseActivationService;
use App\Services\LicenseCheckResult;
use App\Support\LicenseRequestProof;
use App\Support\LicenseResponder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class LicenseController extends Controller
{
    public function __construct(private readonly LicenseActivationService $service) {}

    public function activate(LicenseClientRequest $request): JsonResponse
    {
        return $this->respond($request, 'activate');
    }

    public function check(LicenseClientRequest $request): JsonResponse
    {
        return $this->respond($request, 'check');
    }

    private function respond(LicenseClientRequest $request, string $action): JsonResponse
    {
        $data = $request->validated();

        if (! LicenseRequestProof::verify($data, $action)) {
            return LicenseResponder::respond(
                LicenseCheckResult::fail('invalid_device_proof', 403),
                $data['device_id'],
                $data['nonce'],
                $data['device_public_key'],
            );
        }

        return DB::transaction(fn (): JsonResponse => LicenseResponder::respond(
            $action === 'activate'
                ? $this->service->activate($data, $request->ip())
                : $this->service->check($data, $request->ip()),
            $data['device_id'],
            $data['nonce'],
            $data['device_public_key'],
        ), attempts: 3);
    }
}
