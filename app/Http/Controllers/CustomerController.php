<?php

namespace App\Http\Controllers;

use App\Services\C4C\CustomerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class CustomerController extends Controller
{
    /**
     * The customer service instance.
     *
     * @var CustomerService
     */
    protected $customerService;

    /**
     * Create a new controller instance.
     *
     * @param CustomerService $customerService
     */
    public function __construct(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    /**
     * Find a customer by document.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function findByDocument(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'document_type' => 'required|string|in:DNI,RUC,CE,PASSPORT',
            'document_number' => 'required|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'error' => $validator->errors()->first(),
                'data' => null
            ], 422);
        }

        $documentType = $request->input('document_type');
        $documentNumber = $request->input('document_number');

        switch ($documentType) {
            case 'DNI':
                $result = $this->customerService->findByDNI($documentNumber);
                break;
            case 'RUC':
                $result = $this->customerService->findByRUC($documentNumber);
                break;
            case 'CE':
                $result = $this->customerService->findByCE($documentNumber);
                break;
            case 'PASSPORT':
                $result = $this->customerService->findByPassport($documentNumber);
                break;
            default:
                return response()->json([
                    'success' => false,
                    'error' => 'Invalid document type',
                    'data' => null
                ], 422);
        }

        if ($result['success']) {
            return response()->json($result);
        }

        return response()->json($result, 404);
    }
}
