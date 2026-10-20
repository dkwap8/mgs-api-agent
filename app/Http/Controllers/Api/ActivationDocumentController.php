<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BusinessActivation;
use App\Services\ActivationDocumentService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ActivationDocumentController extends Controller
{
    public function admin(Request $request, int $id, string $field, ActivationDocumentService $documents): BinaryFileResponse
    {
        return $documents->serve(BusinessActivation::findOrFail($id), $field, $request->boolean('download'));
    }

    public function merchant(Request $request, int $id, string $field, ActivationDocumentService $documents): BinaryFileResponse
    {
        $activation = BusinessActivation::where('business_id', $request->activeBusiness()->id)
            ->where('merchant_id', $request->activeMerchant()->id)->findOrFail($id);
        return $documents->serve($activation, $field, $request->boolean('download'));
    }
}
