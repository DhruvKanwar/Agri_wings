<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Scheme;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SchemeController extends Controller
{
    public function index()
    {
        // Retrieve all schemes
        $schemes = Scheme::all();

        return response()->json(['data' => $schemes]);
    }

    public function show($id)
    {
        // Retrieve a specific scheme by ID
        $scheme = Scheme::find($id);

        if (!$scheme) {
            return response()->json(['msg' => 'Scheme not found', 'status' => 'error', 'statuscode' => '404']);
        }

        return response()->json(['data' => $scheme]);
    }

    public function store(Request $request)
    {
        // return "Ds";
        // Validate request data
        $rules = [
            'type' => 'required|string',
            'applicability' => 'required|string',
            'scheme_code' => 'required|string|unique:schemes',
            'scheme_name' => 'required|string',
            'crop_id' => 'required|string',
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
            'crop_base_price' => 'required|numeric',
            'discount_price' => 'nullable|numeric',
            'min_acreage' => 'nullable|integer',
            'max_acreage' => 'nullable|integer|gte:min_acreage',
            'client_id' => 'required|string',
            'status' => 'required|boolean',
        ];

        // Validate the request data
        $validator = Validator::make($request->all(), $rules);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        $data=$request->all();

        // Create a new scheme
        $scheme = Scheme::create($data);

        return response()->json(['msg' => 'Scheme created successfully', 'status' => 'success', 'statuscode' => '201', 'data' => $scheme], 201);
    }

    public function update(Request $request, $id)
    {
        // Validate request data
        $validatedData = $request->validate([
            'type' => 'string',
            'applicability' => 'string',
            'scheme_code' => [
                'string',
                Rule::unique('schemes')->ignore($id),
            ],
            'scheme_name' => 'string',
            'crop_id' => 'string',
            'period_from' => 'date',
            'period_to' => 'date|after_or_equal:period_from',
            'crop_base_price' => 'numeric',
            'discount_price' => 'nullable|numeric',
            'min_acreage' => 'nullable|integer',
            'max_acreage' => 'nullable|integer|gte:min_acreage',
            'client_id' => 'string',
            'status' => 'boolean',
        ]);

        // Find the scheme by ID
        $scheme = Scheme::find($id);

        if (!$scheme) {
            return response()->json(['msg' => 'Scheme not found', 'status' => 'error', 'statuscode' => '404']);
        }

        // Update the scheme
        $scheme->update($validatedData);

        return response()->json(['msg' => 'Scheme updated successfully', 'status' => 'success', 'statuscode' => '200', 'data' => $scheme]);
    }

    public function destroy($id)
    {
        // Find the scheme by ID
        $scheme = Scheme::find($id);

        if (!$scheme) {
            return response()->json(['msg' => 'Scheme not found', 'status' => 'error', 'statuscode' => '404']);
        }

        // Delete the scheme
        $scheme->delete();

        return response()->json(['msg' => 'Scheme deleted successfully', 'status' => 'success', 'statuscode' => '200']);
    }
}
