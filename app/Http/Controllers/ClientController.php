<?php

namespace App\Http\Controllers;

use App\Models\BaseClient;
use Illuminate\Http\Request;
use App\Models\RegionalClient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ClientController extends Controller
{
    public function submit_client_details(Request $request)
    {
        $data = $request->all();
        // return $data;

        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'base_client.client_name' => 'required|string|max:255',
            'base_client.pan_no' => 'nullable|string|max:255',
            'base_client.cin' => 'nullable|string|max:255',
            'base_client.registration_address' => 'nullable|string',
            'base_client.account_no' => 'nullable|string|max:255',
            'base_client.ifsc' => 'nullable|string|max:255',
            'base_client.bank_name' => 'nullable|string|max:255',
            'base_client.branch_name' => 'nullable|string|max:255',
            'base_client.upi_id' => 'nullable|string|max:255',
            'base_client.gst_nature' => 'nullable|string|max:255',
            'base_client.signature_name' => 'nullable|string|max:255',
            'base_client.qr_code' =>'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'base_client.sign_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'base_client.logo_img' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'base_client.phone_number' => 'nullable|string|max:255',
            'base_client.action_key' => 'required|string|in:create,update,delete,no_change',
            'regional_clients.*.remarks' => 'nullable|string',
            'regional_clients.*.client_id' => 'nullable|string',
            'regional_clients.*.base_client_id' => 'nullable|string',
            'regional_clients.*.regional_client_name' => 'required|string',
            'regional_clients.*.state' => 'required|string',
            'regional_clients.*.gst_no' => 'required|string',
            'regional_clients.*.address' => 'required|string',
            'regional_clients.*.action_key' => 'required|string|in:create,update,delete,no_change',
        ]);

        // Check if validation fails
        if ($validator->fails()) {
            return response()->json(['msg' => $validator->errors(), 'statuscode' => '400', 'status' => 'error',], 422);
        }



        // Determine the action key for BaseClient
        $baseClientActionKey = $request->input('base_client.action_key');

        // Check if there's a related regional_clients array
        $regional_client_data = $data['regional_clients'];

        $base_client_data = $data['base_client'];

        // start img

        $qr_code = $request->file('qr_code');
        if (!empty($qr_code)) {
            // Generate a random string for the filename
            $randomString = Str::random(10); // Adjust the length as needed

            // Concatenate the random string with the desired file extension
            $customFilename = 'QR_' . $randomString . '.' . $qr_code->getClientOriginalExtension();
            // return $customFilename;

            // Specify the filename when storing the file in S3
            $path = $qr_code->storeAs('qr', $customFilename, 's3');

            // Optionally, you can generate a publicly accessible URL
            $url = Storage::disk('s3')->url($path);

            $base_client_data['qr_code'] = $customFilename;
        }

        $sign_img = $request->file('sign_img');
        if (!empty($sign_img)) {
            // Generate a random string for the filename
            $randomString = Str::random(10); // Adjust the length as needed

            // Concatenate the random string with the desired file extension
            $customFilename = 'SIGN_' . $randomString . '.' . $sign_img->getClientOriginalExtension();
            // return $customFilename;

            // Specify the filename when storing the file in S3
            $path = $sign_img->storeAs('sign_img', $customFilename, 's3');

            // Optionally, you can generate a publicly accessible URL
            $url = Storage::disk('s3')->url($path);

            $base_client_data['sign_img'] = $customFilename;
        }

        $logo_img = $request->file('logo_img');
        if (!empty($logo_img)) {
            // Generate a random string for the filename
            $randomString = Str::random(10); // Adjust the length as needed

            // Concatenate the random string with the desired file extension
            $customFilename = 'LOGO_' . $randomString . '.' . $logo_img->getClientOriginalExtension();
            // return $customFilename;

            // Specify the filename when storing the file in S3
            $path = $logo_img->storeAs('logo_img', $customFilename, 's3');

            // Optionally, you can generate a publicly accessible URL
            $url = Storage::disk('s3')->url($path);

            $base_client_data['logo_img'] = $customFilename;
        }

        // end img

        unset($base_client_data['action_key']);
        // return $data;
        $details = Auth::user();
        $base_client_data['saved_by_name'] = $details->name;
        $base_client_data['saved_by_id'] = $details->id;
        $base_client_data['updated_by_name'] = "";
        $base_client_data['updated_by_id'] = "";
        // Perform CRUD operations based on the action key for BaseClient
        switch ($baseClientActionKey) {
            case 'create':
                // Check if the PAN number already exists
                $existingBaseClient = BaseClient::where('pan_no', $base_client_data['pan_no'])->first();

                if ($existingBaseClient) {
                    // PAN number already exists, return an error response
                    return response()->json(['msg' => 'PAN number already exists in the database', 'status' => 'error', 'statuscode' => '400',  'data' => $existingBaseClient], 409);
                }

                // Create a new BaseClient instance with the validated data
                $baseClient = BaseClient::create($base_client_data);

                // Process each regional client
                foreach ($regional_client_data as $regionalClientData) {
                    $regionalClientData['base_client_id'] = $baseClient->id;
                    unset($regionalClientData['action_key']);
                    $regionalClientData['saved_by_name'] = $details->name;
                    $regionalClientData['saved_by_id'] = $details->id;
                    $regionalClientData['updated_by_name'] = "";
                    $regionalClientData['updated_by_id'] = "";


                    // Create a new RegionalClient instance with the validated data
                    $existingRegionalClient = RegionalClient::where('gst_no', $regionalClientData['gst_no'])->first();

                    if ($existingRegionalClient) {
                        // GST number already exists, return an error response
                        return response()->json(['msg' => 'GST number already exists in the database', 'status' => 'error', 'data' => $existingRegionalClient], 409);
                    }
                    $regionalClientData['regional_client_name'] = $base_client_data['client_name'] . '-' . $regionalClientData['state'];
                    $regionalClient = new RegionalClient($regionalClientData);
                    $baseClient->regionalClients()->save($regionalClient);
                }

                $base_client_datas=BaseClient::with('regionalClients')->where('id', $baseClient->id)->get();
                return response()->json(['msg' => 'Base client and regional clients information stored successfully', 'status' => 'success', 'statuscode' => '200', 'data' => $base_client_datas], 201);


            case 'no_change':
                // Retrieve the client ID from the request
                $clientId = $request->input('base_client.id');

                // Find the BaseClient record
                $baseClient = BaseClient::find($clientId);

                // Check if the record exists
                if (!$baseClient) {
                    return response()->json(['msg' => 'Client not found', 'statuscode' => '200', 'status' => 'success',  'data' => null], 404);
                }


                // Process each regional client for update, create, or delete
                foreach ($regional_client_data as $regionalClientData) {

                    // $regionalClient = RegionalClient::where('base_client_id', $clientId)->get();

                    // Validate and update the RegionalClient record based on the action key
                    switch ($regionalClientData['action_key']) {
                        case 'create':
                            $regionalClientData['base_client_id'] = $clientId;
                            $regionalClientData['saved_by_name'] = $details->name;
                            $regionalClientData['saved_by_id'] = $details->id;
                            $regionalClientData['updated_by_name'] = "";
                            $regionalClientData['updated_by_id'] = "";
                            $regionalClientData['regional_client_name'] = $base_client_data['client_name'] . '-' . $regionalClientData['state'];

                            unset($regionalClientData['action_key']);
                            RegionalClient::create($regionalClientData);
                            break;
                        case 'update':
                            $regionalClientData['updated_by_name'] = $details->name;
                            $regionalClientData['updated_by_id'] = $details->id;
                            unset($regionalClientData['action_key']);
                            RegionalClient::where('id', $regionalClientData['id'])->update($regionalClientData);
                            break;

                        case 'delete':
                            // Delete the RegionalClient record
                            RegionalClient::where('id', $regionalClientData['id'])->delete();
                            break;
                    }
                }
                $base_client_datas = BaseClient::with('regionalClients')->where('id', $clientId)->get();

                return response()->json(['msg' => 'Base client and regional clients information updated successfully', 'status' => 'success', 'data' => $base_client_datas], 200);

            case 'update':
                // Find the base client by ID
                $baseClient = BaseClient::find($base_client_data['id']);

                if (!$baseClient) {
                    // Handle the case where the base client does not exist
                    return response()->json(['msg' => 'Base client not found', 'status' => 'error', 'statuscode' => '404']);
                }

                // start img

                $qr_code = $request->file('qr_code');
                if (!empty($qr_code)) {
                    // Generate a random string for the filename
                    $randomString = Str::random(10); // Adjust the length as needed

                    // Concatenate the random string with the desired file extension
                    $customFilename = 'QR_' . $randomString . '.' . $qr_code->getClientOriginalExtension();
                    // return $customFilename;

                    // Specify the filename when storing the file in S3
                    $path = $qr_code->storeAs('qr', $customFilename, 's3');

                    // Optionally, you can generate a publicly accessible URL
                    $url = Storage::disk('s3')->url($path);

                    $base_client_data['qr_code'] = $customFilename;
                }

                $sign_img = $request->file('sign_img');
                if (!empty($sign_img)) {
                    // Generate a random string for the filename
                    $randomString = Str::random(10); // Adjust the length as needed

                    // Concatenate the random string with the desired file extension
                    $customFilename = 'SIGN_' . $randomString . '.' . $sign_img->getClientOriginalExtension();
                    // return $customFilename;

                    // Specify the filename when storing the file in S3
                    $path = $sign_img->storeAs('sign_img', $customFilename, 's3');

                    // Optionally, you can generate a publicly accessible URL
                    $url = Storage::disk('s3')->url($path);

                    $base_client_data['sign_img'] = $customFilename;
                }

                $logo_img = $request->file('logo_img');
                if (!empty($logo_img)) {
                    // Generate a random string for the filename
                    $randomString = Str::random(10); // Adjust the length as needed

                    // Concatenate the random string with the desired file extension
                    $customFilename = 'LOGO_' . $randomString . '.' . $logo_img->getClientOriginalExtension();
                    // return $customFilename;

                    // Specify the filename when storing the file in S3
                    $path = $logo_img->storeAs('logo_img', $customFilename, 's3');

                    // Optionally, you can generate a publicly accessible URL
                    $url = Storage::disk('s3')->url($path);

                    $base_client_data['logo_img'] = $customFilename;
                }

                // end img

                // Update the base client data
                $baseClient->update($base_client_data);
                $storedBAseClientId = $baseClient->id;

                // Process each regional client for update, create, or delete
                foreach ($regional_client_data as $regionalClientData) {

                    // $regionalClient = RegionalClient::where('base_client_id', $clientId)->get();

                    // Validate and update the RegionalClient record based on the action key
                    switch ($regionalClientData['action_key']) {
                        case 'create':
                            $regionalClientData['base_client_id'] = $storedBAseClientId;
                            $regionalClientData['saved_by_name'] = $details->name;
                            $regionalClientData['saved_by_id'] = $details->id;
                            $regionalClientData['updated_by_name'] = "";
                            $regionalClientData['updated_by_id'] = "";
                            $regionalClientData['regional_client_name'] = $base_client_data['client_name'] . '-' . $regionalClientData['state'];

                            unset($regionalClientData['action_key']);
                            RegionalClient::create($regionalClientData);
                            break;
                        case 'update':
                            $regionalClientData['updated_by_name'] = $details->name;
                            $regionalClientData['updated_by_id'] = $details->id;
                            unset($regionalClientData['action_key']);
                            RegionalClient::where('id', $regionalClientData['id'])->update($regionalClientData);
                            break;

                        case 'delete':
                            // Delete the RegionalClient record
                            RegionalClient::where('id', $regionalClientData['id'])->delete();
                            break;
                    }
                }
                $base_client_datas = BaseClient::with('regionalClients')->where('id', $storedBAseClientId)->get();


                return response()->json(['msg' => 'Base client and regional clients information updated successfully', 'status' => 'success', 'data' => $base_client_datas], 200);

            case 'delete':
                // Find the base client by ID
                $baseClient = BaseClient::find($base_client_data['id']);

                if (!$baseClient) {
                    // Handle the case where the base client does not exist
                    return response()->json(['msg' => 'Base client not found', 'status' => 'error', 'statuscode' => '404']);
                }

                // Delete all associated regional clients
                $baseClient->regionalClients()->delete();

                // Delete the base client
                $baseClient->delete();

                return response()->json(['msg' => 'Base client and associated regional clients deleted successfully', 'status' => 'success', 'statuscode' => '200']);

            default:
                return response()->json(['msg' => 'Invalid action_key for BaseClient', 'data' => null], 400);
        }
    }

    public function get_all_clients_list()
    {
        $base_client_datas = BaseClient::with('regionalClients')->get();
        if (!$base_client_datas->isEmpty()) {
            return [
                'data' => $base_client_datas,
                'statuscode' => '200',
                'msg' => 'CLients list fetched successfully.'
            ];
        } else {
            return [
                'status' => 'error',
                'statuscode' => '200',
                'msg' => 'Clients not found.'
            ];
        }
        
    }


    // public function submit_client_details(Request $request)
    // {
    //     $data=$request->all();
    //     return $data;
    //     // Validate the incoming request data
    //     $validatedData = $request->validate([
    //         'client_name' => 'required|string|max:255',
    //         'pan_no' => 'nullable|string|max:255',
    //         'cin' => 'nullable|string|max:255',
    //         'registration_address' => 'nullable|string',
    //         'account_no' => 'nullable|string|max:255',
    //         'ifsc' => 'nullable|string|max:255',
    //         'bank_name' => 'nullable|string|max:255',
    //         'branch_name' => 'nullable|string|max:255',
    //         'upi_id' => 'nullable|string|max:255',
    //         'gst_nature' => 'nullable|string|max:255',
    //         'signature_name' => 'nullable|string|max:255',
    //         'qr_code' => 'nullable|string',
    //         'sign_img' => 'nullable|string|max:255',
    //         'logo_img' => 'nullable|string|max:255',
    //         'phone_number' => 'nullable|string|max:255',
    //     ]);

    //     // Create a new BaseClient instance with the validated data
    //     $baseClient = BaseClient::create($validatedData);

    //     // You can add additional logic or redirection here if needed

    //     return response()->json(['message' => 'Base client information stored successfully', 'data' => $baseClient], 201);
    // }
}
