<?php

namespace App\Http\Controllers;

use App\Events\RealtimeMessage;
use App\Exports\PodExport;
use App\Models\AppMedia;
use App\Models\BaseClient;
use App\Models\Battery;
use App\Models\BranchAddress;
use App\Models\Consignee;
use App\Models\Consigner;
use App\Models\ConsignmentItem;
use App\Models\ConsignmentNote;
use App\Models\ConsignmentSubItem;
use App\Models\Crop;
use App\Models\CropPriceScheme;
use App\Models\Driver;
use App\Models\Farm;
use App\Models\Feedback;
use App\Models\ItemMaster;
use App\Models\Job;
use App\Models\Location;
use App\Models\OrderFarm;
use App\Models\PaymentSettlement;
use App\Models\SubventionSettlement;
use App\Models\RegionalClient;
use App\Models\Role;
use App\Models\TransactionSheet;
use App\Models\User;
use App\Models\Vehicle;
use App\Models\VehicleType;
use App\Models\Zone;
use Auth;
use Carbon\Carbon;
use Config;
use DateTime;
use DB;
use Helper;
use Illuminate\Http\Request;
use LynX39\LaraPdfMerger\Facades\PdfMerger;
use Maatwebsite\Excel\Facades\Excel;
use QrCode;
use Response;
use Session;
use Storage;
use URL;
use Validator;

class ConsignmentController extends Controller
{
    public $prefix;
    public $title;
    public $segment;

    public function __construct()
    {
        $this->title = "Consignments";
        $this->segment = \Request::segment(2);
        $this->apikey = \Config::get('keys.api');
        $this->sms_link = \Config::get('sms_api.req_sms');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $this->prefix = request()->route()->getPrefix();
        $peritem = Config::get('variable.PER_PAGE');
        $query = ConsignmentNote::query();

        if ($request->ajax()) {
            if (isset($request->resetfilter)) {
                Session::forget('peritem');
                $url = URL::to($this->prefix . '/' . $this->segment);
                return response()->json(['success' => true, 'redirect_url' => $url]);
            }
            if (isset($request->updatestatus)) {
                ConsignmentNote::where('id', $request->id)->update(['status' => $request->status, 'reason_to_cancel' => $request->reason_to_cancel, 'delivery_status' => 'Cancel']);
                ConsignmentItem::where('consignment_id', $request->id)->update(['status' => $request->status]);

                $url = $this->prefix . '/consignments';
                $response['success'] = true;
                $response['success_message'] = "Consignment updated successfully";
                $response['error'] = false;
                $response['page'] = 'consignment-updateupdate';
                $response['redirect_url'] = $url;

                return response()->json($response);
            }

            $authuser = Auth::user();
            $role_id = Role::where('id', '=', $authuser->role_id)->first();
            $baseclient = explode(',', $authuser->baseclient_id);
            $regclient = explode(',', $authuser->regionalclient_id);
            $cc = explode(',', $authuser->branch_id);

            $query = $query->whereNotIn('status', [5, 7])->with('ConsignmentItems', 'ConsignerDetail', 'ConsigneeDetail', 'VehicleDetail', 'DriverDetail', 'JobDetail', 'fallIn');

            if ($authuser->role_id == 1) {
                $query;
            } elseif ($authuser->role_id == 4) {
                $query = $query->whereIn('regclient_id', $regclient);
            } elseif ($authuser->role_id == 7) {
                $query = $query->where('user_id', $authuser->id);
            } else {
                $query = $query->whereIn('branch_id', $cc);
                // ->orWhere(function ($query) use ($cc){
                //     $query->whereIn('fall_in', $cc);
                // });

                // if(!empty('to_branch_id')){
                //     $query = $query->whereIn('to_branch_id', $cc);
                // }else{
                // $query = $query->whereIn('branch_id', $cc);
                // }
            }

            if (!empty($request->search)) {
                $search = $request->search;
                $searchT = str_replace("'", "", $search);
                $query->where(function ($query) use ($search, $searchT) {
                    $query->where('id', 'like', '%' . $search . '%')
                        ->orWhereHas('ConsignerDetail.GetRegClient', function ($regclientquery) use ($search) {
                            $regclientquery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('ConsignerDetail', function ($query) use ($search, $searchT) {
                            $query->where(function ($cnrquery) use ($search, $searchT) {
                                $cnrquery->where('nick_name', 'like', '%' . $search . '%');
                            });
                        })
                        ->orWhereHas('ConsigneeDetail', function ($query) use ($search, $searchT) {
                            $query->where(function ($cneequery) use ($search, $searchT) {
                                $cneequery->where('nick_name', 'like', '%' . $search . '%');
                            });
                        });
                });
                // ->orWhereHas('ConsignmentItem',function( $query ) use($search,$searchT){
                //     $query->where(function ($invcquery)use($search,$searchT) {
                //         $invcquery->where('invoice_no', 'like', '%' . $search . '%');
                //     });
                // });

                // });
            }

            if ($request->peritem) {
                Session::put('peritem', $request->peritem);
            }

            $peritem = Session::get('peritem');
            if (!empty($peritem)) {
                $peritem = $peritem;
            } else {
                $peritem = Config::get('variable.PER_PAGE');
            }

            $consignments = $query->orderBy('id', 'DESC')->paginate($peritem);
            $consignments = $consignments->appends($request->query());

            $html = view('consignments.consignment-list-ajax', ['prefix' => $this->prefix, 'consignments' => $consignments, 'peritem' => $peritem])->render();

            return response()->json(['html' => $html]);
        }

        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $baseclient = explode(',', $authuser->baseclient_id);
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);

        $query = $query->whereNotIn('status', [5, 7])->with('ConsignmentItems', 'ConsignerDetail', 'ConsigneeDetail', 'VehicleDetail', 'DriverDetail', 'JobDetail', 'fallIn', 'OrderactivityDetails');

        if ($authuser->role_id == 1) {
            $query;
        } elseif ($authuser->role_id == 4) {
            $query = $query->whereIn('regclient_id', $regclient);
        } elseif ($authuser->role_id == 7) {
            $query = $query->where('user_id', $authuser->id);
        } else {

            $query = $query;
            // $query = $query->whereIn('branch_id', $cc)->orWhereIn('fall_in', $cc);
        }
        $consignments = $query->orderBy('id', 'DESC')->paginate($peritem);
        $consignments = $consignments->appends($request->query());

        return view('consignments.consignment-list', ['consignments' => $consignments, 'peritem' => $peritem, 'prefix' => $this->prefix, 'segment' => $this->segment]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->prefix = request()->route()->getPrefix();
        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);

        if ($authuser->role_id == 2 || $authuser->role_id == 3) {
            if ($authuser->role_id == $role_id->id) {
                $consigners = Consigner::select('id', 'nick_name')->whereIn('branch_id', $cc)->get();
            } else {
                $consigners = Consigner::select('id', 'nick_name')->get();
            }
        } else if ($authuser->role_id != 2 || $authuser->role_id != 3) {
            if ($authuser->role_id != 1) {
                $consigners = Consigner::select('id', 'nick_name')->whereIn('regionalclient_id', $regclient)->get();
            } else {
                $consigners = Consigner::select('id', 'nick_name')->get();
            }
        } else {
            $consigners = Consigner::select('id', 'nick_name')->get();
        }

        $getconsignment = Location::select('id', 'name', 'consignment_no')->whereIn('id', $cc)->latest('id')->first();
        if (!empty($getconsignment->consignment_no)) {
            $con_series = $getconsignment->consignment_no;
        } else {
            $con_series = '';
        }

        $cn = ConsignmentNote::select('id', 'consignment_no', 'branch_id')->whereIn('branch_id', $cc)->latest('id')->first();
        if ($cn) {
            if (!empty($cn->consignment_no)) {
                $cc = explode('-', $cn->consignment_no);
                $getconsignmentno = @$cc[1] + 1;
                $consignmentno = $cc[0] . '-' . $getconsignmentno;
            } else {
                $consignmentno = $con_series . '-1';
            }
        } else {
            $consignmentno = $con_series . '-1';
        }

        if (empty($consignmentno)) {
            $consignmentno = "";
        }
        $vehicles = Vehicle::where('status', '1')->select('id', 'regn_no')->get();
        $drivers = Driver::where('status', '1')->select('id', 'name', 'phone')->get();
        $vehicletypes = VehicleType::where('status', '1')->select('id', 'name')->get();
        $itemlists = ItemMaster::where('status', '1')->get();

        /////////////////////////////Bill to regional clients //////////////////////////

        if ($authuser->role_id == 2 || $authuser->role_id == 3) {
            $branch = $authuser->branch_id;
            $branch_loc = explode(',', $branch);
            $regionalclient = RegionalClient::whereIn('location_id', $branch_loc)->select('id', 'name')->get();
        } elseif ($authuser->role_id == 4) {
            $reg = $authuser->regionalclient_id;
            $regional = explode(',', $reg);
            $regionalclient = RegionalClient::whereIn('id', $regional)->select('id', 'name')->get();
        } else {
            $regionalclient = RegionalClient::select('id', 'name')->get();
        }

        return view('consignments.create-consignment', ['prefix' => $this->prefix, 'consigners' => $consigners, 'vehicles' => $vehicles, 'vehicletypes' => $vehicletypes, 'consignmentno' => $consignmentno, 'drivers' => $drivers, 'regionalclient' => $regionalclient, 'itemlists' => $itemlists]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $this->prefix = request()->route()->getPrefix();
            $rules = array(
                'consigner_id' => 'required',
                'consignee_id' => 'required',
                'ship_to_id' => 'required',
            );
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $errors = $validator->errors();
                $response['success'] = false;
                $response['validation'] = false;
                $response['formErrors'] = true;
                $response['errors'] = $errors;
                return response()->json($response);
            }

            $authuser = Auth::user();
            $cc = explode(',', $authuser->branch_id);

            if (empty($request->vehicle_id)) {
                $status = '2';
            } else {
                $status = '1';
            }
            $consignmentsave['regclient_id'] = $request->regclient_id;
            $consignmentsave['consigner_id'] = $request->consigner_id;
            $consignmentsave['consignee_id'] = $request->consignee_id;
            $consignmentsave['ship_to_id'] = $request->ship_to_id;
            $consignmentsave['is_salereturn'] = $request->is_salereturn;
            // $consignmentsave['consignment_no'] = $consignmentno;
            $consignmentsave['consignment_date'] = $request->consignment_date;
            $consignmentsave['payment_type'] = $request->payment_type;
            $consignmentsave['description'] = $request->description;
            $consignmentsave['packing_type'] = $request->packing_type;
            $consignmentsave['dispatch'] = $request->dispatch;
            $consignmentsave['total_quantity'] = $request->total_quantity;
            $consignmentsave['total_weight'] = $request->total_weight;
            $consignmentsave['total_gross_weight'] = $request->total_gross_weight;
            //$consignmentsave['total_freight'] = $request->total_freight;
            $consignmentsave['transporter_name'] = $request->transporter_name;
            $consignmentsave['vehicle_type'] = $request->vehicle_type;
            $consignmentsave['purchase_price'] = $request->purchase_price;
            $consignmentsave['user_id'] = $authuser->id;
            $consignmentsave['vehicle_id'] = $request->vehicle_id;
            $consignmentsave['driver_id'] = $request->driver_id;
            $consignmentsave['branch_id'] = $authuser->branch_id;
            $consignmentsave['edd'] = $request->edd;
            $consignmentsave['status'] = $status;
            if (!empty($request->vehicle_id)) {
                $consignmentsave['delivery_status'] = "Started";
            } else {
                $consignmentsave['delivery_status'] = "Unassigned";
            }

            $saveconsignment = ConsignmentNote::create($consignmentsave);
            $consignment_id = $saveconsignment->id;
            //===================== Create DRS in LR ================================= //

            if (!empty($request->vehicle_id)) {
                $consignmentdrs = DB::table('consignment_notes')->select('consignment_notes.*', 'consigners.nick_name as consigner_name', 'consignees.nick_name as consignee_name', 'consignees.city as city', 'consignees.postal_code as pincode', 'vehicles.regn_no as regn_no', 'drivers.name as driver_name', 'drivers.phone as driver_phone')
                    ->join('consigners', 'consigners.id', '=', 'consignment_notes.consigner_id')
                    ->join('consignees', 'consignees.id', '=', 'consignment_notes.consignee_id')
                    ->leftjoin('vehicles', 'vehicles.id', '=', 'consignment_notes.vehicle_id')
                    ->leftjoin('drivers', 'drivers.id', '=', 'consignment_notes.driver_id')
                    ->where('consignment_notes.id', $consignment_id)
                    ->first(['consignees.city']);
                $simplyfy = json_decode(json_encode($consignmentdrs), true);
                //echo'<pre>'; print_r($simplyfy); die;

                $no_of_digit = 5;
                $drs = DB::table('transaction_sheets')->select('drs_no')->latest('drs_no')->first();
                $drs_no = json_decode(json_encode($drs), true);
                if (empty($drs_no) || $drs_no == null) {
                    $drs_no['drs_no'] = 0;
                }
                $number = $drs_no['drs_no'] + 1;
                $drs_no = str_pad($number, $no_of_digit, "0", STR_PAD_LEFT);

                $transaction = DB::table('transaction_sheets')->insert(['drs_no' => $drs_no, 'consignment_no' => $simplyfy['id'], 'consignee_id' => $simplyfy['consignee_name'], 'consignment_date' => $simplyfy['consignment_date'], 'branch_id' => $authuser->branch_id, 'city' => $simplyfy['city'], 'pincode' => $simplyfy['pincode'], 'total_quantity' => $simplyfy['total_quantity'], 'total_weight' => $simplyfy['total_weight'], 'vehicle_no' => $simplyfy['regn_no'], 'driver_name' => $simplyfy['driver_name'], 'driver_no' => $simplyfy['driver_phone'], 'order_no' => '1', 'delivery_status' => 'Assigned', 'status' => '1']);
            }
            //===========================End drs lr ================================= //
            if ($saveconsignment) {

                /******* PUSH LR to Shadow if vehicle available & Driver has team & fleet ID   ********/
                $vn = $consignmentsave['vehicle_id'];
                $lid = $saveconsignment->id;
                $lrdata = DB::table('consignment_notes')->select('consignment_notes.*', 'consigners.nick_name as consigner_id', 'consignees.nick_name as consignee_name', 'consignees.phone as phone', 'consignees.email as email', 'vehicles.regn_no as vehicle_id', 'consignees.city as city', 'consignees.postal_code as pincode', 'drivers.name as driver_id', 'drivers.phone as driver_phone', 'drivers.team_id as team_id', 'drivers.fleet_id as fleet_id')
                    ->join('consigners', 'consigners.id', '=', 'consignment_notes.consigner_id')
                    ->join('consignees', 'consignees.id', '=', 'consignment_notes.consignee_id')
                    ->join('vehicles', 'vehicles.id', '=', 'consignment_notes.vehicle_id')
                    ->join('drivers', 'drivers.id', '=', 'consignment_notes.driver_id')
                    ->where('consignment_notes.id', $lid)
                    ->get();
                $simplyfy = json_decode(json_encode($lrdata), true);
                //echo "<pre>";print_r($simplyfy);die;
                //Send Data to API

                if (($request->edd) >= $request->consignment_date) {
                    if (!empty($vn) && !empty($simplyfy[0]['team_id']) && !empty($simplyfy[0]['fleet_id'])) {
                        $createTask = $this->createTookanTasks($simplyfy);
                        $json = json_decode($createTask[0], true);
                        $job_id = $json['data']['job_id'];
                        $tracking_link = $json['data']['tracking_link'];
                        $update = DB::table('consignment_notes')->where('id', $lid)->update(['job_id' => $job_id, 'tracking_link' => $tracking_link]);
                    }
                }
                // insert consignment items
                if (!empty($request->data)) {
                    $get_data = $request->data;
                    foreach ($get_data as $key => $save_data) {
                        $save_data['consignment_id'] = $saveconsignment->id;
                        $save_data['status'] = 1;
                        $saveconsignmentitems = ConsignmentItem::create($save_data);
                    }
                }
                $url = $this->prefix . '/consignments';
                $response['success'] = true;
                $response['success_message'] = "Consignment Added successfully";
                $response['error'] = false;
                // $response['resetform'] = true;
                $response['page'] = 'create-consignment';
                $response['redirect_url'] = $url;
            } else {
                $response['success'] = false;
                $response['error_message'] = "Can not created consignment please try again";
                $response['error'] = true;
            }
            DB::commit();
        } catch (Exception $e) {
            $response['error'] = false;
            $response['error_message'] = $e;
            $response['success'] = false;
            $response['redirect_url'] = $url;
        }
        return response()->json($response);
    }

    public function storeLRItem(Request $request)
    {
        // echo '<pre>';
        // print_r($request->all());die;
        try {
            DB::beginTransaction();

            $this->prefix = request()->route()->getPrefix();
            $rules = array(
                'consigner_id' => 'required',
                'consignee_id' => 'required',
                'ship_to_id' => 'required',
            );
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $errors = $validator->errors();
                $response['success'] = false;
                $response['validation'] = false;
                $response['formErrors'] = true;
                $response['errors'] = $errors;
                return response()->json($response);
            }

            $authuser = Auth::user();
            $cc = explode(',', $authuser->branch_id);

            if (empty($request->vehicle_id)) {
                $status = '2';
            } else {
                $status = '1';
            }

            $getconsignment = Location::select('id', 'name', 'consignment_no')->whereIn('id', $cc)->latest('id')->first();
            if (!empty($getconsignment->consignment_no)) {
                $con_series = $getconsignment->consignment_no;
            } else {
                $con_series = '';
            }
            // $con_series = $getconsignment->consignment_no;
            $cn = ConsignmentNote::select('id', 'consignment_no', 'branch_id')->whereIn('branch_id', $cc)->latest('id')->first();
            if ($cn) {
                if (!empty($cn->consignment_no)) {
                    $cc = explode('-', $cn->consignment_no);
                    $getconsignmentno = @$cc[1] + 1;
                    $consignmentno = $cc[0] . '-' . $getconsignmentno;
                } else {
                    $consignmentno = $con_series . '-1';
                }
            } else {
                $consignmentno = $con_series . '-1';
            }
            $consignmentsave['regclient_id'] = $request->regclient_id;
            $consignmentsave['consigner_id'] = $request->consigner_id;
            $consignmentsave['consignee_id'] = $request->consignee_id;
            $consignmentsave['ship_to_id'] = $request->ship_to_id;
            $consignmentsave['is_salereturn'] = $request->is_salereturn;
            $consignmentsave['consignment_no'] = $consignmentno;
            $consignmentsave['consignment_date'] = $request->consignment_date;
            $consignmentsave['payment_type'] = $request->payment_type;
            $consignmentsave['freight'] = $request->freight;
            $consignmentsave['description'] = $request->description;
            $consignmentsave['packing_type'] = $request->packing_type;
            $consignmentsave['dispatch'] = $request->dispatch;
            $consignmentsave['total_quantity'] = $request->total_quantity;
            $consignmentsave['total_weight'] = $request->total_weight;
            $consignmentsave['total_gross_weight'] = $request->total_gross_weight;
            // $consignmentsave['total_freight'] = $request->total_freight;
            $consignmentsave['transporter_name'] = $request->transporter_name;
            $consignmentsave['vehicle_type'] = $request->vehicle_type;
            $consignmentsave['purchase_price'] = $request->purchase_price;
            $consignmentsave['user_id'] = $authuser->id;
            $consignmentsave['vehicle_id'] = $request->vehicle_id;
            $consignmentsave['driver_id'] = $request->driver_id;
            $consignmentsave['branch_id'] = $authuser->branch_id;
            $consignmentsave['edd'] = $request->edd;
            $consignmentsave['status'] = $status;
            if (!empty($request->vehicle_id)) {
                $consignmentsave['delivery_status'] = "Started";
            } else {
                $consignmentsave['delivery_status'] = "Unassigned";
            }

            $saveconsignment = ConsignmentNote::create($consignmentsave);
            $consignment_id = $saveconsignment->id;
            //===================== Create DRS in LR ================================= //

            if (!empty($request->vehicle_id)) {
                $consignmentdrs = DB::table('consignment_notes')->select('consignment_notes.*', 'consigners.nick_name as consigner_name', 'consignees.nick_name as consignee_name', 'consignees.city as city', 'consignees.postal_code as pincode', 'vehicles.regn_no as regn_no', 'drivers.name as driver_name', 'drivers.phone as driver_phone')
                    ->join('consigners', 'consigners.id', '=', 'consignment_notes.consigner_id')
                    ->join('consignees', 'consignees.id', '=', 'consignment_notes.consignee_id')
                    ->leftjoin('vehicles', 'vehicles.id', '=', 'consignment_notes.vehicle_id')
                    ->leftjoin('drivers', 'drivers.id', '=', 'consignment_notes.driver_id')
                    ->where('consignment_notes.id', $consignment_id)
                    ->first(['consignees.city']);
                $simplyfy = json_decode(json_encode($consignmentdrs), true);
                //echo'<pre>'; print_r($simplyfy); die;

                $no_of_digit = 5;
                $drs = DB::table('transaction_sheets')->select('drs_no')->latest('drs_no')->first();
                $drs_no = json_decode(json_encode($drs), true);
                if (empty($drs_no) || $drs_no == null) {
                    $drs_no['drs_no'] = 0;
                }
                $number = $drs_no['drs_no'] + 1;
                $drs_no = str_pad($number, $no_of_digit, "0", STR_PAD_LEFT);

                $transaction = DB::table('transaction_sheets')->insert(['drs_no' => $drs_no, 'consignment_no' => $simplyfy['id'], 'consignee_id' => $simplyfy['consignee_name'], 'consignment_date' => $simplyfy['consignment_date'], 'branch_id' => $authuser->branch_id, 'city' => $simplyfy['city'], 'pincode' => $simplyfy['pincode'], 'total_quantity' => $simplyfy['total_quantity'], 'total_weight' => $simplyfy['total_weight'], 'vehicle_no' => $simplyfy['regn_no'], 'driver_name' => $simplyfy['driver_name'], 'driver_no' => $simplyfy['driver_phone'], 'order_no' => '1', 'delivery_status' => 'Assigned', 'status' => '1']);
            }
            //===========================End drs lr ================================= //
            if ($saveconsignment) {

                /******* PUSH LR to Shadow if vehicle available & Driver has team & fleet ID   ********/
                $vn = $consignmentsave['vehicle_id'];
                $lid = $saveconsignment->id;
                $lrdata = DB::table('consignment_notes')->select('consignment_notes.*', 'consigners.nick_name as consigner_id', 'consignees.nick_name as consignee_name', 'consignees.phone as phone', 'consignees.email as email', 'vehicles.regn_no as vehicle_id', 'consignees.city as city', 'consignees.postal_code as pincode', 'drivers.name as driver_id', 'drivers.phone as driver_phone', 'drivers.team_id as team_id', 'drivers.fleet_id as fleet_id')
                    ->join('consigners', 'consigners.id', '=', 'consignment_notes.consigner_id')
                    ->join('consignees', 'consignees.id', '=', 'consignment_notes.consignee_id')
                    ->join('vehicles', 'vehicles.id', '=', 'consignment_notes.vehicle_id')
                    ->join('drivers', 'drivers.id', '=', 'consignment_notes.driver_id')
                    ->where('consignment_notes.id', $lid)
                    ->get();
                $simplyfy = json_decode(json_encode($lrdata), true);
                //echo "<pre>";print_r($simplyfy);die;
                //Send Data to API

                if (($request->edd) >= $request->consignment_date) {
                    if (!empty($vn) && !empty($simplyfy[0]['team_id']) && !empty($simplyfy[0]['fleet_id'])) {
                        $createTask = $this->createTookanTasks($simplyfy);
                        $json = json_decode($createTask[0], true);
                        $job_id = $json['data']['job_id'];
                        $tracking_link = $json['data']['tracking_link'];
                        $update = DB::table('consignment_notes')->where('id', $lid)->update(['job_id' => $job_id, 'tracking_link' => $tracking_link, 'lr_mode' => 1]);
                    }
                }
                // insert consignment items
                if (!empty($request->data)) {
                    $get_data = $request->data;
                    foreach ($get_data as $key => $save_data) {
                        $save_data['consignment_id'] = $saveconsignment->id;
                        $save_data['status'] = 1;
                        $saveconsignmentitems = ConsignmentItem::create($save_data);

                        if ($saveconsignmentitems) {
                            // dd($save_data['item_data']);
                            if (!empty($save_data['item_data'])) {
                                $qty_array = array();
                                $netwt_array = array();
                                $grosswt_array = array();
                                $chargewt_array = array();
                                foreach ($save_data['item_data'] as $key => $save_itemdata) {
                                    // echo "<pre>"; print_r($save_itemdata); die;
                                    $qty_array[] = $save_itemdata['quantity'];
                                    $netwt_array[] = $save_itemdata['net_weight'];
                                    $grosswt_array[] = $save_itemdata['gross_weight'];
                                    $chargewt_array[] = $save_itemdata['chargeable_weight'];

                                    $save_itemdata['conitem_id'] = $saveconsignmentitems->id;
                                    $save_itemdata['status'] = 1;
                                    $savesubitems = ConsignmentSubItem::create($save_itemdata);
                                }
                                $quantity_sum = array_sum($qty_array);
                                $netwt_sum = array_sum($netwt_array);
                                $grosswt_sum = array_sum($grosswt_array);
                                $chargewt_sum = array_sum($chargewt_array);

                                ConsignmentItem::where('id', $savesubitems->conitem_id)->update(['quantity' => $quantity_sum, 'weight' => $netwt_sum, 'gross_weight' => $grosswt_sum, 'chargeable_weight' => $chargewt_sum]);
                            }
                        }
                    }
                }
                $url = $this->prefix . '/consignments';
                $response['success'] = true;
                $response['success_message'] = "Consignment Added successfully";
                $response['error'] = false;
                // $response['resetform'] = true;
                $response['page'] = 'create-consignment';
                $response['redirect_url'] = $url;
            } else {
                $response['success'] = false;
                $response['error_message'] = "Can not created consignment please try again";
                $response['error'] = true;
            }
            DB::commit();
        } catch (Exception $e) {
            $response['error'] = false;
            $response['error_message'] = $e;
            $response['success'] = false;
            $response['redirect_url'] = $url;
        }
        return response()->json($response);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($consignment)
    {
        $this->prefix = request()->route()->getPrefix();
        $query = ConsignmentNote::query();
        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);
        if ($authuser->role_id != 1) {
            if ($authuser->role_id == $role_id->id) {
                $getconsignment = $query->whereIn('branch_id', $cc)->orderby('id', 'DESC')->get();
            }
        } else {
            $getconsignment = $query->orderby('id', 'DESC')->get();
        }
        $branch_add = BranchAddress::first();
        $locations = Location::whereIn('id', $cc)->first();
        return view('consignments.view-consignment', ['prefix' => $this->prefix, 'getconsignment' => $getconsignment, 'branch_add' => $branch_add, 'locations' => $locations]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    // get consigner address on change
    public function getConsigners(Request $request)
    {
        // echo'<pre>'; print_r($request->consigner_id); die;
        $getconsigners = Consignee::select('address_line1', 'address_line2', 'address_line3', 'address_line4', 'gst_number', 'phone', 'city', 'branch_id')->where(['id' => $request->consigner_id, 'status' => '1'])->first();
        $getregclients = RegionalClient::select('id', 'is_multiple_invoice')->where('id', $request->regclient_id)->first();
        $getConsignees = Farm::where('farmer_id', $request->consigner_id)->get();
        if ($getconsigners) {
            $response['success'] = true;
            $response['success_message'] = "Consigner list fetch successfully";
            $response['error'] = false;
            $response['data'] = $getconsigners;
            $response['consignee'] = $getConsignees;
            $response['regclient'] = $getregclients;
        } else {
            $response['success'] = false;
            $response['error_message'] = "Can not fetch consigner list please try again";
            $response['error'] = true;
        }
        return response()->json($response);
    }

    //// get consioner from regional client ////
    public function getConsignersonRegional(Request $request)
    {
        $getconsigners = Consigner::select('id', 'nick_name')->where('regionalclient_id', $request->regclient_id)->get();

        // $getregclients = RegionalClient::with('PaymentTerm')->where('id', $request->regclient_id)->first();
        $query = RegionalClient::query();

        $getregclients = $query->with('PaymentTerm')->where('id', $request->regclient_id)->whereHas('PaymentTerm', function ($query) {
            $query->where('status', '=', 1);
        })->first();

        $itemlists = ItemMaster::where('status', '1')->get();

        if ($getconsigners) {
            $response['success'] = true;
            $response['success_message'] = "Consigner list fetch successfully";
            $response['error'] = false;
            $response['data'] = $getconsigners;
            $response['data_regclient'] = $getregclients;
            $response['data_items'] = $itemlists;
        } else {
            $response['success'] = false;
            $response['error_message'] = "Can not fetch consigner list please try again";
            $response['error'] = true;
        }
        return response()->json($response);
    }

    // get consigner address on change
    public function getConsignees(Request $request)
    {
        $getconsignees = Farm::where('id', $request->consignee_id)->first();

        if ($getconsignees) {
            $response['success'] = true;
            $response['success_message'] = "Consignee list fetch successfully";
            $response['error'] = false;
            $response['data'] = $getconsignees;
        } else {
            $response['success'] = false;
            $response['error_message'] = "Can not fetch consignee list please try again";
            $response['error'] = true;
        }
        return response()->json($response);
    }

    // getConsigndetails
    public function getConsigndetails(Request $request)
    {
        $cn_id = $request->id;
        $cn_details = ConsignmentNote::where('id', $cn_id)->with('ConsignmentItems', 'ConsignerDetail', 'ConsigneeDetail', 'ShiptoDetail', 'VehicleDetail', 'DriverDetail')->first();
        if ($cn_details) {
            $response['success'] = true;
            $response['success_message'] = "Consignment details fetch successfully";
            $response['error'] = false;
            $response['data'] = $cn_details;
        } else {
            $response['success'] = false;
            $response['error_message'] = "Can not fetch consignment details please try again";
            $response['error'] = true;
        }
        return response()->json($response);
    }

    // print LR for new view
    public function consignPrintview(Request $request)
    {
        $query = ConsignmentNote::query();
        $authuser = Auth::user();
        $cc = explode(',', $authuser->branch_id);
        $branch_add = BranchAddress::get();
        $locations = Location::with('GstAddress')->whereIn('id', $cc)->first();
        $cn_id = $request->id;

        $getdata = ConsignmentNote::where('id', $cn_id)->with('ConsignmentItems', 'ConsignerDetail.GetZone', 'ConsigneeDetail.GetZone', 'ShiptoDetail.GetZone', 'VehicleDetail', 'DriverDetail', 'RegClient', 'Farm', 'Crop')->first();
        $data = json_decode(json_encode($getdata), true);
        // echo '<pre>'; print_r($data); die;

        if (isset($data['consigner_detail']['legal_name'])) {
            $legal_name = '<b>' . $data['consigner_detail']['legal_name'] . '</b><br>';
        } else {
            $legal_name = '';
        }
        if (isset($data['consigner_detail']['address_line1'])) {
            $address_line1 = '' . $data['consigner_detail']['address_line1'] . '<br>';
        } else {
            $address_line1 = '';
        }
        if (isset($data['consigner_detail']['address_line2'])) {
            $address_line2 = '' . $data['consigner_detail']['address_line2'] . '<br>';
        } else {
            $address_line2 = '';
        }
        if (isset($data['consigner_detail']['address_line3'])) {
            $address_line3 = '' . $data['consigner_detail']['address_line3'] . '<br>';
        } else {
            $address_line3 = '';
        }
        if (isset($data['consigner_detail']['address_line4'])) {
            $address_line4 = '' . $data['consigner_detail']['address_line4'] . '<br><br>';
        } else {
            $address_line4 = '<br>';
        }
        if (isset($data['consigner_detail']['city'])) {
            $city = $data['consigner_detail']['city'] . ',';
        } else {
            $city = '';
        }
        if (isset($data['consigner_detail']['get_zone']['state'])) {
            $district = $data['consigner_detail']['get_zone']['state'] . ',';
        } else {
            $district = '';
        }
        if (isset($data['consigner_detail']['postal_code'])) {
            $postal_code = $data['consigner_detail']['postal_code'] . '<br>';
        } else {
            $postal_code = '';
        }
        if (isset($data['consigner_detail']['gst_number'])) {
            $gst_number = 'GST No: ' . $data['consigner_detail']['gst_number'] . '<br>';
        } else {
            $gst_number = '';
        }
        if (isset($data['consigner_detail']['phone'])) {
            $phone = 'Phone No: ' . $data['consigner_detail']['phone'] . '<br>';
        } else {
            $phone = '';
        }

        $conr_add = $legal_name . ' ' . $address_line1 . ' ' . $address_line2 . ' ' . $address_line3 . ' ' . $address_line4 . '' . $city . ' ' . $district . ' ' . $postal_code . '' . $gst_number . ' ' . $phone;

        if (isset($data['consignee_detail']['legal_name'])) {
            $nick_name = '<b>' . $data['consignee_detail']['legal_name'] . '</b><br>';
        } else {
            $nick_name = '';
        }
        if (isset($data['consignee_detail']['address_line1'])) {
            $address_line1 = '' . $data['consignee_detail']['address_line1'] . '<br>';
        } else {
            $address_line1 = '';
        }
        if (isset($data['consignee_detail']['address_line2'])) {
            $address_line2 = '' . $data['consignee_detail']['address_line2'] . '<br>';
        } else {
            $address_line2 = '';
        }
        if (isset($data['consignee_detail']['address_line3'])) {
            $address_line3 = '' . $data['consignee_detail']['address_line3'] . '<br>';
        } else {
            $address_line3 = '';
        }
        if (isset($data['consignee_detail']['address_line4'])) {
            $address_line4 = '' . $data['consignee_detail']['address_line4'] . '<br><br>';
        } else {
            $address_line4 = '<br>';
        }
        if (isset($data['consignee_detail']['city'])) {
            $city = $data['consignee_detail']['city'] . ',';
        } else {
            $city = '';
        }
        if (isset($data['consignee_detail']['get_zone']['state'])) {
            $district = $data['consignee_detail']['get_zone']['state'] . ',';
        } else {
            $district = '';
        }
        if (isset($data['consignee_detail']['postal_code'])) {
            $postal_code = $data['consignee_detail']['postal_code'] . '<br>';
        } else {
            $postal_code = '';
        }

        if (isset($data['consignee_detail']['gst_number'])) {
            $gst_number = 'GST No: ' . $data['consignee_detail']['gst_number'] . '<br>';
        } else {
            $gst_number = '';
        }
        if (isset($data['consignee_detail']['phone'])) {
            $phone = 'Phone No: ' . $data['consignee_detail']['phone'] . '<br>';
        } else {
            $phone = '';
        }

        $consnee_add = $nick_name . ' ' . $address_line1 . ' ' . $address_line2 . ' ' . $address_line3 . ' ' . $address_line4 . '' . $city . ' ' . $district . ' ' . $postal_code . '' . $gst_number . ' ' . $phone;

        if (isset($data['shipto_detail']['legal_name'])) {
            $nick_name = '<b>' . $data['shipto_detail']['legal_name'] . '</b><br>';
        } else {
            $nick_name = '';
        }
        if (isset($data['shipto_detail']['address_line1'])) {
            $address_line1 = '' . $data['shipto_detail']['address_line1'] . '<br>';
        } else {
            $address_line1 = '';
        }
        if (isset($data['shipto_detail']['address_line2'])) {
            $address_line2 = '' . $data['shipto_detail']['address_line2'] . '<br>';
        } else {
            $address_line2 = '';
        }
        if (isset($data['shipto_detail']['address_line3'])) {
            $address_line3 = '' . $data['shipto_detail']['address_line3'] . '<br>';
        } else {
            $address_line3 = '';
        }
        if (isset($data['shipto_detail']['address_line4'])) {
            $address_line4 = '' . $data['shipto_detail']['address_line4'] . '<br><br>';
        } else {
            $address_line4 = '<br>';
        }
        if (isset($data['shipto_detail']['city'])) {
            $city = $data['shipto_detail']['city'] . ',';
        } else {
            $city = '';
        }
        if (isset($data['shipto_detail']['get_zone']['state'])) {
            $district = $data['shipto_detail']['get_zone']['state'] . ',';
        } else {
            $district = '';
        }
        if (isset($data['shipto_detail']['postal_code'])) {
            $postal_code = $data['shipto_detail']['postal_code'] . '<br>';
        } else {
            $postal_code = '';
        }
        if (isset($data['shipto_detail']['gst_number'])) {
            $gst_number = 'GST No: ' . $data['shipto_detail']['gst_number'] . '<br>';
        } else {
            $gst_number = '';
        }
        if (isset($data['shipto_detail']['phone'])) {
            $phone = 'Phone No: ' . $data['shipto_detail']['phone'] . '<br>';
        } else {
            $phone = '';
        }

        $shiptoadd = $nick_name . ' ' . $address_line1 . ' ' . $address_line2 . ' ' . $address_line3 . ' ' . $address_line4 . '' . $city . ' ' . $district . ' ' . $postal_code . '' . $gst_number . ' ' . $phone;

        $generate_qrcode = QrCode::size(150)->generate('' . $cn_id . '');
        $output_file = '/qr-code/img-' . time() . '.svg';
        Storage::disk('public')->put($output_file, $generate_qrcode);
        $fullpath = storage_path('app/public/' . $output_file);
        //  dd($generate_qrcode);
        $no_invoive = count($data['consignment_items']);

        if ($request->typeid == 1) {
            $adresses = '<table width="100%">
                    <tr>
                        <td style="width:50%">' . $conr_add . '</td>
                        <td style="width:50%">' . $consnee_add . '</td>
                    </tr>
                </table>';
        } else if ($request->typeid == 2) {
            $adresses = '<table width="100%">
                        <tr>
                            <td style="width:33%">' . $conr_add . '</td>
                            <td style="width:33%">' . $consnee_add . '</td>
                            <td style="width:33%">' . $shiptoadd . '</td>
                        </tr>
                    </table>';
        }

        // get branch address
        if (!empty($locations->GstAddress)) {
            $branch_address = '<span style="font-size: 14px;"><b>' . $branch_add[0]->name . ' </b></span><br />
        <b>' . $locations->GstAddress->address_line_1 . ',</b><br />
        <b>	' . $locations->GstAddress->address_line_2 . '</b><br />
        <b>GST No. : ' . $locations->GstAddress->gst_no . '</b><br />';
        } else {
            $branch_address = '<span style="font-size: 14px;"><b>' . $branch_add[0]->name . ' </b></span><br />
            <b>	Plot no: ' . $branch_add[0]->address . ',</b><br />
            <b>	' . $branch_add[0]->district . ' - ' . $branch_add[0]->postal_code . ',' . $branch_add[0]->state . '</b><br />
            <b>GST No. : ' . $branch_add[0]->gst_number . '</b><br />';
        }

        // if ($data['branch_id'] == 2 || $data['branch_id'] == 6 || $data['branch_id'] == 26) {
        //     $branch_address = '<span style="font-size: 14px;"><b>' . $branch_add[1]->name . ' </b></span><br />
        // <b>' . $branch_add[1]->address . ',</b><br />
        // <b>    ' . $branch_add[1]->district . ' - ' . $branch_add[1]->postal_code . ',' . $branch_add[1]->state . '</b><br />
        // <b>GST No. : ' . $branch_add[1]->gst_number . '</b><br />';
        // } else {
        //     $branch_address = '<span style="font-size: 14px;"><b>' . $branch_add[0]->name . ' </b></span><br />
        // <b>    Plot no: ' . $branch_add[0]->address . ',</b><br />
        // <b>    ' . $branch_add[0]->district . ' - ' . $branch_add[0]->postal_code . ',' . $branch_add[0]->state . '</b><br />
        // <b>GST No. : ' . $branch_add[0]->gst_number . '</b><br />';
        // }

        // relocate cnr cnee address check for sale to return case
        if ($data['is_salereturn'] == '1') {
            $cnradd_heading = '<div class="container">
            <div>
            <h5  style="margin-left:6px; margin-top: 0px">CONSIGNOR NAME & ADDRESS</h5><br>
            </div>
            <div style="margin-top: -11px;">
            <p  style="margin-left:6px;margin-top: -13px; font-size: 12px;">
            ' . $consnee_add . '
            </p>
            </div>';
            $cneadd_heading = '<div class="container">
            <div>
            <h5  style="margin-left:6px; margin-top: 0px">CONSIGNEE NAME & ADDRESS</h5><br>
            </div>
                <div style="margin-top: -11px;">
                <p  style="margin-left:6px;margin-top: -13px; font-size: 12px;">
                ' . $conr_add . '
            </p>
            </div>';
            $shipto_address = '';
        } else {
            $cnradd_heading = '<div class="container">
            <div>
            <h5  style="margin-left:6px; margin-top: 0px">CONSIGNOR NAME & ADDRESS</h5><br>
            </div>
            <div style="margin-top: -11px;">
            <p  style="margin-left:6px;margin-top: -13px; font-size: 12px;">
            ' . $conr_add . '
            </p>
            </div>';
            $cneadd_heading = '<div class="container">
            <div>
            <h5  style="margin-left:6px; margin-top: 0px">CONSIGNEE NAME & ADDRESS</h5><br>
            </div>
                <div style="margin-top: -11px;">
                <p  style="margin-left:6px;margin-top: -13px; font-size: 12px;">
                ' . $consnee_add . '
            </p>
            </div>';
            $shipto_address = '<td width="30%" style="vertical-align:top;>
            <div class="container">
            <div>
            <h5  style="margin-left:6px; margin-top: 0px">SHIP TO NAME & ADDRESS</h5><br>
            </div>
                <div style="margin-top: -11px;">
                <p  style="margin-left:6px;margin-top: -13px; font-size: 12px;">
              ' . $shiptoadd . '
            </p>
                </div>
            </td>';
        }

        $pay = public_path('assets/img/LOGO_Frowarders.jpg');
        $payment = public_path('assets/123.png');
        $agri = public_path('assets/img/agri.png');
        $qr = public_path('assets/qr.png');
        $corteva = public_path('assets/corteva.png');
        $bg1 = public_path('assets/b-1.jpg');
        $bg2 = public_path('assets/b-2.jpg');
        $file_link = public_path('assets/Roboto/Roboto-Regular.ttf');

        for ($i = 1; $i < 5; $i++) {
            if ($i == 1) {
                $type = 'ORIGINAL';
            } elseif ($i == 2) {
                $type = 'DUPLICATE';
            } elseif ($i == 3) {
                $type = 'TRIPLICATE';
            } elseif ($i == 4) {
                $type = 'QUADRUPLE';
            }
            if (!empty($data['consigner_detail']['get_zone']['state'])) {
                $cnr_state = $data['consigner_detail']['get_zone']['state'];
            } else {
                $cnr_state = '';
            }

            $html = '<!DOCTYPE html>
            <html>
            <head>
                <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
                <style>

                    * {
                       font-family: system-ui, sans-serif !important;
                        padding: 0;
                        margin: 0;
                        -webkit-print-color-adjust: exact !important; /* Chrome, Safari 6 – 15.3, Edge */
                        color-adjust: exact !important; /* Firefox 48 – 96 */
                        print-color-adjust: exact !important; /* Firefox 97+, Safari 15.4+ */

                    }
                    h1, h2 , h3, h4, h5, h6, p{
                       font-family: system-ui, sans-serif !important;
                    }

                    .headingTd {
                       font-family: system-ui, sans-serif !important;

                        color: #fff;
                        font-size: 32px;
                        line-height: 32px;
                        font-weight: 500;
                        padding: 14px 20px;
                        margin-top: 24px;
                        background: #fff url("' . $bg2 . '") no-repeat 96%;
                    }

                    .headingTd span {
                        font-size: 18px;
                        line-height: 18px;
                    }

                    .headerTd {
                        color: #fff;
                        width: 70%;
                        padding-left: 70px;
                        text-align: right;
                        padding-right: 20px;
                        height: 125px;
                        background: #fff url("' . $bg1 . '") no-repeat;
                    }

                    .headerTd h4 {
                        font-size: 30px;
                        margin: 0;
                        font-weight: 700;
                    }

                    .headerTd p {
                        margin: 0;
                        font-size: 18px;
                        font-weight: 600;
                    }

                    .headerTd .companyName {
                        font-family: system-ui, sans-serif !important;
                        font-size: 20px;
                        font-weight: 600;
                        margin-bottom: 1px;
                    }

                    .headerTd .companyAddress {
                        font-size: 12px;
                        width: 300px;
                        margin: 0 0 0 auto;
                        font-weight: 400;
                    }

                    .headerTd .companyContact {
                        font-size: 12px;
                        margin-top: 6px;
                        font-weight: 500;
                    }


                    .subHeading {
                        font-weight: 500;
                        font-size: 17px;
                        background: #02381d;
                        width: 220px;
                        border-radius: 18px 18px 0 0;
                        text-align: center;
                        color: #fff;
                        line-height: 28px;
                    }

                    .addressBlock {
                        border: 2px solid #02381d;
                        width: 90%;
                        border-radius: 0 12px 12px;
                        overflow: hidden;
                    }

                    .addressBlock p {
                        padding: 1rem;
                        min-height: 70px;
                    }


                    .pin, .city, .pinValue, .cityValue {
                        border-top: 2px solid #02381d;
                        font-weight: 700;
                    }

                    .pinValue {
                        width: 76px
                    }

                    .pin, .city {
                        text-align: center;
                        width: 60px;
                        color: #248f27;
                    }

                    .roundTable td {
                        padding: 8px;
                        border: none;
                    }

                    .terms {
                        font-size: 15px;
                        font-weight: 600;
                        border-bottom: 1px solid #02381d80;
                        padding: 0 1rem;
                    }


                    .orderDetailsBlock {
                        padding: 1rem;
                        border-radius: 15px;
                        margin-right: 60px;
                    }

                    .billingClientBlock {
                        margin-top: 14px;
                        border-radius: 12px;
                        padding: 16px;
                    }

                    .billingClientBlock .heading {
                        font-size: 20px;
                        font-weight: 500;
                        color: #02381d;
                        border-bottom: 2px solid #02381d;
                    }

                    .billingClientBlock .des {
                        padding-left: 1rem;
                    }

                    .paymentBlock {
                        width: 200px;
                        vertical-align: baseline;
                        text-align: center;
                    }

                    .imgText {
                        margin-bottom: 6px;
                        font-weight: 600;
                        color: #02381d;
                    }

                    .footer {
                        border-radius: 12px;
                        position: fixed;
                        bottom: 0;
                        width: 100%;
                        font-size: 11px;
                    }
                </style>
            </head>

            <body>
            <!--header-->
            <table width="100%">
                <tbody>
                <tr>
                    <td style="width: 30%">
                        <img src="' . $agri . '" style="width: 28vw"/>
                    </td>
                    <td class="headerTd">
                        <p class="companyName">' . $branch_add[0]->name . '</p>
                        <p class="companyAddress">' . $locations->GstAddress->address_line_1 . ',' . $locations->GstAddress->address_line_2 . '</p>
                        <p class="companyAddress">GST No.:  ' . $locations->GstAddress->gst_no . '</p>
                        <p class="companyContact">Phone: ' . @$locations->phone . ' &nbsp;&nbsp;&nbsp; Email:
                        ' . @$locations->email . '</p>
                    </td>
                </tr>
                <tr>
                    <td width="30%" class="headingTd"><p >Order Sheet<br/><span>Original</span></p></td>
                </tr>
                </tbody>
            </table>

            <table width="90%" style="margin: auto">
                <tbody>
                <tr>
                    <td>
                        <div class="orderDetailsBlock">
                            <p>Order No.&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;
                                <strong>' . $data['id'] . '</strong></p>
                            <p>Order Date&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp; <strong>' . date('d-m-Y', strtotime($data['consignment_date'])) . '</strong></p>
                            <p>Drone&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;:&nbsp;
                                <strong>' . @$data['vehicle_detail']['regn_no'] . '</strong></p>
                            <p>Rider&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
                                :&nbsp; <strong>' . ucwords(@$data['driver_detail']['name']) . '</strong>
                            <p>Payment Term&nbsp;&nbsp;: <strong>' . @$data['payment_type'] . '</strong>
                            </p>
                        </div>

                        <div class="billingClientBlock" style="margin-right: 70px;">
                            <p class="heading">Billing Client</p>
                            <p class="des">' . $data['reg_client']['name'] . '</p>
                        </div>
                    </td>
                    <td class="paymentBlock">
                        <p class="imgText">Payment Link</p>
                        <img src="' . $qr . '" alt="qr code" style="width: 160px;"/>
                    </td>
                </tr>
                </tbody>
            </table>

            <table style="width: 96%; margin: auto">
                <tbody>
                <!--    address-->
                <tr>
                    <td style="padding: 1rem 0">
                        <!--address-->
                        <table width="94%" style="border-collapse: collapse; margin: auto">
                            <tbody>
                            <tr>
                                <td><p class="subHeading">Farmer Home Address</p></td>
                                <td><p class="subHeading">Farmer Farm Address</p></td>
                            </tr>
                            <tr>
                                <td style="width: 50%;">
                                    <div class="addressBlock">
                                        <p>' . $data['consignee_detail']['nick_name'] . '<br>' . $data['consignee_detail']['address_line1'] . '
                                        </p>
                                        <table style="width: 100%; border-collapse: collapse">
                                            <tbody>
                                            <tr>
                                                <td class="pin"><strong>PIN :</strong></td>
                                                <td class="pinValue">' . $data['consignee_detail']['postal_code'] . '</td>
                                                <td class="city"><strong>CITY :</strong></td>
                                                <td class="cityValue">' . @$data['consignee_detail']['city'] . '</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                                <td style="width: 50%">
                                    <div class="addressBlock">
                                        <p>
                                        ' . @$data['farm']['field_area'] . ',' . @$data['farm']['address'] . '
                                        </p>
                                        <table style="width: 100%; border-collapse: collapse">
                                            <tbody>
                                            <tr>
                                                <td class="pin"><strong>PIN :</strong></td>
                                                <td class="pinValue">' . @$data['farm']['pin_code'] . '</td>
                                                <td class="city"><strong>CITY :</strong></td>
                                                <td class="cityValue">' . @$data['farm']['city'] . '</td>
                                            </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </td>
                            </tr>
                            </tbody>
                        </table>
                    </td>
                </tr>

                <!--    other info-->
                <tr>
                    <td>
                        <div class="billingClientBlock">
                            <p class="heading">Other Information</p>
                            <table width="100%">
                                <tbody>
                                <tr>
                                    <td width="50%"><p class="des">Crop : <strong>' . @$data['crop']['crop_name'] . '</strong></p></td>
                                    <td width="50%"><p class="des">Acreage : <strong>' . @$data['acreage'] . '</strong></p></td>
                                </tr>
                                </tbody>
                            </table>
                        </div>
                    </td>
                </tr>

                <tr>
                    <td style="text-align: center">
                        <img src="' . $corteva . '" height="160px"/>
                    </td>
                </tr>
                </tbody>
            </table>

            <!--    terms-->
            <div class="footer">
                <p class="terms">Terms & Conditions</p>
                <ul style="text-align: left;padding-left: 2rem;margin: 10px;">
                    <li>List One</li>
                    <li>List One</li>
                    <li>List One</li>
                    <li>List One</li>
                    <li>List One</li>
                </ul>
            </div>

            </body>
            </html>';

            $pdf = \App::make('dompdf.wrapper');
            $pdf->loadHTML($html);
            $pdf->setPaper('legal', 'portrait');
            $pdf->save(public_path() . '/consignment-pdf/congn-' . $i . '.pdf')->stream('congn-' . $i . '.pdf');
            $pdf_name[] = 'congn-' . $i . '.pdf';
        }
        $pdfMerger = PDFMerger::init();
        foreach ($pdf_name as $pdf) {
            $pdfMerger->addPDF(public_path() . '/consignment-pdf/' . $pdf);
        }
        $pdfMerger->merge();
        $pdfMerger->save("all.pdf", "browser");
        $file = new Filesystem;
        $file->cleanDirectory('pdf');
    }

    // print LR for old view
    public function consignPrintviewold(Request $request)
    {
        $query = ConsignmentNote::query();
        $authuser = Auth::user();
        $cc = explode(',', $authuser->branch_id);
        $branch_add = BranchAddress::get();
        $locations = Location::whereIn('id', $cc)->first();

        $cn_id = $request->id;
        $getdata = ConsignmentNote::where('id', $cn_id)->with('ConsignmentItems', 'ConsignerDetail', 'ConsigneeDetail', 'ShiptoDetail', 'VehicleDetail', 'DriverDetail')->first();
        $data = json_decode(json_encode($getdata), true);

        if (isset($data['consigner_detail']['legal_name'])) {
            $legal_name = '<p><b>' . $data['consigner_detail']['legal_name'] . '</b></p>';
        } else {
            $legal_name = '';
        }
        if (isset($data['consigner_detail']['address_line1'])) {
            $address_line1 = '<p>' . $data['consigner_detail']['address_line1'] . '</p>';
        } else {
            $address_line1 = '';
        }
        if (isset($data['consigner_detail']['address_line2'])) {
            $address_line2 = '<p>' . $data['consigner_detail']['address_line2'] . '</p>';
        } else {
            $address_line2 = '';
        }
        if (isset($data['consigner_detail']['address_line3'])) {
            $address_line3 = '<p>' . $data['consigner_detail']['address_line3'] . '</p>';
        } else {
            $address_line3 = '';
        }
        if (isset($data['consigner_detail']['address_line4'])) {
            $address_line4 = '<p>' . $data['consigner_detail']['address_line4'] . '</p>';
        } else {
            $address_line4 = '';
        }
        if (isset($data['consigner_detail']['city'])) {
            $city = $data['consigner_detail']['city'] . ',';
        } else {
            $city = '';
        }
        if (isset($data['consigner_detail']['get_zone']['district'])) {
            $district = @$data['consigner_detail']['get_zone']['district'] . ',';
        } else {
            $district = '';
        }
        if (isset($data['consigner_detail']['postal_code'])) {
            $postal_code = $data['consigner_detail']['postal_code'];
        } else {
            $postal_code = '';
        }
        if (isset($data['consigner_detail']['gst_number'])) {
            $gst_number = '<p>GST No: ' . $data['consigner_detail']['gst_number'] . '</p>';
        } else {
            $gst_number = '';
        }
        if (isset($data['consigner_detail']['phone'])) {
            $phone = '<p>Phone No: ' . $data['consigner_detail']['phone'] . '</p>';
        } else {
            $phone = '';
        }

        $conr_add = '<p>' . 'CONSIGNOR NAME & ADDRESS' . '</p>
            ' . $legal_name . ' ' . $address_line1 . ' ' . $address_line2 . ' ' . $address_line3 . ' ' . $address_line4 . '<p>' . $city . ' ' . $district . ' ' . $postal_code . '</p>' . $gst_number . ' ' . $phone;

        if (isset($data['consignee_detail']['nick_name'])) {
            $nick_name = '<p><b>' . $data['consignee_detail']['nick_name'] . '</b></p>';
        } else {
            $nick_name = '';
        }
        if (isset($data['consignee_detail']['address_line1'])) {
            $address_line1 = '<p>' . $data['consignee_detail']['address_line1'] . '</p>';
        } else {
            $address_line1 = '';
        }
        if (isset($data['consignee_detail']['address_line2'])) {
            $address_line2 = '<p>' . $data['consignee_detail']['address_line2'] . '</p>';
        } else {
            $address_line2 = '';
        }
        if (isset($data['consignee_detail']['address_line3'])) {
            $address_line3 = '<p>' . $data['consignee_detail']['address_line3'] . '</p>';
        } else {
            $address_line3 = '';
        }
        if (isset($data['consignee_detail']['address_line4'])) {
            $address_line4 = '<p>' . $data['consignee_detail']['address_line4'] . '</p>';
        } else {
            $address_line4 = '';
        }
        if (isset($data['consignee_detail']['city'])) {
            $city = $data['consignee_detail']['city'] . ',';
        } else {
            $city = '';
        }
        if (isset($data['consignee_detail']['get_zone']['district'])) {
            $district = @$data['consignee_detail']['get_zone']['district'] . ',';
        } else {
            $district = '';
        }
        if (isset($data['consignee_detail']['postal_code'])) {
            $postal_code = $data['consignee_detail']['postal_code'];
        } else {
            $postal_code = '';
        }

        if (isset($data['consignee_detail']['gst_number'])) {
            $gst_number = '<p>GST No: ' . $data['consignee_detail']['gst_number'] . '</p>';
        } else {
            $gst_number = '';
        }
        if (isset($data['consignee_detail']['phone'])) {
            $phone = '<p>Phone No: ' . $data['consignee_detail']['phone'] . '</p>';
        } else {
            $phone = '';
        }

        $consnee_add = '<p>' . 'CONSIGNEE NAME & ADDRESS' . '</p>
        ' . $nick_name . ' ' . $address_line1 . ' ' . $address_line2 . ' ' . $address_line3 . ' ' . $address_line4 . '<p>' . $city . ' ' . $district . ' ' . $postal_code . '</p>' . $gst_number . ' ' . $phone;

        if (isset($data['shipto_detail']['nick_name'])) {
            $nick_name = '<p><b>' . $data['shipto_detail']['nick_name'] . '</b></p>';
        } else {
            $nick_name = '';
        }
        if (isset($data['shipto_detail']['address_line1'])) {
            $address_line1 = '<p>' . $data['shipto_detail']['address_line1'] . '</p>';
        } else {
            $address_line1 = '';
        }
        if (isset($data['shipto_detail']['address_line2'])) {
            $address_line2 = '<p>' . $data['shipto_detail']['address_line2'] . '</p>';
        } else {
            $address_line2 = '';
        }
        if (isset($data['shipto_detail']['address_line3'])) {
            $address_line3 = '<p>' . $data['shipto_detail']['address_line3'] . '</p>';
        } else {
            $address_line3 = '';
        }
        if (isset($data['shipto_detail']['address_line4'])) {
            $address_line4 = '<p>' . $data['shipto_detail']['address_line4'] . '</p>';
        } else {
            $address_line4 = '';
        }
        if (isset($data['shipto_detail']['city'])) {
            $city = $data['shipto_detail']['city'] . ',';
        } else {
            $city = '';
        }
        if (isset($data['shipto_detail']['get_zone']['district'])) {
            $district = @$data['shipto_detail']['get_zone']['district'] . ',';
        } else {
            $district = '';
        }
        if (isset($data['shipto_detail']['postal_code'])) {
            $postal_code = $data['shipto_detail']['postal_code'];
        } else {
            $postal_code = '';
        }
        if (isset($data['shipto_detail']['gst_number'])) {
            $gst_number = '<p>GST No: ' . $data['shipto_detail']['gst_number'] . '</p>';
        } else {
            $gst_number = '';
        }
        if (isset($data['shipto_detail']['phone'])) {
            $phone = '<p>Phone No: ' . $data['shipto_detail']['phone'] . '</p>';
        } else {
            $phone = '';
        }

        $shiptoadd = '<p>' . 'SHIP TO NAME & ADDRESS' . '</p>
        ' . $nick_name . ' ' . $address_line1 . ' ' . $address_line2 . ' ' . $address_line3 . ' ' . $address_line4 . '<p>' . $city . ' ' . $district . ' ' . $postal_code . '</p>' . $gst_number . ' ' . $phone;

        $generate_qrcode = QrCode::size(150)->generate('Eternity Forwarders Pvt. Ltd.');
        $output_file = '/qr-code/img-' . time() . '.svg';
        Storage::disk('public')->put($output_file, $generate_qrcode);
        $fullpath = storage_path('app/public/' . $output_file);
        //echo'<pre>'; print_r($fullpath);
        if ($request->typeid == 1) {
            $adresses = '<table width="100%">
                    <tr>
                        <td style="width:50%">' . $conr_add . '</td>
                        <td style="width:50%">' . $consnee_add . '</td>
                    </tr>
                </table>';
        } else if ($request->typeid == 2) {
            $adresses = '<table width="100%">
                        <tr>
                            <td style="width:33%">' . $conr_add . '</td>
                            <td style="width:33%">' . $consnee_add . '</td>
                            <td style="width:33%">' . $shiptoadd . '</td>
                        </tr>
                    </table>';
        }
        if ($locations->id == 2 || $locations->id == 6 || $locations->id == 26) {
            $branch_address = '<h2>' . $branch_add[1]->name . '</h2>
                                <table width="100%">
                                    <tr>
                                        <td width="50%">
                                            <p>' . $branch_add[1]->address . '</p>
                                            <p>' . $branch_add[1]->district . ' - ' . $branch_add[1]->postal_code . ',' . $branch_add[1]->state . '</p>
                                            <p>GST No. : ' . $branch_add[1]->gst_number . '</p>
                                            <p>CIN No. : U63030PB2021PTC053388 </p>
                                            <p>Email : ' . @$locations->email . '</p>
                                            <p>Phone No. : ' . @$locations->phone . '' . '</p>
                                            <br>
                                            <span>
                                                <hr id="s" style="width:100%;">
                                                </hr>
                                            </span>
                                        </td>';
        } else {
            $branch_address = '<h2>' . $branch_add[0]->name . '</h2>
            <table width="100%">
                <tr>
                    <td width="50%">
                        <p>Plot No. ' . $branch_add[0]->address . '</p>
                        <p>' . $branch_add[0]->district . ' - ' . $branch_add[0]->postal_code . ',' . $branch_add[0]->state . '</p>
                        <p>GST No. : ' . $branch_add[0]->gst_number . '</p>
                        <p>CIN No. : U63030PB2021PTC053388 </p>
                        <p>Email : ' . @$locations->email . '</p>
                        <p>Phone No. : ' . @$locations->phone . '' . '</p>
                        <br>
                        <span>
                            <hr id="s" style="width:100%;">
                            </hr>
                        </span>
                    </td>';
        }

        for ($i = 1; $i < 5; $i++) {
            if ($i == 1) {
                $type = 'ORIGINAL';
            } elseif ($i == 2) {
                $type = 'DUPLICATE';
            } elseif ($i == 3) {
                $type = 'TRIPLICATE';
            } elseif ($i == 4) {
                $type = 'QUADRUPLE';
            }

            $html = '<!DOCTYPE html>
                    <html lang="en">
                        <head>
                            <title>PDF</title>
                            <meta charset="utf-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1">
                            <style>
                                .aa{
                                    border: 1px solid black;
                                    border-collapse: collapse;
                                }
                                .bb{
                                    border: 1px solid black;
                                    border-collapse: collapse;
                                }
                                .cc{
                                    border: 1px solid black;
                                    border-collapse: collapse;
                                }
                                h2.l {
                                    margin-left: 90px;
                                    margin-top: 132px;
                                    margin-bottom: 2px;
                                }
                                p.l {
                                    margin-left: 90px;
                                }
                                img#set_img {
                                    margin-left: 25px;
                                    margin-bottom: 100px;
                                }
                                p {
                                    margin-top: 2px;
                                    margin-bottom: 2px;
                                }
                                h4 {
                                    margin-top: 2px;
                                    margin-bottom: 2px;
                                }
                                body {
                                    font-family: Arial, Helvetica, sans-serif;
                                    font-size: 14px;
                                }
                            </style>
                        </head>
                        <body>
                        <div class="container">
                            <div class="row">';

            $html .= $branch_address . '<td width="50%">
            <h2 class="l">CONSIGNMENT NOTE</h2>
            <p class="l">' . $type . '</p>
        </td>
    </tr>
</table></div></div>';
            $html .= '<div class="row"><div class="col-sm-6">
                                <table width="100%">
                                <tr>
                            <td width="30%">
                                <p><b>Consignment No.</b></p>
                                <p><b>Consignment Date</b></p>
                                <p><b>Dispatch From</b></p>
                                <p><b>Order Id</b></p>
                                <p><b>Invoice No.</b></p>
                                <p><b>Invoice Date</b></p>
                                <p><b>Value INR</b></p>
                                <p><b>Vehicle No.</b></p>
                                <p><b>Driver Name</b></p>
                                <p><b>Driver Number</b></p>
                            </td>
                            <td width="30%">';
            if (@$data['consignment_no'] != '') {
                $html .= '<p>' . $data['id'] . '</p>';
            } else {
                $html .= '<p>N/A</p>';
            }
            if (@$data['consignment_date'] != '') {
                $html .= '<p>' . date('d-m-Y', strtotime($data['consignment_date'])) . '</p>';
            } else {
                $html .= '<p> N/A </p>';
            }
            if (@$data['consigner_detail']['city'] != '') {
                $html .= '<p> ' . $data['consigner_detail']['city'] . '</p>';
            } else {
                $html .= '<p> N/A </p>';
            }
            if (@$data['order_id'] != '') {
                $html .= '<p>' . $data['order_id'] . '</p>';
            } else {
                $html .= '<p> - </p>';
            }
            if (@$data['invoice_no'] != '') {
                $html .= '<p>' . $data['invoice_no'] . '</p>';
            } else {
                $html .= '<p> N/A </p>';
            }
            if (@$data['invoice_date'] != '') {
                $html .= '<p>' . date('d-m-Y', strtotime($data['invoice_date'])) . '</p>';
            } else {
                $html .= '<p> N/A </p>';
            }

            if (@$data['invoice_amount'] != '') {
                $html .= '<p>' . $data['invoice_amount'] . '</p>';
            } else {
                $html .= '<p> N/A </p>';
            }
            if (@$data['vehicle_detail']['regn_no'] != '') {
                $html .= '<p>' . $data['vehicle_detail']['regn_no'] . '</p>';
            } else {
                $html .= '<p> - </p>';
            }
            if (@$data['driver_detail']['name'] != '') {
                $html .= '<p>' . ucwords($data['driver_detail']['name']) . '</p>';
            } else {
                $html .= '<p> - </p>';
            }
            if (@$data['driver_detail']['phone'] != '') {
                $html .= '<p>' . ucwords($data['driver_detail']['phone']) . '</p>';
            } else {
                $html .= '<p> - </p>';
            }

            $html .= '</td>
                            <td width="50%" colspan="3" style="text-align: center;">
                            <img src= "' . $fullpath . '" alt="barcode">
                            </td>
                        </tr>
                    </table>
                </div>
                <span><hr id="e"></hr></span>
            </div>
            <div class="main">' . $adresses . '</div>
            <span><hr id="e"></hr></span><br>';
            $html .= '<div class="bb">
                <table class="aa" width="100%">
                    <tr>
                        <th class="cc">Sr.No.</th>
                        <th class="cc">Description</th>
                        <th class="cc">Quantity</th>
                        <th class="cc">Net Weight</th>
                        <th class="cc">Gross Weight</th>
                        <th class="cc">Freight</th>
                        <th class="cc">Payment Terms</th>
                    </tr>';
            ///
            $counter = 0;
            foreach ($data['consignment_items'] as $k => $dataitem) {
                $counter = $counter + 1;
                $html .= '<tr>' .
                    '<td class="cc">' . $counter . '</td>' .
                    '<td class="cc">' . $dataitem['description'] . '</td>' .
                    '<td class="cc">' . $dataitem['packing_type'] . ' ' . $dataitem['quantity'] . '</td>' .
                    '<td class="cc">' . $dataitem['weight'] . ' Kgs.</td>' .
                    '<td class="cc">' . $dataitem['gross_weight'] . ' Kgs.</td>' .
                    '<td class="cc">INR ' . $dataitem['freight'] . '</td>' .
                    '<td class="cc">' . $dataitem['payment_type'] . '</td>' .
                    '</tr>';
            }
            $html .= '<tr><td colspan="2" class="cc"><b>TOTAL</b></td>
                            <td class="cc">' . $data['total_quantity'] . '</td>
                            <td class="cc">' . $data['total_weight'] . ' Kgs.</td>
                            <td class="cc">' . $data['total_gross_weight'] . ' Kgs.</td>
                            <td class="cc"></td>
                            <td class="cc"></td>
                        </tr></table></div><br><br>
                        <span><hr id="e"></hr></span>';

            $html .= '<div class="nn">
                                <table  width="100%">
                                    <tr>
                                        <td>
                                            <h4><b>Receivers Signature</b></h4>
                                            <p>Received the goods mentioned above in good condition.</p>
                                        </td>
                                        <td>
                                        <h4><b>For Eternity Forwarders Pvt. Ltd.</b></h4>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </body>
                    </html>';

            $pdf = \App::make('dompdf.wrapper');
            $pdf->loadHTML($html);
            $pdf->setPaper('A4', 'portrait');
            $pdf->save(public_path() . '/consignment-pdf/congn-' . $i . '.pdf')->stream('congn-' . $i . '.pdf');
            $pdf_name[] = 'congn-' . $i . '.pdf';
        }
        $pdfMerger = PDFMerger::init();
        foreach ($pdf_name as $pdf) {
            $pdfMerger->addPDF(public_path() . '/consignment-pdf/' . $pdf);
        }
        $pdfMerger->merge();
        $pdfMerger->save("all.pdf", "browser");
        $file = new Filesystem;
        $file->cleanDirectory('pdf');
    }

    public function printSticker(Request $request)
    {
        $query = ConsignmentNote::query();
        $authuser = Auth::user();
        $cc = explode(',', $authuser->branch_id);
        $branch_add = BranchAddress::first();
        $locations = Location::whereIn('id', $cc)->first();

        $cn_id = $request->id;
        $getdata = ConsignmentNote::where('id', $cn_id)->with('ConsignmentItems', 'ConsignerDetail', 'ConsigneeDetail', 'ShiptoDetail', 'VehicleDetail', 'DriverDetail')->first();
        $data = json_decode(json_encode($getdata), true);
        //echo'<pre>'; print_r($data);die;
        $regional = $data['consigner_detail']['regionalclient_id'];

        $getdataregional = RegionalClient::where('id', $regional)->with('BaseClient')->first();
        $sl = json_decode(json_encode($getdataregional), true);
        if (!empty($sl)) {
            $baseclient = $sl['base_client']['client_name'];
        } else {
            $baseclient = '';
        }

        //$logo = url('assets/img/logo_se.jpg');
        $barcode = url('assets/img/barcode.png');

        // if ($authuser->branch_id == 28) {
        //     return view('consignments.consignment-sticker-ldh', ['data' => $data, 'baseclient' => $baseclient]);
        // } else {
        return view('consignments.consignment-sticker', ['data' => $data, 'baseclient' => $baseclient]);
        // }
        //echo $barcode; die;

    }

    public function unverifiedList(Request $request)
    {
        $this->prefix = request()->route()->getPrefix();
        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $baseclient = explode(',', $authuser->baseclient_id);
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);
        $user = User::where('branch_id', $authuser->branch_id)->where('role_id', 2)->first();

        $data = $consignments = DB::table('consignment_notes')->select('consignment_notes.*', 'consignees.nick_name as consignee_id', 'consignees.address_line1 as farmer_address', 'consignees.city as city', 'consignees.postal_code as pincode', 'consignees.district as consignee_district', 'zones.primary_zone as zone', 'order_farms.farm_location as farm_id', 'farms.address as farm_address')
            ->join('consignees', 'consignees.id', '=', 'consignment_notes.consignee_id')
            ->leftjoin('order_farms', 'order_farms.order_id', '=', 'consignment_notes.id')
            ->leftjoin('farms', 'farms.id', '=', 'order_farms.farm_location')
            ->leftjoin('zones', 'zones.id', '=', 'consignees.zone_id')
            ->whereIn('consignment_notes.status', ['2', '5', '6']);

        if ($authuser->role_id == 1) {
            $data;
        } elseif ($authuser->role_id == 4) {
            $data = $data->whereIn('consignment_notes.regclient_id', $regclient);
        } elseif ($authuser->role_id == 6) {
            $data = $data->whereIn('base_clients.id', $baseclient);
        } elseif ($authuser->role_id == 7) {
            $data = $data->whereIn('regional_clients.id', $regclient);
        } else {
            $data = $data->whereIn('consignment_notes.status', ['2', '6']);
        }
        $data = $data->orderBy('id', 'DESC');
        $consignments = $data->get();

        $vehicles = Vehicle::where('status', '1')->select('id', 'regn_no')->get();
        $drivers = Driver::where('status', '1')->select('id', 'name', 'phone')->get();
        $vehicletypes = VehicleType::where('status', '1')->select('id', 'name')->get();
        return view('consignments.unverified-list', ['consignments' => $consignments, 'prefix' => $this->prefix, 'title' => $this->title, 'vehicles' => $vehicles, 'drivers' => $drivers, 'vehicletypes' => $vehicletypes])
            ->with('i', ($request->input('page', 1) - 1) * 5);
    }

    public function updateUnverifiedLr(Request $request)
    {

        $consignerId = $request->transaction_id;
        $cc = explode(',', $consignerId);
        $addvechileNo = $request->vehicle_id;
        $adddriverId = $request->driver_id;
        $vehicleType = $request->vehicle_type;
        $transporterName = $request->transporter_name;
        $purchasePrice = $request->purchase_price;
        $assigndate = date('Y-m-d');

        $consigner = DB::table('consignment_notes')->whereIn('id', $cc)->update(['vehicle_id' => $addvechileNo, 'driver_id' => $adddriverId, 'transporter_name' => $transporterName, 'vehicle_type' => $vehicleType, 'purchase_price' => $purchasePrice, 'assign_date' => $assigndate, 'battery_id' => $request->battery_id, 'no_of_battery' => $request->no_of_battery, 'delivery_status' => 'Assigned']);

        $consignees = DB::table('consignment_notes')->select('consignment_notes.*', 'consignees.nick_name as consignee_name', 'consignees.phone as phone', 'consignees.email as email', 'vehicles.regn_no as vehicle_id', 'consignees.city as city', 'consignees.postal_code as pincode', 'drivers.name as driver_id', 'drivers.phone as driver_phone', 'drivers.team_id as team_id', 'drivers.fleet_id as fleet_id')
            ->join('consignees', 'consignees.id', '=', 'consignment_notes.consignee_id')
            ->join('vehicles', 'vehicles.id', '=', 'consignment_notes.vehicle_id')
            ->join('drivers', 'drivers.id', '=', 'consignment_notes.driver_id')
            ->whereIn('consignment_notes.id', $cc)
            ->get(['consignees.city']);

        $simplyfy = json_decode(json_encode($consignees), true);
        foreach ($simplyfy as $value) {
            $consignment_no = $value['consignment_no'];
            $vehicle_no = $value['vehicle_id'];
            $consignee_name = $value['consignee_name'];
            $consignment_date = $value['consignment_date'];
            $city = $value['city'];
            $pincode = $value['pincode'];
            $total_quantity = $value['total_quantity'];
            $total_weight = $value['total_weight'];
            $driverName = $value['driver_id'];
            $driverPhone = $value['driver_phone'];
        }
        $chk_tooken = Driver::where('id', $adddriverId)->select('team_id', 'fleet_id')->get();
        $tooken_details = json_decode(json_encode($chk_tooken), true);
        // Push to tooken if Team Id & Fleet Id Available

        $transaction = DB::table('transaction_sheets')->whereIn('consignment_no', $cc)->where('status', 1)->update(['vehicle_no' => $vehicle_no, 'driver_name' => $driverName, 'driver_no' => $driverPhone, 'delivery_status' => 'Assigned']);

        $mytime = Carbon::now('Asia/Kolkata');
        $currentdate = $mytime->toDateTimeString();

        foreach ($cc as $c_id) {

            // =================== task assign
            $respons2 = array('consignment_id' => $c_id, 'status' => 'Assigned', 'create_at' => $currentdate, 'type' => '2');

            $lastjob = DB::table('jobs')->select('response_data')->where('consignment_id', $c_id)->orderBy('id', 'DESC')->first();
            if (!empty($lastjob->response_data)) {
                $st = json_decode($lastjob->response_data);
                array_push($st, $respons2);
                $sts = json_encode($st);

                $start = Job::create(['consignment_id' => $c_id, 'response_data' => $sts, 'status' => 'Assigned', 'type' => '2']);
                // ==== end started
            }

            $app_notify = $this->sendNotification($request->driver_id);

            // $order_details = ConsignmentNote::with('BillingClient')->where('id', $c_id)->first();
            //  // ----- otp alert msg ----------------- //
            // $billingclient = @$order_details->BillingClient->name;
            // $billingclientphone = @$order_details->BillingClient->phone;
            // $text = 'Dear '.$billingclient.',
            // Your AgriWings order '.$c_id.' is received and has been allocated. Below are the details of the Pilot -Name: '.$driverName.' No.:'.$driverPhone.' Date of Service: '.$order_details->edd.' Thanks for choosing AgriWings!';

            // $url = 'http://sms.innuvissolutions.com/api/mt/SendSMS?APIkey=' . $this->sms_link . '&senderid=AGRWNG&channel=Trans&DCS=0&flashsms=0&number=' . urlencode($billingclientphone) . '&text=' . urlencode($text) . '&route=2&peid=1701168155524038890';
            // $result = $this->SendTSMS($url);
        }

        $response['success'] = true;
        $response['success_message'] = "Data Imported successfully";
        return response()->json($response);
    }

    public function transactionSheet(Request $request)
    {
        $this->prefix = request()->route()->getPrefix();
        $peritem = Config::get('variable.PER_PAGE');
        $query = TransactionSheet::query();

        $this->prefix = request()->route()->getPrefix();
        $vehicles = Vehicle::where('status', '1')->select('id', 'regn_no')->get();
        $drivers = Driver::where('status', '1')->select('id', 'name', 'phone')->get();
        $vehicletypes = VehicleType::where('status', '1')->select('id', 'name')->get();
        $batterytypes = Battery::where('status', '1')->select('id', 'type', 'battery_no')->get();

        if ($request->ajax()) {
            if (isset($request->resetfilter)) {
                Session::forget('peritem');
                $url = URL::to($this->prefix . '/' . $this->segment);
                return response()->json(['success' => true, 'redirect_url' => $url]);
            }

            if (isset($request->updatestatus)) {
                if ($request->drs_status == 'Started') {
                    TransactionSheet::where('drs_no', $request->drs_no)->update(['delivery_status' => $request->drs_status]);
                } elseif ($request->drs_status == 'Successful') {
                    TransactionSheet::where('drs_no', $request->drs_no)->update(['delivery_status' => $request->drs_status]);
                }
                $url = $this->prefix . '/transaction-sheet';
                $response['success'] = true;
                $response['success_message'] = "Dsr cancel status updated successfully";
                $response['error'] = false;
                $response['page'] = 'dsr-cancel-update';
                $response['redirect_url'] = $url;

                return response()->json($response);
            }

            $authuser = Auth::user();
            $role_id = Role::where('id', '=', $authuser->role_id)->first();
            $baseclient = explode(',', $authuser->baseclient_id);
            $regclient = explode(',', $authuser->regionalclient_id);
            $cc = explode(',', $authuser->branch_id);
            $user = User::where('branch_id', $authuser->branch_id)->where('role_id', 2)->first();

            $query = $query->whereIn('status', ['1', '0', '3'])
                ->groupBy('drs_no');

            if ($authuser->role_id == 1) {
                $query = $query->with('ConsignmentDetail');
            } elseif ($authuser->role_id == 4) {
                $query = $query
                    ->whereHas('ConsignmentDetail', function ($query) use ($regclient) {
                        $query->whereIn('regclient_id', $regclient);
                    });
            } elseif ($authuser->role_id == 6) {
                $query = $query
                    ->whereHas('ConsignmentDetail', function ($query) use ($baseclient) {
                        $query->whereIn('base_clients.id', $baseclient);
                    });
            } elseif ($authuser->role_id == 7) {
                $query = $query
                    ->whereHas('ConsignmentDetail.ConsignerDetail.RegClient', function ($query) use ($baseclient) {
                        $query->whereIn('id', $regclient);
                    });
            } else {
                $query = $query->with('ConsignmentDetail')->whereIn('branch_id', $cc);
            }

            if (!empty($request->search)) {
                $search = $request->search;
                $searchT = str_replace("'", "", $search);
                $query->where(function ($query) use ($search, $searchT) {
                    $query->where('drs_no', 'like', '%' . $search . '%')
                        ->orWhere('vehicle_no', 'like', '%' . $search . '%')
                        ->orWhere('driver_name', 'like', '%' . $search . '%')
                        ->orWhere('driver_no', 'like', '%' . $search . '%');
                });
            }

            if ($request->peritem) {
                Session::put('peritem', $request->peritem);
            }

            $peritem = Session::get('peritem');
            if (!empty($peritem)) {
                $peritem = $peritem;
            } else {
                $peritem = Config::get('variable.PER_PAGE');
            }

            $vehicles = Vehicle::where('status', '1')->select('id', 'regn_no')->get();
            $drivers = Driver::where('status', '1')->select('id', 'name', 'phone')->get();
            $vehicletypes = VehicleType::where('status', '1')->select('id', 'name')->get();
            $batterytypes = Battery::where('status', '1')->select('id', 'type', 'battery_no')->get();
            $transaction = $query->orderBy('id', 'DESC')->paginate($peritem);
            $transaction = $transaction->appends($request->query());

            $html = view('consignments.download-drs-list-ajax', ['peritem' => $peritem, 'prefix' => $this->prefix, 'transaction' => $transaction, 'vehicles' => $vehicles, 'drivers' => $drivers, 'vehicletypes' => $vehicletypes, 'batterytypes' => $batterytypes])->render();

            return response()->json(['html' => $html]);
        }

        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $baseclient = explode(',', $authuser->baseclient_id);
        $regclient = explode(',', $authuser->regionalclient_id);

        $cc = explode(',', $authuser->branch_id);
        $user = User::where('branch_id', $authuser->branch_id)->where('role_id', 2)->first();

        $query = $query->with('ConsignmentDetail')
            ->whereIn('status', ['1', '0', '3'])
            ->groupBy('drs_no');

        if ($authuser->role_id == 1) {
            $query = $query->with('ConsignmentDetail');
        } elseif ($authuser->role_id == 3) {
            $query = $query;
        } elseif ($authuser->role_id == 4) {
            $query = $query
                ->whereHas('ConsignmentDetail', function ($query) use ($regclient) {
                    $query->whereIn('regclient_id', $regclient);
                });
        } elseif ($authuser->role_id == 6) {
            $query = $query
                ->whereHas('ConsignmentDetail', function ($query) use ($baseclient) {
                    $query->whereIn('base_clients.id', $baseclient);
                });
        } elseif ($authuser->role_id == 7) {
            $query = $query->with('ConsignmentDetail')->whereIn('regional_clients.id', $regclient);
        } else {
            $query = $query->with('ConsignmentDetail')->whereIn('branch_id', $cc);
        }
        $transaction = $query->orderBy('id', 'DESC')->paginate($peritem);
        $transaction = $transaction->appends($request->query());

        return view('consignments.download-drs-list', ['peritem' => $peritem, 'prefix' => $this->prefix, 'transaction' => $transaction, 'vehicles' => $vehicles, 'drivers' => $drivers, 'vehicletypes' => $vehicletypes, 'batterytypes' => $batterytypes]);
    }

    public function getTransactionDetails(Request $request)
    {
        $id = $_GET['cat_id'];
        $query = TransactionSheet::query();
        $query = $query->where('drs_no', $id)
            ->with('ConsignmentDetail', function ($query) {
                $query->whereIn('status', [1, 5]);
            });
        $query = $query
            ->orderby('order_no', 'asc')
            ->get();

        // $transcationview = DB::table('transaction_sheets')->select('transaction_sheets.*', 'consignment_notes.consignment_no as c_no')
        //     ->join('consignment_notes', 'consignment_notes.id', '=', 'transaction_sheets.consignment_no')->where('drs_no', $id)->where('consignment_notes.status', '1')->orderby('order_no', 'asc')->get();
        $result = json_decode(json_encode($query), true);

        $response['fetch'] = $result;
        $response['success'] = true;
        $response['success_message'] = "Data Imported successfully";
        echo json_encode($response);
    }
    public function printTransactionsheetold(Request $request)
    {
        $id = $request->id;
        $transcationview = TransactionSheet::select('*')->with('ConsignmentDetail', 'consigneeDetail')->where('drs_no', $id)->whereIn('status', ['1', '3'])->orderby('order_no', 'asc')->get();

        $simplyfy = json_decode(json_encode($transcationview), true);
        $no_of_deliveries = count($simplyfy);
        $details = $simplyfy[0];
        $pay = public_path('assets/img/LOGO_Frowarders.jpg');

        $drsDate = date('d-m-Y', strtotime($details['created_at']));
        $html = '<html>
        <head>
        <title>Document</title>
        <!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>-->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
          <style>
          table,
          th,
          td {
              border: 1px solid black;
              border-collapse: collapse;
              text-align: left;
          }
            @page { margin: 100px 25px; }
            header { position: fixed; top: -60px; left: 0px; right: 0px; height: 200px; }
            footer { position: fixed; bottom: -105px; left: 0px; right: 0px;  height: 100px; }
           /* p { page-break-after: always; }
            p:last-child { page-break-after: never; } */
            * {
                box-sizing: border-box;
              }


              .column {
                float: left;
                width: 14.33%;
                padding: 5px;
                height: auto;
              }


              .row:after {
                content: "";
                display: table;
                clear: both;
              }
              .dd{
                margin-left: 0px;
              }
          </style>
        </head>
        <body style="font-size:13px; font-family:Arial Helvetica,sans-serif;">
                    <header><div class="row" style="display:flex;">
                    <div class="column"  style="width: 493px;">
                        <h1 class="dd">Delivery Run Sheet</h1>
                        <div  class="dd">
                        <table style="width:100%">
                            <tr>
                                <th>DRS No.</th>
                                <th>DRS-' . $details['drs_no'] . '</th>
                                <th>Vehicle No.</th>
                                <th>' . $details['vehicle_no'] . '</th>
                            </tr>
                            <tr>
                                <td>DRS Date</td>
                                <td>' . $drsDate . '</td>
                                <td>Driver Name</td>
                                <td>' . @$details['driver_name'] . '</td>
                            </tr>
                            <tr>
                                <td>No. of Deliveries</td>
                                <td>' . $no_of_deliveries . '</td>
                                <td>Driver No.</td>
                                <td>' . @$details['driver_no'] . '</td>
                            </tr>
                        </table>
                    </div>

                    </div>
                     <div class="column" style="margin-left: 56px;">
                        <img src="' . $pay . '" class="imga" style = "width: 170px; height: 80px; margin-top:30px;">
                    </div>
                </div>
                <br>
                <div id="content"><div class="row" style="border: 1px solid black;">
                <div class="column" style="width:75px;">
                    <h4 style="margin: 0px;">Order Id</h4>
                </div>
                <div class="column" style="width:75px;">
                    <h4 style="margin: 0px;">LR No. & Date</h4>
                </div>
                <div class="column" style="width:140px;">
                    <h4 style="margin: 0px;">Consignee Name & Mobile Number</h4>
                </div>
                <div class="column" style="width:110px;">
                    <h4 style="margin: 0px;">Delivery City & PIN</h4>
                    </div>
                    <div class="column">
                    <h4 style="margin: 0px;">Shipment Details</h4>
                    </div>
                    <div class="column" style="width:170px;">
                    <h4 style="margin: 0px; ">Stamp & Signature of Receiver</h4>
                    </div>
                </div>
                </div>
                </header>
                    <footer><div class="row">
                    <div class="col-sm-12" style="margin-left: 0px;">
                        <p>Head Office:Forwarders private Limited</p>
                        <p style="margin-top:-13px;">Add:Plot No.B-014/03712,prabhat,Zirakpur-140603</p>
                        <p style="margin-top:-13px;">Phone:07126645510 email:contact@eternityforwarders.com</p>
                    </div>
                </div></footer>
                    <main style="margin-top:150px;">';
        $i = 0;
        $total_Boxes = 0;
        $total_weight = 0;

        foreach ($simplyfy as $dataitem) {

            $i++;
            if ($i % 7 == 0) {
                $html .= '<div style="page-break-before: always; margin-top:160px;"></div>';
            }

            $total_Boxes += $dataitem['total_quantity'];
            $total_weight += $dataitem['total_weight'];

            $html .= '
                <div class="row" style="border: 1px solid black;">
                    <div class="column" style="width:75px;">
                      <p style="margin-top:0px; overflow-wrap: break-word;">' . $dataitem['consignment_detail']['order_id'] . '</p>
                      <p></p>
                    </div>
                    <div class="column" style="width:75px;">
                        <p style="margin-top:0px;">' . $dataitem['consignment_no'] . '</p>
                        <p style="margin-top:-13px;">' . Helper::ShowDayMonthYear($dataitem['consignment_date']) . '</p>
                    </div>
                    <div class="column" style="width:140px;">
                        <p style="margin-top:0px;">' . $dataitem['consignee_id'] . '</p>
                        <p style="margin-top:-13px;">' . @$dataitem['consignee_detail']['phone'] . '</p>

                    </div>
                    <div class="column" style="width:110px;">
                        <p style="margin-top:0px;">' . $dataitem['city'] . '</p>
                        <p style="margin-top:-13px;">' . @$dataitem['pincode'] . '</p>

                      </div>
                      <div class="column" >
                        <p style="margin-top:0px;">Boxes:' . $dataitem['total_quantity'] . '</p>
                        <p style="margin-top:-13px;">Wt:' . $dataitem['consignment_detail']['total_gross_weight'] . '</p>
                        <p style="margin-top:-13px;">Inv No. ' . $dataitem['consignment_detail']['invoice_no'] . '</p>

                      </div>
                      <div class="column" style="width:170px;">
                        <p></p>
                      </div>
                  </div>

                <br>';
        }

        $html .= '</main>
        </body>
        </html>';

        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('print.pdf');
    }

    public function printTransactionsheet(Request $request)
    {
        $id = $request->id;
        $transcationview = TransactionSheet::select('*')
            ->with('ConsignmentDetail.ConsignerDetail.GetRegClient', 'consigneeDetail', 'ConsignmentItem', 'ConsignmentDetail.RegClient', 'ConsignmentDetail.Farm')
            ->whereHas('ConsignmentDetail', function ($q) {
                $q->where('status', '!=', 0);
            })
            ->where('drs_no', $id)
            ->whereIn('status', ['1', '3'])
            ->orderby('order_no', 'asc')->get();
        $simplyfy = json_decode(json_encode($transcationview), true);

        $no_of_deliveries = count($simplyfy);
        $details = $simplyfy[0];
        $pay = public_path('assets/img/agri.png');

        $date = new DateTime($details['created_at'], new \DateTimeZone('GMT-7'));
        $date->setTimezone(new \DateTimeZone('IST'));
        $drsDate = $date->format('d-m-Y');

        // $drsDate = date('d-m-Y', strtotime($newdate));

        $html = '<html>
        <head>
        <title>Document</title>
        <!-- <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/css/bootstrap.min.css">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>-->
        <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.4.1/js/bootstrap.min.js"></script>
          <style>
          table,
          th,
          td {
              border: 0px solid black;
              border-collapse: collapse;
              text-align: left;
          }
          .drs_t,
          .drs_r,
          .drs_d,
          .drs_h {
              border: 1px solid black;
              border-collapse: collapse;
              text-align: left;
          }
            @page { margin: 100px 25px; }
            header { position: fixed; top: -60px; left: 0px; right: 0px; height: 200px; }
            footer { position: fixed; bottom: -105px; left: 0px; right: 0px;  height: 100px; }
           /* p { page-break-after: always; }
            p:last-child { page-break-after: never; } */
            * {
                box-sizing: border-box;
              }


              .column {
                float: left;
                width: 14.33%;
                padding: 5px;
                height: auto;
              }


              .row:after {
                content: "";
                display: table;
                clear: both;
              }
              .dd{
                margin-left: 0px;
              }

          </style>
        </head>
        <body style="font-size:13px; font-family:Arial Helvetica,sans-serif;">
                    <header><div class="row" style="display:flex;">
                    <div class="column"  style="width: 493px;">
                        <h1 class="dd">Order Run Sheet</h1>
                        <div  class="dd">
                        <table class="drs_t" style="width:100%">
                            <tr class="drs_r">
                                <th class="drs_h">ORS No.</th>
                                <th class="drs_h">ORS-' . $details['drs_no'] . '</th>
                                <th class="drs_h">Drone No.</th>
                                <th class="drs_h">' . $details['vehicle_no'] . '</th>
                            </tr>
                            <tr class="drs_r">
                                <td class="drs_d">ORS Date</td>
                                <td class="drs_d">' . $drsDate . '</td>
                                <td class="drs_d">Rider Name</td>
                                <td class="drs_d">' . @$details['driver_name'] . '</td>
                            </tr>
                            <tr class="drs_r">
                                <td class="drs_d">No. of Deliveries</td>
                                <td class="drs_d">' . $no_of_deliveries . '</td>
                                <td class="drs_d">Rider No.</td>
                                <td class="drs_d">' . @$details['driver_no'] . '</td>
                            </tr>
                        </table>
                    </div>

                    </div>
                     <div class="column" style="margin-left: 56px;">
                        <img src="' . $pay . '" class="imga" style = "width: 170px; height: 80px; margin-top:30px;">
                    </div>
                </div>
                <br>
                <div id="content"><div class="row" style="border: 1px solid black;">
                <div class="column" style="width:125px;">
                    <h4 style="margin: 0px;"> Bill to Client</h4>
                    <h4 style="margin: 0px;">Order Details:</h4>
                </div>
                <div class="column" style="width:200px;">
                    <h4 style="margin: 0px;">Farmer Name &  Number</h4>
                </div>
                <div class="column" style="width:125px;">
                    <h4 style="margin: 0px;">Farm Address</h4>

                    </div>
                    <div class="column">
                    <h4 style="margin: 0px;">Crop And Acreage</h4>
                    </div>
                    <div class="column" style="width:170px;">
                    <h4 style="margin: 0px; ">Stamp & Signature of Receiver</h4>
                    </div>
                </div>
                </div>
                </header>
                    <footer><div class="row">
                    <div class="col-sm-12" style="margin-left: 0px;">
                        <p>Head Office:Forwarders private Limited</p>
                        <p style="margin-top:-13px;">Add:Plot No.B-014/03712,prabhat,Zirakpur-140603</p>
                        <p style="margin-top:-13px;">Phone:07126645510 email:contact@eternityforwarders.com</p>
                    </div>
                </div></footer>
                    <main style="margin-top:150px;">';
        $i = 0;
        $total_Boxes = 0;
        $total_weight = 0;

        foreach ($simplyfy as $dataitem) {
            //    echo'<pre>'; print_r($dataitem); die;

            $i++;
            if ($i % 5 == 0) {
                $html .= '<div style="page-break-before: always; margin-top:160px;"></div>';
            }

            $total_Boxes += $dataitem['total_quantity'];
            $total_weight += $dataitem['total_weight'];
            //echo'<pre>'; print_r($dataitem['consignment_no']); die;
            $html .= '
                <div class="row" style="border-left: 1px solid black; border: 1px solid black; margin-bottom: -10px;">

                    <div class="column" style="width:125px;">
                       <p style="margin-top:0px;">' . @$dataitem['consignment_detail']['reg_client']['name'] . '</p>
                        <p style="margin-top:-8px;">' . $dataitem['consignment_no'] . '</p>
                        <p style="margin-top:-13px;">' . Helper::ShowDayMonthYear($dataitem['consignment_date']) . '</p>
                    </div>
                    <div class="column" style="width:200px;">
                        <p style="margin-top:0px;">' . $dataitem['consignee_id'] . '</p>
                        <p style="margin-top:-13px;">' . @$dataitem['consignee_detail']['phone'] . '</p>

                    </div>
                    <div class="column" style="width:125px;">
                        <p style="margin-top:0px;">' . @$dataitem['consignment_detail']['farm']['field_area'] . '</p>
                        <p style="margin-top:-13px;">' . @$dataitem['consignment_detail']['farm']['address'] . '</p>

                      </div>
                      <div class="column" >

                        <p style="margin-top:-13px;"></p>
                      </div>
                      <div class="column" style="width:170px;">
                        <p></p>
                      </div>
                  </div>';
        }

        $html .= '</main>
        </body>
        </html>';

        $pdf = \App::make('dompdf.wrapper');
        $pdf->loadHTML($html);
        $pdf->setPaper('a4', 'portrait');
        return $pdf->stream('print.pdf');
    }

    public function updateEDD(Request $request)
    {
        //echo'<pre>'; print_r($_POST); die;
        $edd = $_POST['drs_edd'];
        $consignmentId = $_POST['consignment_id'];

        $consigner = DB::table('consignment_notes')->where('id', $consignmentId)->update(['edd' => $edd]);
        if ($consigner) {
            //echo'ok';
            return response()->json(['success' => 'EDD Updated Successfully']);
        } else {
            return response()->json(['error' => 'Something went wrong']);
        }
    }
    //////////////////////////////////remove lr////////////////////
    public function removeLR(Request $request)
    {
        //
        $consignmentId = $_GET['consignment_id'];
        $consigner = DB::table('consignment_notes')->where('id', $consignmentId)->update(['status' => '2']);
        $transac = DB::table('transaction_sheets')->where('consignment_no', $consignmentId)->delete();

        $response['success'] = true;
        $response['success_message'] = "Data Imported successfully";
        echo json_encode($response);
    }

    public function CreateEdd(Request $request)
    {

        $consignmentId = $_POST['consignmentID'];
        $authuser = Auth::user();
        $cc = $authuser->branch_id;

        /////check order book drs
        $checklrstatus = ConsignmentNote::whereIn('id', $consignmentId)->get();
        foreach ($checklrstatus as $check) {
            if ($check->status == 5) {
                $consigner = DB::table('consignment_notes')->whereIn('id', $consignmentId)->update(['booked_drs' => '1']);
            } else {
                $consigner = DB::table('consignment_notes')->whereIn('id', $consignmentId)->update(['status' => '1']);
            }
        }

        $consignment = DB::table('consignment_notes')->select('consignment_notes.*', 'consignees.nick_name as consignee_id', 'consignees.city as city', 'consignees.postal_code as pincode')
            ->join('consignees', 'consignees.id', '=', 'consignment_notes.consignee_id')
            ->whereIn('consignment_notes.id', $consignmentId)
            ->get(['consignees.city']);

        $simplyfy = json_decode(json_encode($consignment), true);
        //echo'<pre>'; print_r($simplyfy); die;

        $no_of_digit = 5;
        $drs = DB::table('transaction_sheets')->select('drs_no')->latest('drs_no')->first();
        $drs_no = json_decode(json_encode($drs), true);
        if (empty($drs_no) || $drs_no == null) {
            $drs_no['drs_no'] = 0;
        }
        $number = $drs_no['drs_no'] + 1;
        $drs_no = str_pad($number, $no_of_digit, "0", STR_PAD_LEFT);

        $i = 0;
        foreach ($simplyfy as $value) {
            $i++;
            $unique_id = $value['id'];
            $consignment_no = $value['consignment_no'];
            $consignee_id = $value['consignee_id'];
            $consignment_date = $value['consignment_date'];
            $city = $value['city'];
            $pincode = $value['pincode'];
            //echo'<pre>'; print_r($data); die;

            $transaction = DB::table('transaction_sheets')->insert(['drs_no' => $drs_no, 'consignment_no' => $unique_id, 'branch_id' => $cc, 'consignee_id' => $consignee_id, 'consignment_date' => $consignment_date, 'city' => $city, 'pincode' => $pincode, 'order_no' => $i, 'delivery_status' => 'Unassigned', 'status' => '1']);
        }

        $response['success'] = true;
        $response['success_message'] = "Data Imported successfully";
        return response()->json($response);
    }

    public function updateSuffle(Request $request)
    {
        $page_id = $request->page_id_array;

        for ($count = 0; $count < count($page_id); $count++) {
            $drs = DB::table('transaction_sheets')->where('id', $page_id[$count])->update(['order_no' => $count + 1]);
        }

        $response['success'] = true;
        $response['success_message'] = " Data suffle updated successfully";
        return response()->json($response);
    }

    public function view_saveDraft(Request $request)
    {
        //echo'hi';
        $id = $_GET['draft_id'];

        $transcationview = TransactionSheet::select('*')->with('ConsignmentDetail', 'ConsignmentItem')->where('drs_no', $id)
            ->whereHas('ConsignmentDetail', function ($query) {
                $query->whereIn('status', ['1', '5']);
            })
            ->orderby('order_no', 'asc')->get();

        // $transcationview = DB::table('transaction_sheets')->select('transaction_sheets.*','consignment_items.*','consignment_notes.status as lrstatus', 'consignment_notes.edd as edd')
        //     ->join('consignment_notes', 'consignment_notes.id', '=', 'transaction_sheets.consignment_no')
        //     ->join('consignment_items','consignment_items.consignment_id', '=', 'transaction_sheets.consignment_no')
        //     ->where('drs_no', $id)->where('consignment_notes.status', '1')->orderby('order_no', 'asc')->get();
        $result = json_decode(json_encode($transcationview), true);
        // echo'<pre>'; print_r($result); die;

        $response['fetch'] = $result;
        $response['success'] = true;
        $response['success_message'] = "Data Imported successfully";
        echo json_encode($response);
    }

    public function updateDelivery(Request $request)
    {
        $id = $_GET['draft_id'];
        $transcationview = DB::table('transaction_sheets')->select('transaction_sheets.*', 'consignment_notes.status as lrstatus', 'consignment_notes.edd as edd', 'consignment_notes.delivery_date as dd')
            ->join('consignment_notes', 'consignment_notes.id', '=', 'transaction_sheets.consignment_no')->where('drs_no', $id)->where('consignment_notes.status', '1')->get();
        $result = json_decode(json_encode($transcationview), true);

        $response['fetch'] = $result;
        $response['success'] = true;
        $response['success_message'] = "Data Imported successfully";
        echo json_encode($response);
    }

    public function updateDeliveryStatus(Request $request)
    {
        //echo'<pre>'; print_r($_POST); die;
        $consignmentId = $_POST['consignment_no'];
        $cc = explode(',', $consignmentId);

        $consigner = DB::table('consignment_notes')->whereIn('id', $cc)->update(['delivery_status' => 'Successful']);

        $drs = DB::table('transaction_sheets')->whereIn('consignment_no', $cc)->update(['status' => '3']);

        $response['success'] = true;
        $response['success_message'] = "Data Imported successfully";
        return response()->json($response);
    }

    public function updateDeliveryDateOneBy(Request $request)
    {
        $delivery_date = $_POST['delivery_date'];
        $consignmentId = $_POST['consignment_id'];
        $consigner = DB::table('consignment_notes')->where('id', $consignmentId)->update(['delivery_date' => $delivery_date]);
        if ($consigner) {
            //echo'ok';
            return response()->json(['success' => 'Delivery Date Updated Successfully']);
        } else {
            return response()->json(['error' => 'Something went wrong']);
        }
    }

    //+++++++++++++get delevery data model+++++++++++++++++++++++++

    public function getdeliverydatamodel(Request $request)
    {
        $transcationview = DB::table('transaction_sheets')->select('transaction_sheets.*', 'consignment_notes.status as lrstatus', 'consignment_notes.edd as edd', 'consignment_notes.delivery_date as dd', 'consignment_notes.signed_drs as signed_drs')
            ->join('consignment_notes', 'consignment_notes.id', '=', 'transaction_sheets.consignment_no')->where('drs_no', $request->drs_no)->whereIn('transaction_sheets.status', ['1', '0', '3'])->get();
        $result = json_decode(json_encode($transcationview), true);
        //echo'<pre>'; print_r($result); exit;
        $response['fetch'] = $result;

        $response['success'] = true;
        $response['success_message'] = "Data Imported successfully";
        echo json_encode($response);
    }

    //======================== Bulk Print LR ==============================//
    public function BulkLrView(Request $request)
    {

        $this->prefix = request()->route()->getPrefix();
        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $baseclient = explode(',', $authuser->baseclient_id);
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);
        $user = User::where('branch_id', $authuser->branch_id)->where('role_id', 2)->first();

        $query = ConsignmentNote::query();

        $query = $query->where('status', '!=', 5)
            ->with('ConsignerDetail', 'ConsigneeDetail');

        if ($authuser->role_id == 1) {
            $query;
        } elseif ($authuser->role_id == 4) {
            $query = $query->whereIn('regclient_id', $regclient);
        } elseif ($authuser->role_id == 7) {
            $query = $query->whereIn('regclient_id', $regclient);
        } else {
            $query = $query->whereIn('branch_id', $cc);
        }
        $query = $query->orderBy('id', 'DESC');
        $consignments = $query->get();

        return view('consignments.bulkLr-view', ['prefix' => $this->prefix, 'consignments' => $consignments, 'prefix' => $this->prefix, 'title' => $this->title]);
    }

    public function DownloadBulkLr(Request $request)
    {
        $lrno = $request->checked_lr;
        $pdftype = $request->type;

        $query = ConsignmentNote::query();
        $authuser = Auth::user();
        $cc = explode(',', $authuser->branch_id);
        $branch_add = BranchAddress::get();
        $locations = Location::whereIn('id', $cc)->first();

        foreach ($lrno as $key => $value) {

            $getdata = ConsignmentNote::where('id', $value)->with('ConsignmentItems', 'ConsignerDetail.GetZone', 'ConsigneeDetail.GetZone', 'ShiptoDetail.GetZone', 'VehicleDetail', 'DriverDetail')->first();
            $data = json_decode(json_encode($getdata), true);

            //  ==================================new lr view ==========================  //
            if (empty($data['invoice_no'])) {
                if ($data['consigner_detail']['legal_name'] != null) {
                    $legal_name = '<b>' . $data['consigner_detail']['legal_name'] . '</b><br>';
                } else {
                    $legal_name = '';
                }
                if ($data['consigner_detail']['address_line1'] != null) {
                    $address_line1 = '' . $data['consigner_detail']['address_line1'] . '<br>';
                } else {
                    $address_line1 = '';
                }
                if ($data['consigner_detail']['address_line2'] != null) {
                    $address_line2 = '' . $data['consigner_detail']['address_line2'] . '<br>';
                } else {
                    $address_line2 = '';
                }
                if ($data['consigner_detail']['address_line3'] != null) {
                    $address_line3 = '' . $data['consigner_detail']['address_line3'] . '<br>';
                } else {
                    $address_line3 = '';
                }
                if ($data['consigner_detail']['address_line4'] != null) {
                    $address_line4 = '' . $data['consigner_detail']['address_line4'] . '<br><br>';
                } else {
                    $address_line4 = '<br>';
                }
                if ($data['consigner_detail']['city'] != null) {
                    $city = $data['consigner_detail']['city'] . ',';
                } else {
                    $city = '';
                }
                if ($data['consigner_detail']['get_zone'] != null) {
                    $district = $data['consigner_detail']['get_zone']['state'] . ',';
                } else {
                    $district = '';
                }
                if ($data['consigner_detail']['postal_code'] != null) {
                    $postal_code = $data['consigner_detail']['postal_code'] . '<br>';
                } else {
                    $postal_code = '';
                }
                if ($data['consigner_detail']['gst_number'] != null) {
                    $gst_number = 'GST No: ' . $data['consigner_detail']['gst_number'] . '<br>';
                } else {
                    $gst_number = '';
                }
                if ($data['consigner_detail']['phone'] != null) {
                    $phone = 'Phone No: ' . $data['consigner_detail']['phone'] . '<br>';
                } else {
                    $phone = '';
                }

                $conr_add = $legal_name . ' ' . $address_line1 . ' ' . $address_line2 . ' ' . $address_line3 . ' ' . $address_line4 . '' . $city . ' ' . $district . ' ' . $postal_code . '' . $gst_number . ' ' . $phone;

                if (@$data['consignee_detail']['legal_name'] != null) {
                    $nick_name = '<b>' . $data['consignee_detail']['legal_name'] . '</b><br>';
                } else {
                    $nick_name = '';
                }
                if (@$data['consignee_detail']['address_line1'] != null) {
                    $address_line1 = '' . $data['consignee_detail']['address_line1'] . '<br>';
                } else {
                    $address_line1 = '';
                }
                if (@$data['consignee_detail']['address_line2'] != null) {
                    $address_line2 = '' . $data['consignee_detail']['address_line2'] . '<br>';
                } else {
                    $address_line2 = '';
                }
                if (@$data['consignee_detail']['address_line3'] != null) {
                    $address_line3 = '' . $data['consignee_detail']['address_line3'] . '<br>';
                } else {
                    $address_line3 = '';
                }
                if (@$data['consignee_detail']['address_line4'] != null) {
                    $address_line4 = '' . $data['consignee_detail']['address_line4'] . '<br><br>';
                } else {
                    $address_line4 = '<br>';
                }
                if (@$data['consignee_detail']['city'] != null) {
                    $city = $data['consignee_detail']['city'] . ',';
                } else {
                    $city = '';
                }
                if (@$data['consignee_detail']['get_zone'] != null) {
                    $district = $data['consignee_detail']['get_zone']['state'] . ',';
                } else {
                    $district = '';
                }
                if (@$data['consignee_detail']['postal_code'] != null) {
                    $postal_code = $data['consignee_detail']['postal_code'] . '<br>';
                } else {
                    $postal_code = '';
                }

                if (@$data['consignee_detail']['gst_number'] != null) {
                    $gst_number = 'GST No: ' . $data['consignee_detail']['gst_number'] . '<br>';
                } else {
                    $gst_number = '';
                }
                if (@$data['consignee_detail']['phone'] != null) {
                    $phone = 'Phone No: ' . $data['consignee_detail']['phone'] . '<br>';
                } else {
                    $phone = '';
                }

                $consnee_add = $nick_name . ' ' . $address_line1 . ' ' . $address_line2 . ' ' . $address_line3 . ' ' . $address_line4 . '' . $city . ' ' . $district . ' ' . $postal_code . '' . $gst_number . ' ' . $phone;

                if (@$data['shipto_detail']['legal_name'] != null) {
                    $nick_name = '<b>' . $data['shipto_detail']['legal_name'] . '</b><br>';
                } else {
                    $nick_name = '';
                }
                if (@$data['shipto_detail']['address_line1'] != null) {
                    $address_line1 = '' . $data['shipto_detail']['address_line1'] . '<br>';
                } else {
                    $address_line1 = '';
                }
                if (@$data['shipto_detail']['address_line2'] != null) {
                    $address_line2 = '' . $data['shipto_detail']['address_line2'] . '<br>';
                } else {
                    $address_line2 = '';
                }
                if (@$data['shipto_detail']['address_line3'] != null) {
                    $address_line3 = '' . $data['shipto_detail']['address_line3'] . '<br>';
                } else {
                    $address_line3 = '';
                }
                if (@$data['shipto_detail']['address_line4'] != null) {
                    $address_line4 = '' . $data['shipto_detail']['address_line4'] . '<br><br>';
                } else {
                    $address_line4 = '<br>';
                }
                if (@$data['shipto_detail']['city'] != null) {
                    $city = $data['shipto_detail']['city'] . ',';
                } else {
                    $city = '';
                }
                if (@$data['shipto_detail']['get_zone'] != null) {
                    $district = $data['shipto_detail']['get_zone']['state'] . ',';
                } else {
                    $district = '';
                }
                if (@$data['shipto_detail']['postal_code'] != null) {
                    $postal_code = $data['shipto_detail']['postal_code'] . '<br>';
                } else {
                    $postal_code = '';
                }
                if (@$data['shipto_detail']['gst_number'] != null) {
                    $gst_number = 'GST No: ' . $data['shipto_detail']['gst_number'] . '<br>';
                } else {
                    $gst_number = '';
                }
                if (@$data['shipto_detail']['phone'] != null) {
                    $phone = 'Phone No: ' . $data['shipto_detail']['phone'] . '<br>';
                } else {
                    $phone = '';
                }
                if (@$data['is_salereturn'] != 1) {
                    $shiptoadd = $nick_name . ' ' . $address_line1 . ' ' . $address_line2 . ' ' . $address_line3 . ' ' . $address_line4 . '' . $city . ' ' . $district . ' ' . $postal_code . '' . $gst_number . ' ' . $phone;
                } else {
                    $shiptoadd = '';
                }

                $generate_qrcode = QrCode::size(150)->generate('Eternity Forwarders Pvt. Ltd.');
                $output_file = '/qr-code/img-' . time() . '.svg';
                Storage::disk('public')->put($output_file, $generate_qrcode);
                $fullpath = storage_path('app/public/' . $output_file);
                //echo'<pre>'; print_r($fullpath);
                //  dd($generate_qrcode);
                $no_invoive = count($data['consignment_items']);
                if ($request->typeid == 1) {
                    if ($data['is_salereturn'] == "1") {
                        $adresses = '<table width="100%">
                            <tr>
                                <td style="width:50%">' . $consnee_add . '</td>
                                <td style="width:50%">' . $conr_add . '</td>
                            </tr>
                        </table>';
                    } else {
                        $adresses = '<table width="100%">
                            <tr>
                                <td style="width:50%">' . $conr_add . '</td>
                                <td style="width:50%">' . $consnee_add . '</td>
                            </tr>
                        </table>';
                    }
                } else if ($request->typeid == 2) {
                    if ($data['is_salereturn'] == 1) {
                        $adresses = '<table width="100%">
                                <tr>
                                    <td style="width:33%">' . $consnee_add . '</td>
                                    <td style="width:33%">' . $conr_add . '</td>

                                </tr>
                            </table>';
                    } else {
                        $adresses = '<table width="100%">
                                <tr>
                                    <td style="width:33%">' . $conr_add . '</td>
                                    <td style="width:33%">' . $consnee_add . '</td>
                                    <td style="width:33%">' . $shiptoadd . '</td>
                                </tr>
                            </table>';
                    }
                }
                if ($locations->id == 2 || $locations->id == 6 || $locations->id == 26) {
                    $branch_address = '<span style="font-size: 14px;"><b>' . $branch_add[1]->name . ' </b></span><br />
                <b>' . $branch_add[1]->address . ',</b><br />
                <b>	' . $branch_add[1]->district . ' - ' . $branch_add[1]->postal_code . ',' . $branch_add[1]->state . 'b</b><br />
                <b>GST No. : ' . $branch_add[1]->gst_number . '</b><br />';
                } else {
                    $branch_address = '<span style="font-size: 14px;"><b>' . $branch_add[0]->name . ' </b></span><br />
                                    <b>	Plot no: ' . $branch_add[0]->address . ',</b><br />
                                    <b>	' . $branch_add[0]->district . ' - ' . $branch_add[0]->postal_code . ',' . $branch_add[0]->state . 'b</b><br />
                                    <b>GST No. : ' . $branch_add[0]->gst_number . '</b><br />';
                }
                $pay = public_path('assets/img/LOGO_Frowarders.jpg');
                foreach ($pdftype as $i => $pdf) {

                    if ($pdf == 1) {
                        $type = 'ORIGINAL';
                    } elseif ($pdf == 2) {
                        $type = 'DUPLICATE';
                    } elseif ($pdf == 3) {
                        $type = 'TRIPLICATE';
                    } elseif ($pdf == 4) {
                        $type = 'QUADRUPLE';
                    }
                    if (!empty($data['consigner_detail']['get_zone'])) {
                        $cnr_state = $data['consigner_detail']['get_zone']['state'];
                    } else {
                        $cnr_state = '';
                    }

                    $html = '<!DOCTYPE html>
                    <html lang="en">
                        <head>
                            <!-- Required meta tags -->
                            <meta charset="utf-8" />
                            <meta name="viewport" content="width=device-width, initial-scale=1" />

                            <!-- Bootstdap CSS -->

                            <style>
                                * {
                                    box-sizing: border-box;
                                }
                                label {
                                    padding: 12px 12px 12px 0;
                                    display: inline-block;
                                }

                                /* Responsive layout - when the screen is less than 600px wide, make the two columns stack on top of each other instead of next to each other */
                                @media screen and (max-width: 600px) {
                                }
                                img {
                                    width: 120px;
                                    height: 60px;
                                }
                                .a {
                                    width: 290px;
                                    font-size: 11px;
                                }
                                td.b {
                                    width: 238px;
                                    margin: auto;
                                }
                                .width_set{
                                    width:200px;
                                }
                                img.imgu {
                                    margin-left: 58px;
                                    height:100px;
                                }
                                .loc {
                                        margin-bottom: -8px;
                                        margin-top: 27px;
                                    }
                                    .table3 {
                        border-collapse: collapse;
                        width: 378px;
                        height: 84px;
                        margin-left: 71px;
                    }
                          .footer {
                       position: fixed;
                       left: 0;
                       bottom: 0;


                    }
                    .vl {
                        border-left: solid;
                        height: 18px;
                        margin-left: 3px;
                    }
                    .ff{
                      margin-top: 26px;
                    }
                    .relative {
                      position: relative;
                      left: 30px;
                    }
                    .mini-table1{

                        border: 1px solid;
                        border-radius: 13px;
                        width: 429px;
                        height: 72px;

                    }
                    .mini-th{
                      width:90px;
                      font-size: 12px;
                    }
                    .ee{
                        margin:auto;
                        margin-top:12px;
                    }
                    .nn{
                      border-bottom:1px solid;
                    }
                    .mm{
                    border-right:1px solid;
                    padding:4px;
                    }
                    html { -webkit-print-color-adjust: exact; }
                    .td_style{
                        text-align: left;
                        padding: 8px;
                        color: #627429;
                    }
                            </style>
                        <!-- style="border-collapse: collapse; width: 369px; height: 72px; background:#d2c5c5;"class="table2" -->
                        </head>
                        <body style="font-family:Arial Helvetica,sans-serif;">
                            <div class="container-flex" style="margin-bottom: 5px; margin-top: -30px;">
                                <table style="height: 70px;">
                                    <tr>
                                    <td class="a" style="font-size: 10px;">
                                    ' . $branch_address . '
                                    </td>

                                        <td class="a">
                                        <b>	Email & Phone</b><br />
                                        <b>	' . @$locations->email . '</b><br />
                                        ' . @$locations->phone . '<br />

                                        </td>
                                    </tr>

                                </table>
                                <hr />
                                <table>
                                    <tr>
                                        <td class="b">
                                <div class="ff" >
                                              <img src="' . $fullpath . '" alt="" class="imgu" />
                                </div>
                                        </td>
                                        <td>
                                            <div style="margin-top: -15px; text-align: center">
                                                <h2 style="margin-bottom: -16px">CONSIGNMENT NOTE</h2>
                                                <P>' . $type . '</P>
                                            </div>
                               <div class="mini-table1" style="background:#C0C0C0;">
                                            <table style=" border-collapse: collapse;" class="ee">
                                                <tr>
                                                    <th class="mini-th mm nn">LR Number</th>
                                                    <th class="mini-th mm nn">LR Date</th>
                                                    <th class="mini-th mm nn">Dispatch</th>
                                                    <th class="mini-th nn">Delivery</th>
                                                </tr>
                                                <tr>
                                                    <th class="mini-th mm" >' . $data['id'] . '</th>
                                                    <th class="mini-th mm">' . date('d-m-Y', strtotime($data['consignment_date'])) . '</th>
                                                    <th class="mini-th mm"> ' . $data['consigner_detail']['city'] . '</th>
                                                    <th class="mini-th">' . $data['consignee_detail']['city'] . '</th>

                                                </tr>
                                            </table>
                                </div>
                                        </td>
                                    </tr>
                                </table>
                                <div class="loc">
                                    <table>
                                        <tr>
                                            <td class="width_set">
                                                <div style="margin-left: 20px">
                                            <i class="fa-solid fa-location-dot" style="font-size: 10px; ">&nbsp;&nbsp;<b>' . $data['consigner_detail']['postal_code'] . ',' . $data['consigner_detail']['city'] . ',' . $cnr_state . '</b></i><div class="vl" ></div>

                                                <i class="fa-solid fa-location-dot" style="font-size: 10px; ">&nbsp;&nbsp;<b>' . $data['consignee_detail']['postal_code'] . ',' . $data['consignee_detail']['city'] . ',' . @$data['consignee_detail']['get_zone']['state'] . '</b></i><div style="font-size: 10px; margin-left: 3px;">&nbsp; &nbsp;</div>
                                                </div>
                                            </td>
                                            <td class="width_set">
                                                <table border="1px solid" class="table3">
                                                    <tr>
                                                        <td width="40%" ><b style="margin-left: 7px;">Vehicle No</b></td>
                                                        <td>' . @$data['vehicle_detail']['regn_no'] . '</td>
                                                    </tr>
                                                    <tr>
                                                        <td width="40%"><b style="margin-left: 7px;"> Driver Name</b></td>
                                                        <td>' . ucwords(@$data['driver_detail']['name']) . '</td>
                                                    </tr>
                                                    <tr>
                                                        <td width="40%"><b style="margin-left: 7px;">Driver Number</b></td>
                                                        <td>' . ucwords(@$data['driver_detail']['phone']) . '</td>
                                                    </tr>
                                                </table>
                                            </td>
                                        </tr>
                                    </table>
                                </div>

                                <div class="container">
                                        <div class="row">
                                            <div class="col-sm-12 ">
                                                <h4 style="margin-left:19px;"><b>Pickup and Drop Information</b></h4>
                                            </div>
                                        </div>
                                    <table border="1" style=" border-collapse:collapse; width: 690px; ">
                                        <tr>
                                            <td width="30%" style="vertical-align:top; >
                                                <div class="container">
                                                <div>
                                                <h5  style="margin-left:6px; margin-top: 0px">CONSIGNOR NAME & ADDRESS</h5><br>
                                                </div>
                                                <div style="margin-top: -11px;">';
                    if ($data['is_salereturn'] == "1") {
                        $conr_address = $consnee_add;
                    } else {
                        $conr_address = $conr_add;
                    }
                    // '.$conr_add.'
                    $html .= '<p  style="margin-left:6px;margin-top: -13px; font-size: 12px;">' . $conr_address . '</p>
                                                </div>
                                            </td>
                                            <td width="30%" style="vertical-align:top;>
                                            <div class="container">
                                            <div>
                                            <h5  style="margin-left:6px; margin-top: 0px">CONSIGNEE NAME & ADDRESS</h5><br>
                                            </div>
                                                <div style="margin-top: -11px;">';
                    if ($data['is_salereturn'] == "1") {
                        $consnee_address = $conr_add;
                    } else {
                        $consnee_address = $consnee_add;
                    }
                    $html .= '<p  style="margin-left:6px;margin-top: -13px; font-size: 12px;">
                                                ' . $consnee_address . '
                                            </p>
                                                </div>
                                            </td>
                                            <td width="30%" style="vertical-align:top;>
                                            <div class="container">
                                            <div>
                                            <h5  style="margin-left:6px; margin-top: 0px">SHIP TO NAME & ADDRESS</h5><br>
                                            </div>
                                                <div style="margin-top: -11px;">
                                                <p  style="margin-left:6px;margin-top: -13px; font-size: 12px;">
                                              ' . $shiptoadd . '
                                            </p>
                                                </div>
                                            </td>
                                        </tr>
                                    </table>
                              </div>
                                        <div>
                                              <div class="row">
                                                                   <div class="col-sm-12 ">
                                                        <h4 style="margin-left:19px;"><b>Order Information</b></h4>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <table border="1" style=" border-collapse:collapse; width: 690px;height: 48px; font-size: 10px; background-color:#e0dddc40;">

                                                            <tr>
                                                                <th>Number of invoice</th>
                                                                <th>Item Description</th>
                                                                <th>Mode of packing</th>
                                                                <th>Total Quantity</th>
                                                                <th>Total Net Weight</th>
                                                                <th>Total Gross Weight</th>
                                                            </tr>
                                                            <tr>
                                                                <th>' . $no_invoive . '</th>
                                                                <th>' . $data['description'] . '</th>
                                                                <th>' . $data['packing_type'] . '</th>
                                                                <th>' . $data['total_quantity'] . '</th>
                                                                <th>' . $data['total_weight'] . ' Kgs.</th>
                                                                <th>' . $data['total_gross_weight'] . ' Kgs.</th>


                                                            </tr>
                                                        </table>
                                        </div>

                                        <div class="inputfiled">
                                        <table style="width: 690px;
                                        font-size: 10px; background-color:#e0dddc40;">
                                      <tr>
                                          <th style="width:70px ">Order ID</th>
                                          <th style="width: 70px">Inv No</th>
                                          <th style="width: 70px">Inv Date</th>
                                          <th style="width:70px " >Inv Amount</th>
                                          <th style="width:70px ">E-way No</th>
                                          <th style="width: 70px">E-Way Date</th>
                                          <th style="width: 60px">Quantity</th>
                                          <th style="width:70px ">Net Weight</th>
                                          <th style="width:70px ">Gross Weight</th>

                                      </tr>
                                    </table>
                                    <table style=" border-collapse:collapse; width: 690px;height: 45px; font-size: 10px; background-color:#e0dddc40; text-align: center;" border="1" >';
                    $counter = 0;
                    foreach ($data['consignment_items'] as $k => $dataitem) {
                        $counter = $counter + 1;

                        $html .= ' <tr>
                                        <td style="width:70px ">' . $dataitem['order_id'] . '</td>
                                        <td style="width: 70px">' . $dataitem['invoice_no'] . '</td>
                                        <td style="width:70px ">' . Helper::ShowDayMonthYear($dataitem['invoice_date']) . '</td>
                                        <td style="width:70px ">' . $dataitem['invoice_amount'] . '</td>
                                        <td style="width: 70px">' . $dataitem['e_way_bill'] . '</td>
                                        <td style="width:70px ">' . Helper::ShowDayMonthYear($dataitem['e_way_bill_date']) . '</td>
                                        <td style="width:60px "> ' . $dataitem['quantity'] . '</td>
                                        <td style="width:70px ">' . $dataitem['weight'] . ' Kgs. </td>
                                        <td style="width:70px "> ' . $dataitem['gross_weight'] . ' Kgs.</td>

                                        </tr>';
                    }
                    $html .= '      </table>
                                        <div>
                                            <table style="margin-top:0px;">
                                            <tr>
                                            <td width="50%" style="font-size: 13px;"><p style="margin-top:60px;"><b>Received the goods mentioned above in good conditions.</b><br><br>Receivers Name & Number:<br><br>Receiving Date & Time	:<br><br>Receiver Signature:<br><br></p></td>
                                            <td  width="50%"><p style="margin-left: 99px; margin-bottom:150px;"><b>For Eternity Forwarders Pvt.Ltd</b></p></td>
                                        </tr>
                                            </table>

                                        </div>
                                  </div>

                          <!-- <div class="footer">
                                          <p style="text-align:center; font-size: 10px;">Terms & Conditions</p>
                                        <p style="font-size: 8px; margin-top: -5px">1. Eternity Solutons does not take any responsibility for damage,leakage,shortage,breakages,soliage by sun ran ,fire and any other damage caused.</p>
                                        <p style="font-size: 8px; margin-top: -5px">2. The goods will be delivered to Consignee only against,payment of freight or on confirmation of payment by the consignor. </p>
                                        <p style="font-size: 8px; margin-top: -5px">3. The delivery of the goods will have to be taken immediately on arrival at the destination failing which the  consignee will be liable to detention charges @Rs.200/hour or Rs.300/day whichever is lower.</p>
                                        <p style="font-size: 8px; margin-top: -5px">4. Eternity Solutons takes absolutely no responsibility for delay or loss in transits due to accident strike or any other cause beyond its control and due to breakdown of vehicle and for the consequence thereof. </p>
                                        <p style="font-size: 8px; margin-top: -5px">5. Any complaint pertaining the consignment note will be entertained only within 15 days of receipt of the meterial.</p>
                                        <p style="font-size: 8px; margin-top: -5px">6. In case of mismatch in e-waybill & Invoice of the consignor, Eternity Solutons will impose a penalty of Rs.15000/Consignment  Note in addition to the detention charges stated above. </p>
                                        <p style="font-size: 8px; margin-top: -5px">7. Any dispute pertaining to the consigment Note will be settled at chandigarh jurisdiction only.</p>
                          </div> -->
                            </div>
                            <!-- Optional JavaScript; choose one of the two! -->

                            <!-- Option 1: Bootstdap Bundle with Popper -->
                            <script
                                src="https://cdn.jsdelivr.net/npm/bootstdap@5.0.2/dist/js/bootstdap.bundle.min.js"
                                integrity="sha384-MrcW6ZMFYlzcLA8Nl+NtUVF0sA7MsXsP1UyJoMp4YLEuNSfAP+JcXn/tWtIaxVXM"
                                crossorigin="anonymous"
                            ></script>

                            <!-- Option 2: Separate Popper and Bootstdap JS -->
                            <!--
                        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.2/dist/umd/popper.min.js" integrity="sha384-IQsoLXl5PILFhosVNubq5LC7Qb9DXgDA9i+tQ8Zj3iwWAwPtgFTxbJ8NT4GN1R8p" crossorigin="anonymous"></script>
                        <script src="https://cdn.jsdelivr.net/npm/bootstdap@5.0.2/dist/js/bootstdap.min.js" integrity="sha384-cVKIPhGWiC2Al4u+LWgxfKtdIcfu0JTxR+EQDz/bgldoEyl4H0zUF0QKbrJ0EcQF" crossorigin="anonymous"></script>
                        -->
                        </body>
                    </html>
                    ';

                    $pdf = \App::make('dompdf.wrapper');
                    $pdf->loadHTML($html);
                    $pdf->setPaper('legal', 'portrait');
                    $pdf->save(public_path() . '/bulk/congn-' . $i . '-' . $value . '.pdf')->stream('congn-' . $i . '-' . $value . '.pdf');
                    $pdf_name[] = 'congn-' . $i . '-' . $value . '.pdf';
                }
            } else {

                if ($data['consigner_detail']['nick_name'] != null) {
                    $nick_name = '<p><b>' . $data['consigner_detail']['nick_name'] . '</b></p>';
                } else {
                    $nick_name = '';
                }
                if ($data['consigner_detail']['address_line1'] != null) {
                    $address_line1 = '<p>' . $data['consigner_detail']['address_line1'] . '</p>';
                } else {
                    $address_line1 = '';
                }
                if ($data['consigner_detail']['address_line2'] != null) {
                    $address_line2 = '<p>' . $data['consigner_detail']['address_line2'] . '</p>';
                } else {
                    $address_line2 = '';
                }
                if ($data['consigner_detail']['address_line3'] != null) {
                    $address_line3 = '<p>' . $data['consigner_detail']['address_line3'] . '</p>';
                } else {
                    $address_line3 = '';
                }
                if ($data['consigner_detail']['address_line4'] != null) {
                    $address_line4 = '<p>' . $data['consigner_detail']['address_line4'] . '</p>';
                } else {
                    $address_line4 = '';
                }
                if ($data['consigner_detail']['city'] != null) {
                    $city = $data['consigner_detail']['city'] . ',';
                } else {
                    $city = '';
                }
                if ($data['consigner_detail']['district'] != null) {
                    $district = $data['consigner_detail']['district'] . ',';
                } else {
                    $district = '';
                }
                if ($data['consigner_detail']['postal_code'] != null) {
                    $postal_code = $data['consigner_detail']['postal_code'];
                } else {
                    $postal_code = '';
                }
                if ($data['consigner_detail']['gst_number'] != null) {
                    $gst_number = '<p>GST No: ' . $data['consigner_detail']['gst_number'] . '</p>';
                } else {
                    $gst_number = '';
                }
                if ($data['consigner_detail']['phone'] != null) {
                    $phone = '<p>Phone No: ' . $data['consigner_detail']['phone'] . '</p>';
                } else {
                    $phone = '';
                }

                $conr_add = '<p>' . 'CONSIGNOR NAME & ADDRESS' . '</p>
            ' . $nick_name . ' ' . $address_line1 . ' ' . $address_line2 . ' ' . $address_line3 . ' ' . $address_line4 . '<p>' . $city . ' ' . $district . ' ' . $postal_code . '</p>' . $gst_number . ' ' . $phone;

                if ($data['consignee_detail']['nick_name'] != null) {
                    $nick_name = '<p><b>' . $data['consignee_detail']['nick_name'] . '</b></p>';
                } else {
                    $nick_name = '';
                }
                if ($data['consignee_detail']['address_line1'] != null) {
                    $address_line1 = '<p>' . $data['consignee_detail']['address_line1'] . '</p>';
                } else {
                    $address_line1 = '';
                }
                if ($data['consignee_detail']['address_line2'] != null) {
                    $address_line2 = '<p>' . $data['consignee_detail']['address_line2'] . '</p>';
                } else {
                    $address_line2 = '';
                }
                if ($data['consignee_detail']['address_line3'] != null) {
                    $address_line3 = '<p>' . $data['consignee_detail']['address_line3'] . '</p>';
                } else {
                    $address_line3 = '';
                }
                if ($data['consignee_detail']['address_line4'] != null) {
                    $address_line4 = '<p>' . $data['consignee_detail']['address_line4'] . '</p>';
                } else {
                    $address_line4 = '';
                }
                if ($data['consignee_detail']['city'] != null) {
                    $city = $data['consignee_detail']['city'] . ',';
                } else {
                    $city = '';
                }
                if ($data['consignee_detail']['district'] != null) {
                    $district = $data['consignee_detail']['district'] . ',';
                } else {
                    $district = '';
                }
                if ($data['consignee_detail']['postal_code'] != null) {
                    $postal_code = $data['consignee_detail']['postal_code'];
                } else {
                    $postal_code = '';
                }

                if ($data['consignee_detail']['gst_number'] != null) {
                    $gst_number = '<p>GST No: ' . $data['consignee_detail']['gst_number'] . '</p>';
                } else {
                    $gst_number = '';
                }
                if ($data['consignee_detail']['phone'] != null) {
                    $phone = '<p>Phone No: ' . $data['consignee_detail']['phone'] . '</p>';
                } else {
                    $phone = '';
                }

                $consnee_add = '<p>' . 'CONSIGNEE NAME & ADDRESS' . '</p>
        ' . $nick_name . ' ' . $address_line1 . ' ' . $address_line2 . ' ' . $address_line3 . ' ' . $address_line4 . '<p>' . $city . ' ' . $district . ' ' . $postal_code . '</p>' . $gst_number . ' ' . $phone;

                if ($data['shipto_detail']['nick_name'] != null) {
                    $nick_name = '<p><b>' . $data['shipto_detail']['nick_name'] . '</b></p>';
                } else {
                    $nick_name = '';
                }
                if ($data['shipto_detail']['address_line1'] != null) {
                    $address_line1 = '<p>' . $data['shipto_detail']['address_line1'] . '</p>';
                } else {
                    $address_line1 = '';
                }
                if ($data['shipto_detail']['address_line2'] != null) {
                    $address_line2 = '<p>' . $data['shipto_detail']['address_line2'] . '</p>';
                } else {
                    $address_line2 = '';
                }
                if ($data['shipto_detail']['address_line3'] != null) {
                    $address_line3 = '<p>' . $data['shipto_detail']['address_line3'] . '</p>';
                } else {
                    $address_line3 = '';
                }
                if ($data['shipto_detail']['address_line4'] != null) {
                    $address_line4 = '<p>' . $data['shipto_detail']['address_line4'] . '</p>';
                } else {
                    $address_line4 = '';
                }
                if ($data['shipto_detail']['city'] != null) {
                    $city = $data['shipto_detail']['city'] . ',';
                } else {
                    $city = '';
                }
                if ($data['shipto_detail']['district'] != null) {
                    $district = $data['shipto_detail']['district'] . ',';
                } else {
                    $district = '';
                }
                if ($data['shipto_detail']['postal_code'] != null) {
                    $postal_code = $data['shipto_detail']['postal_code'];
                } else {
                    $postal_code = '';
                }
                if ($data['shipto_detail']['gst_number'] != null) {
                    $gst_number = '<p>GST No: ' . $data['shipto_detail']['gst_number'] . '</p>';
                } else {
                    $gst_number = '';
                }
                if ($data['shipto_detail']['phone'] != null) {
                    $phone = '<p>Phone No: ' . $data['shipto_detail']['phone'] . '</p>';
                } else {
                    $phone = '';
                }

                $shiptoadd = '<p>' . 'SHIP TO NAME & ADDRESS' . '</p>
        ' . $nick_name . ' ' . $address_line1 . ' ' . $address_line2 . ' ' . $address_line3 . ' ' . $address_line4 . '<p>' . $city . ' ' . $district . ' ' . $postal_code . '</p>' . $gst_number . ' ' . $phone;

                $generate_qrcode = QrCode::size(150)->generate('Eternity Forwarders Pvt. Ltd.');
                $output_file = '/qr-code/img-' . time() . '.svg';
                Storage::disk('public')->put($output_file, $generate_qrcode);
                $fullpath = storage_path('app/public/' . $output_file);
                //echo'<pre>'; print_r($fullpath);
                //  dd($generate_qrcode);
                // if ($request->typeid == 1) {
                //     $adresses = '<table width="100%">
                //             <tr>
                //                 <td style="width:50%">' . $conr_add . '</td>
                //                 <td style="width:50%">' . $consnee_add . '</td>
                //             </tr>
                //         </table>';
                // } else if ($request->typeid == 2) {
                $adresses = '<table width="100%">
                        <tr>
                            <td style="width:33%">' . $conr_add . '</td>
                            <td style="width:33%">' . $consnee_add . '</td>
                            <td style="width:33%">' . $shiptoadd . '</td>
                        </tr>
                    </table>';
                // }

                // $type = count($pdftype);
                foreach ($pdftype as $i => $pdf) {

                    if ($pdf == 1) {
                        $type = 'ORIGINAL';
                    } elseif ($pdf == 2) {
                        $type = 'DUPLICATE';
                    } elseif ($pdf == 3) {
                        $type = 'TRIPLICATE';
                    } elseif ($pdf == 4) {
                        $type = 'QUADRUPLE';
                    }

                    $html = '<!DOCTYPE html>
                    <html lang="en">
                        <head>
                            <title>PDF</title>
                            <meta charset="utf-8">
                            <meta name="viewport" content="width=device-width, initial-scale=1">
                            <style>
                                .aa{
                                    border: 1px solid black;
                                    border-collapse: collapse;
                                }
                                .bb{
                                    border: 1px solid black;
                                    border-collapse: collapse;
                                }
                                .cc{
                                    border: 1px solid black;
                                    border-collapse: collapse;
                                }
                                h2.l {
                                    margin-left: 90px;
                                    margin-top: 132px;
                                    margin-bottom: 2px;
                                }
                                p.l {
                                    margin-left: 90px;
                                }
                                img#set_img {
                                    margin-left: 25px;
                                    margin-bottom: 100px;
                                }

                                p {
                                    margin-top: 2px;
                                    margin-bottom: 2px;
                                }
                                h4 {
                                    margin-top: 2px;
                                    margin-bottom: 2px;
                                }
                                body {
                                    font-family: Arial, Helvetica, sans-serif;
                                    font-size: 14px;
                                }
                            </style>
                        </head>

                        <body>
                        <div class="container">
                            <div class="row">';

                    $html .= '<h2>' . $branch_add->name . '</h2>
                                <table width="100%">
                                    <tr>
                                        <td width="50%">
                                            <p>Plot No. ' . $branch_add->address . '</p>
                                            <p>' . $branch_add->district . ' - ' . $branch_add->postal_code . ',' . $branch_add->state . '</p>
                                            <p>GST No. : ' . $branch_add['gst_number'] . '</p>
                                            <p>CIN No. : U63030PB2021PTC053388 </p>
                                            <p>Email : ' . @$locations->email . '</p>
                                            <p>Phone No. : ' . @$locations->phone . '' . '</p>
                                            <br>
                                            <span>
                                                <hr id="s" style="width:100%;">
                                                </hr>
                                            </span>
                                        </td>
                                        <td width="50%">
                                            <h2 class="l">CONSIGNMENT NOTE</h2>

                                            <p class="l">' . $type . '</p>
                                        </td>
                                    </tr>
                                </table></div></div>';
                    $html .= '<div class="row"><div class="col-sm-6">
                                <table width="100%">
                                <tr>
                            <td width="30%">
                                <p><b>Consignment No.</b></p>
                                <p><b>Consignment Date</b></p>
                                <p><b>Dispatch From</b></p>
                                <p><b>Order Id</b></p>
                                <p><b>Invoice No.</b></p>
                                <p><b>Invoice Date</b></p>
                                <p><b>Value INR</b></p>
                                <p><b>Vehicle No.</b></p>
                                <p><b>Driver Name</b></p>
                            </td>
                            <td width="30%">';
                    if (@$data['consignment_no'] != '') {
                        $html .= '<p>' . $data['id'] . '</p>';
                    } else {
                        $html .= '<p>N/A</p>';
                    }
                    if (@$data['consignment_date'] != '') {
                        $html .= '<p>' . date('d-m-Y', strtotime($data['consignment_date'])) . '</p>';
                    } else {
                        $html .= '<p> N/A </p>';
                    }
                    if (@$data['consigner_detail']['city'] != '') {
                        $html .= '<p> ' . $data['consigner_detail']['city'] . '</p>';
                    } else {
                        $html .= '<p> N/A </p>';
                    }
                    if (@$data['order_id'] != '') {
                        $html .= '<p>' . $data['order_id'] . '</p>';
                    } else {
                        $html .= '<p> - </p>';
                    }
                    if (@$data['invoice_no'] != '') {
                        $html .= '<p>' . $data['invoice_no'] . '</p>';
                    } else {
                        $html .= '<p> N/A </p>';
                    }
                    if (@$data['invoice_date'] != '') {
                        $html .= '<p>' . date('d-m-Y', strtotime($data['invoice_date'])) . '</p>';
                    } else {
                        $html .= '<p> N/A </p>';
                    }

                    if (@$data['invoice_amount'] != '') {
                        $html .= '<p>' . $data['invoice_amount'] . '</p>';
                    } else {
                        $html .= '<p> N/A </p>';
                    }
                    if (@$data['vehicle_detail']['regn_no'] != '') {
                        $html .= '<p>' . $data['vehicle_detail']['regn_no'] . '</p>';
                    } else {
                        $html .= '<p> - </p>';
                    }
                    if (@$data['driver_detail']['name'] != '') {
                        $html .= '<p>' . ucwords($data['driver_detail']['name']) . '</p>';
                    } else {
                        $html .= '<p> - </p>';
                    }

                    $html .= '</td>
                            <td width="50%" colspan="3" style="text-align: center;">
                            <img src= "' . $fullpath . '" alt="barcode">
                            </td>
                        </tr>
                    </table>
                </div>
                <span><hr id="e"></hr></span>
            </div>
            <div class="main">' . @$adresses . '</div>
            <span><hr id="e"></hr></span><br>';
                    $html .= '<div class="bb">
                <table class="aa" width="100%">
                    <tr>
                        <th class="cc">Sr.No.</th>
                        <th class="cc">Description</th>
                        <th class="cc">Quantity</th>
                        <th class="cc">Net Weight</th>
                        <th class="cc">Gross Weight</th>
                        <th class="cc">Freight</th>
                        <th class="cc">Payment Terms</th>
                    </tr>';
                    ///
                    $counter = 0;
                    foreach ($data['consignment_items'] as $k => $dataitem) {
                        $counter = $counter + 1;
                        $html .= '<tr>' .
                            '<td class="cc">' . $counter . '</td>' .
                            '<td class="cc">' . $dataitem['description'] . '</td>' .
                            '<td class="cc">' . $dataitem['packing_type'] . ' ' . $dataitem['quantity'] . '</td>' .
                            '<td class="cc">' . $dataitem['weight'] . ' Kgs.</td>' .
                            '<td class="cc">' . $dataitem['gross_weight'] . ' Kgs.</td>' .
                            '<td class="cc">INR ' . $dataitem['freight'] . '</td>' .
                            '<td class="cc">' . $dataitem['payment_type'] . '</td>' .
                            '</tr>';
                    }
                    $html .= '<tr><td colspan="2" class="cc"><b>TOTAL</b></td>
                            <td class="cc">' . $data['total_quantity'] . '</td>
                            <td class="cc">' . $data['total_weight'] . ' Kgs.</td>
                            <td class="cc">' . $data['total_gross_weight'] . ' Kgs.</td>
                            <td class="cc"></td>
                            <td class="cc"></td>
                        </tr></table></div><br><br>
                        <span><hr id="e"></hr></span>';

                    $html .= '<div class="nn">
                                <table  width="100%">
                                    <tr>
                                        <td>
                                            <h4><b>Receivers Signature</b></h4>
                                            <p>Received the goods mentioned above in good condition.</p>
                                        </td>
                                        <td>
                                        <h4><b>For Eternity Forwarders Pvt. Ltd.</b></h4>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </body>
                    </html>';

                    $pdf = \App::make('dompdf.wrapper');
                    $pdf->loadHTML($html);
                    $pdf->setPaper('A4', 'portrait');
                    $pdf->save(public_path() . '/bulk/congn-' . $i . '-' . $value . '.pdf')->stream('congn-' . $i . '-' . $value . '.pdf');
                    $pdf_name[] = 'congn-' . $i . '-' . $value . '.pdf';
                }
            }
        }
        $pdfMerger = PDFMerger::init();
        foreach ($pdf_name as $pdf) {
            $pdfMerger->addPDF(public_path() . '/bulk/' . $pdf);
        }
        $pdfMerger->merge();
        $pdfMerger->save("all.pdf", "browser");
        $file = new Filesystem;
        $file->cleanDirectory('pdf');
    }

    ////////////////get delevery date LR//////////////////////
    public function getDeleveryDateLr(Request $request)
    {
        $authuser = Auth::user();
        $role = $authuser->role_id;
        $transcationview = DB::table('consignment_notes')->select('consignment_notes.*', 'consignees.nick_name as consignee_nick', 'consignees.city as conee_city', 'jobs.status as job_status', 'jobs.response_data as trail')
            ->join('consignees', 'consignees.id', '=', 'consignment_notes.consignee_id')
            ->where('consignment_notes.id', $request->lr_no)
            ->leftjoin('jobs', function ($data) {
                $data->on('jobs.job_id', '=', 'consignment_notes.job_id')
                    ->on('jobs.id', '=', DB::raw("(select max(id) from jobs WHERE jobs.job_id = consignment_notes.job_id)"));
            })->get();

        $result = json_decode(json_encode($transcationview), true);
        //  echo '<pre>'; print_r($result); die;

        $response['fetch'] = $result;
        $response['role_id'] = $role;
        $response['success'] = true;
        $response['success_message'] = "Data Imported successfully";
        echo json_encode($response);
    }

    public function updateLrStatus(Request $request)
    {
        $this->prefix = request()->route()->getPrefix();
        if ($request->ajax()) {
            if (isset($request->updatestatus)) {

                if ($request->lr_status == 'Unassigned') {

                    ConsignmentNote::where('id', $request->lr_no)->update(['delivery_status' => $request->lr_status]);
                } elseif ($request->lr_status == 'Assigned') {

                    ConsignmentNote::where('id', $request->lr_no)->update(['delivery_status' => $request->lr_status]);
                } elseif ($request->lr_status == 'Started') {

                    ConsignmentNote::where('id', $request->lr_no)->update(['delivery_status' => $request->lr_status]);
                } elseif ($request->lr_status == 'Successful') {

                    ConsignmentNote::where('id', $request->lr_no)->update(['delivery_status' => $request->lr_status]);
                }
            }

            $url = $this->prefix . '/consignments';
            $response['success'] = true;
            $response['success_message'] = "Dsr cancel status updated successfully";
            $response['error'] = false;
            $response['page'] = 'dsr-cancel-update';
            $response['redirect_url'] = $url;

            return response()->json($response);
        }
    }

    //++++++++++++++++++++++ Tookan API Push +++++++++++++++++++++++++++++++++++//

    public function createTookanTasks($taskDetails)
    {

        //echo "<pre>";print_r($taskDetails);die;

        foreach ($taskDetails as $task) {

            $td = '{
                "api_key": "' . $this->apikey . '",
                "order_id": "' . $task['consignment_no'] . '",
                "job_description": "DRS-' . $task['id'] . '",
                "customer_email": "' . $task['email'] . '",
                "customer_username": "' . $task['consignee_name'] . '",
                "customer_phone": "' . $task['phone'] . '",
                "customer_address": "' . $task['pincode'] . ',' . $task['city'] . ',India",
                "latitude": "",
                "longitude": "",
                "job_delivery_datetime": "' . $task['edd'] . ' 21:00:00",
                "custom_field_template": "Template_1",
                "meta_data": [
                    {
                        "label": "Invoice Amount",
                        "data": "' . $task['invoice_amount'] . '"
                    },
                    {
                        "label": "Quantity",
                        "data": "' . $task['total_weight'] . '"
                    }
                ],
                "team_id": "' . $task['team_id'] . '",
                "auto_assignment": "1",
                "has_pickup": "0",
                "has_delivery": "1",
                "layout_type": "0",
                "tracking_link": 1,
                "timezone": "-330",
                "fleet_id": "' . $task['fleet_id'] . '",
                "notify": 1,
                "tags": "",
                "geofence": 0
            }';

            //echo "<pre>";print_r($td);echo "</pre>";die;

            //die;

            $curl = curl_init();

            curl_setopt_array($curl, array(
                CURLOPT_URL => 'https://api.tookanapp.com/v2/create_task',
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_POSTFIELDS => $td,
                CURLOPT_HTTPHEADER => array(
                    'Content-Type: application/json',
                ),
            ));

            $response[] = curl_exec($curl);

            curl_close($curl);
        }
        //echo "<pre>";print_r($response);echo "</pre>";die;
        return $response;
    }

    // Multiple Deliveries at once

    public function createTookanMultipleTasks($taskDetails)
    {
        //echo "<pre>";print_r($taskDetails);die;
        $deliveries = array();
        foreach ($taskDetails as $task) {

            $deliveries[] = '{
                "address": "' . $task['pincode'] . ',' . $task['city'] . ',India",
                "name": "' . $task['consignee_name'] . '",
                "latitude": " ",
                "longitude": " ",
                "time": "' . $task['edd'] . '",
                "phone": "' . $task['phone'] . '",
                "job_description": "LR-ID:' . $task['id'] . '",
                "template_name": "Template_1",
                "template_data": [
                  {
                    "label": "Invoice Amount",
                    "data":  "' . $task['invoice_amount'] . '"
                  },
                  {
                    "label": "Quantity",
                    "data": "' . $task['total_weight'] . '"
                  }
                ],
                "email": null,
                 "order_id":  "' . $task['id'] . '"
                }';
        }
        $de_json = implode(",", $deliveries);
        //echo "<pre>"; print_r($de_json);die;

        $apidata = '{
                "api_key": "' . $this->apikey . '",
                "fleet_id": "' . $taskDetails[0]['fleet_id'] . '",
                "timezone": -330,
                "has_pickup": 0,
                "has_delivery": 1,
                "layout_type": 0,
                "geofence": 0,
                "team_id": "' . $taskDetails[0]['team_id'] . '",
                "auto_assignment": 1,
                "tags": "",
                "deliveries": [' . $de_json . ']
              }';

        //echo "<pre>";print_r($apidata);echo "</pre>";die;

        //die;

        $curl = curl_init();

        curl_setopt_array($curl, array(
            CURLOPT_URL => 'https://api.tookanapp.com/v2/create_multiple_tasks',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $apidata,
            CURLOPT_HTTPHEADER => array(
                'Content-Type: application/json',
            ),
        ));

        $response = curl_exec($curl);

        curl_close($curl);
        return $response;
    }

    //+++++++++++++++++++++++ webhook for status update +++++++++++++++++++++++++//

    public function handle(Request $request)
    {
        header('Content-Type: application/json');
        $request = file_get_contents('php://input');
        $req_dump = print_r($request, true);
        $fp = Storage::disk('local')->put('file.json', $req_dump);
        $data = Storage::disk('local')->get('file.json');
        $json = json_decode($data, true);
        $job_id = $json['job_id'];
        $time = strtotime($json['job_delivery_datetime']);
        $newformat = date('Y-m-d', $time);
        $delivery_status = $json['job_state'];

        //Update LR
        if ($delivery_status == 'Successful') {
            $update = \DB::table('consignment_notes')->where('job_id', $job_id)->limit(1)->update(['delivery_status' => $json['job_state'], 'delivery_date' => $newformat]);
            //Update DRS
            $updatedrs = \DB::table('transaction_sheets')->where('job_id', $job_id)->limit(1)->update(['delivery_status' => $json['job_state'], 'delivery_date' => $newformat]);
        } else {
            $update = \DB::table('consignment_notes')->where('job_id', $job_id)->limit(1)->update(['delivery_status' => $json['job_state']]);
            //Update DRS
            $updatedrs = \DB::table('transaction_sheets')->where('job_id', $job_id)->limit(1)->update(['delivery_status' => $json['job_state']]);
        }

        //Save jobs response

        $jobData['job_id'] = $json['job_id'];
        $jobData['response_data'] = $data;
        $jobData['status'] = $json['job_state'];
        $jobData['type'] = 1;
        //echo "<pre>"; print_r($jobData);

        $saveJobresponse = Job::create($jobData);
    }

    //Notify on status update

    public function notifications()
    {
        $jobs = Job::select('job_id')->where('type', 1)->get();
        $simplyfy = json_decode(json_encode($jobs), true);
        $count = count($simplyfy);
        $jids = array();
        foreach ($simplyfy as $jid) {
            $lr = ConsignmentNote::select('id')->where('job_id', $jid['job_id'])->first();
            $smf = json_decode(json_encode($lr), true);
            //echo "<pre>"; print_r($smf);die;
            $jids[] = $smf['id'];
        }
        //return $count;
        $j = implode(',', $jids);
        return $j;
    }

    public function update_notifications()
    {

        $update = DB::table('jobs')->where('type', 1)->update(['type' => 0]);
        $response['success'] = true;

        return response()->json($response);

        event(new \App\Events\RealTimeMessage('Status updated as ' . $json['job_state'] . ' for consignment no -' . $job_id));
    }
    // //////////   ACTIVE CANCEL STATUS DRS
    public function drsStatus(Request $request)
    {
        $this->prefix = request()->route()->getPrefix();

        if ($request->ajax()) {
            if (isset($request->updatestatus)) {

                TransactionSheet::where('drs_no', $request->drs_id)->update(['status' => 0]);

                $drs = DB::table('transaction_sheets')->select('consignment_no')->where('drs_no', $request->drs_id)->get();
                $simplyfydrs = $data = json_decode(json_encode($drs), true);
                foreach ($simplyfydrs as $consgnment) {
                    $lrNo = $consgnment['consignment_no'];

                    ConsignmentNote::where('id', $lrNo)->update(['status' => 2]);
                }
            }

            $url = $this->prefix . '/transaction-sheet';
            $response['success'] = true;
            $response['success_message'] = "Dsr cancel status updated successfully";
            $response['error'] = false;
            $response['page'] = 'dsr-cancel-update';
            $response['redirect_url'] = $url;

            return response()->json($response);
        }
    }
    public function uploadDrsImg(Request $request)
    {
        try {

            $deliverydate = $request->delivery_date;
            $file = $request->file('file');
            if (!empty($file)) {
                $filename = $file->getClientOriginalName();
                $file->move(public_path('drs/Image'), $filename);
            } else {
                $filename = null;
            }
            if (!empty($deliverydate)) {
                ConsignmentNote::where('id', $request->lr)->update(['signed_drs' => $filename, 'delivery_date' => $deliverydate, 'delivery_status' => 'Successful']);
                TransactionSheet::where('consignment_no', $request->lr)->update(['delivery_status' => 'Successful']);

                $response['success'] = true;
                $response['messages'] = 'Image uploaded successfully';
                return Response::json($response);
            } else {
                $response['success'] = false;
                $response['messages'] = 'Img not Found';
                return Response::json($response);
            }
        } catch (\Exception $e) {
            $bug = $e->getMessage();
            $response['success'] = false;
            $response['messages'] = $bug;
            return Response::json($response);
        }
    }

    public function allSaveDRS(Request $request)
    {

        if (!empty($request->data)) {
            $get_data = $request->data;
            foreach ($get_data as $key => $save_data) {
                // echo'<pre>'; print_r($save_data); die;
                $lrno = $save_data['lrno'];
                $deliverydate = @$save_data['delivery_date'];
                $pic = @$save_data['img'];

                if ($save_data['job_id'] == 'null') {

                    if (!empty($pic)) {
                        $filename = $pic->getClientOriginalName();
                        $pic->move(public_path('drs/Image'), $filename);
                    } else {
                        $filename = null;
                    }

                    if (!empty($deliverydate)) {
                        $dateTimestamp1 = strtotime($save_data['lr_date']);
                        $dateTimestamp2 = strtotime($deliverydate);
                        // Compare the timestamp date
                        if ($dateTimestamp1 > $dateTimestamp2) {
                            $response['success'] = false;
                            $response['error'] = 'date_less';
                            $response['messages'] = 'Delivery date cannot be less than LR Date';
                            return Response::json($response);
                        }
                        ConsignmentNote::where('id', $lrno)->update(['signed_drs' => $filename, 'delivery_date' => $deliverydate, 'delivery_status' => 'Successful']);
                        TransactionSheet::where('consignment_no', $lrno)->update(['delivery_status' => 'Successful']);
                    } else {
                        if (!empty($filename)) {
                            ConsignmentNote::where('id', $lrno)->update(['signed_drs' => $filename]);
                            // TransactionSheet::where('consignment_no', $lrno)->update(['delivery_status' => 'Successful']);
                        }
                    }
                }
            }
            $response['success'] = true;
            $response['messages'] = 'img uploaded successfully';
            return Response::json($response);
        }
    }

    public function addmoreLr(Request $request)
    {

        $this->prefix = request()->route()->getPrefix();
        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $baseclient = explode(',', $authuser->baseclient_id);
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);
        $user = User::where('branch_id', $authuser->branch_id)->where('role_id', 2)->first();

        $data = $consignments = DB::table('consignment_notes')->select('consignment_notes.*', 'consignees.nick_name as consignee_id', 'consignees.address_line1 as farmer_address', 'consignees.city as city', 'consignees.postal_code as pincode', 'consignees.district as consignee_district', 'zones.primary_zone as zone', 'order_farms.farm_location as farm_id', 'farms.address as farm_address')
            ->join('consignees', 'consignees.id', '=', 'consignment_notes.consignee_id')
            ->leftjoin('order_farms', 'order_farms.order_id', '=', 'consignment_notes.id')
            ->leftjoin('farms', 'farms.id', '=', 'order_farms.farm_location')
            ->leftjoin('zones', 'zones.id', '=', 'consignees.zone_id')
            ->whereIn('consignment_notes.status', ['2', '5', '6']);

        if ($authuser->role_id == 1) {
            $data;
        } elseif ($authuser->role_id == 4) {
            $data = $data->whereIn('consignment_notes.regclient_id', $regclient);
        } elseif ($authuser->role_id == 6) {
            $data = $data->whereIn('base_clients.id', $baseclient);
        } elseif ($authuser->role_id == 7) {
            $data = $data->whereIn('regional_clients.id', $regclient);
        } else {
            $data = $data->whereIn('consignment_notes.status', ['2', '6']);
        }
        $data = $data->orderBy('id', 'DESC');
        $consignments = $data->get();

        $vehicles = Vehicle::where('status', '1')->select('id', 'regn_no')->get();
        $drivers = Driver::where('status', '1')->select('id', 'name', 'phone')->get();
        $vehicletypes = VehicleType::where('status', '1')->select('id', 'name')->get();

        $response['lrlist'] = $consignments;
        $response['success'] = true;
        $response['messages'] = 'img uploaded successfully';
        return Response::json($response);
    }

    public function addunverifiedLr(Request $request)
    {

        $drs_no = $_POST['drs_no'];
        $consignmentId = $_POST['consignmentID'];
        $authuser = Auth::user();
        $cc = $authuser->branch_id;

        $consigner = DB::table('consignment_notes')->whereIn('id', $consignmentId)->update(['status' => '1']);
        $consignment = DB::table('consignment_notes')->select('consignment_notes.*', 'consignees.nick_name as consignee_id', 'consignees.city as city', 'consignees.postal_code as pincode')
            ->join('consignees', 'consignees.id', '=', 'consignment_notes.consignee_id')
            ->whereIn('consignment_notes.id', $consignmentId)
            ->get(['consignees.city']);
        $simplyfy = json_decode(json_encode($consignment), true);

        $drs_order_no = DB::table('transaction_sheets')->select('order_no')->where('drs_no', $drs_no)->latest('order_no')->first();
        $orderno = $drs_order_no->order_no;

        $i = $orderno;
        foreach ($simplyfy as $value) {
            $i++;
            $unique_id = $value['id'];
            $consignment_no = $value['consignment_no'];
            $consignee_id = $value['consignee_id'];
            $consignment_date = $value['consignment_date'];
            $city = $value['city'];
            $pincode = $value['pincode'];
            $total_quantity = $value['total_quantity'];
            $total_weight = $value['total_weight'];

            $transaction = DB::table('transaction_sheets')->insert(['drs_no' => $drs_no, 'consignment_no' => $unique_id, 'branch_id' => $cc, 'consignee_id' => $consignee_id, 'consignment_date' => $consignment_date, 'city' => $city, 'pincode' => $pincode, 'total_quantity' => $total_quantity, 'total_weight' => $total_weight, 'order_no' => $i, 'delivery_status' => 'Unassigned', 'status' => '1']);
        }

        $response['success'] = true;
        $response['success_message'] = "Data Imported successfully";
        return response()->json($response);
    }

    public function viewupdateInvoice(Request $request)
    {
        $consignment = $_GET['consignment_id'];
        $consignmentitm = ConsignmentItem::where('consignment_id', $consignment)->get();

        $response['fetch'] = $consignmentitm;
        $response['success'] = true;
        $response['success_message'] = "Data Imported successfully";
        return response()->json($response);
    }
    public function allupdateInvoice(Request $request)
    {
        if (!empty($request->data)) {
            $get_data = $request->data;
            foreach ($get_data as $key => $save_data) {

                $itm_id = $save_data['id'];
                $billno = @$save_data['e_way_bill'];
                $billdate = @$save_data['e_way_bill_date'];

                if (!empty($save_data['e_way_bill_date'])) {
                    $billdate = $save_data['e_way_bill_date'];
                } else {
                    $billdate = null;
                }

                if (!empty($billno)) {
                    ConsignmentItem::where('id', $itm_id)->update(['e_way_bill' => $billno]);
                    if (!empty($billdate)) {
                        ConsignmentItem::where('id', $itm_id)->update(['e_way_bill_date' => $billdate]);
                    }
                } else {
                    if (!empty($billdate)) {
                        ConsignmentItem::where('id', $itm_id)->update(['e_way_bill_date' => $billdate]);
                    }
                }
            }
            $consignmentitm = ConsignmentItem::where('consignment_id', $request->cn_no)->get();

            $response['fetch'] = $consignmentitm;
            $response['success'] = true;
            $response['messages'] = 'img uploaded successfully';
            return Response::json($response);
        }
    }

    public function getJob(Request $request)
    {
        $this->prefix = request()->route()->getPrefix();
        $app_trail = Job::where('consignment_id', $request->lr_id)->orderBy('id', 'DESC')->first();

        $app_media = AppMedia::where('consignment_no', $request->lr_id)->get();

        if (!empty($app_trail)) {

            $url = URL::to($this->prefix . '/consignments');
            $response['success'] = true;
            $response['success_message'] = "Jobs fetch successfully";
            $response['error'] = false;
            $response['app_trail'] = $app_trail;
            $response['app_media'] = $app_media;
        } else {
            $url = URL::to($this->prefix . '/consignments');
            $response['success'] = false;
            $response['success_message'] = "Job data not found";
            $response['error'] = true;
        }

        return response()->json($response);
    }

    // public function invoiceCheck(Request $request)
    // {
    //     $invoice_check = Consignmentitem::where('invoice_no', $request->invc_no)->first();

    //     if(isset($request->invc_no)){
    //         if (isset($invoice_check)) {
    //             $response['success'] = true;
    //             $response['invc_check'] = true;
    //             $response['errors'] = "Invoice no already exist";
    //             return response()->json($response);
    //         }
    //         $response['success'] = false;
    //         $response['invc_check'] = false;
    //         return response()->json($response);
    //     }
    //     $response['success'] = false;
    //     $response['invc_check'] = false;
    //     return response()->json($response);
    // }

    public function exportDownloadDrs(Request $request)
    {
        return Excel::download(new exportDownloadDrs, 'PaymentReport.xlsx');
    }

    public function getItems(Request $request)
    {
        $getitem = ItemMaster::where('id', $request->item_val)->first();

        if ($getitem) {
            $response['success'] = true;
            $response['page'] = 'update-locations';
            $response['error'] = false;
            $response['item'] = $getitem;
            $response['success_message'] = "Item master fetch successfully";
        } else {
            $response['success'] = false;
            $response['error'] = true;
            $response['error_message'] = "Can not fetch Item master please try again";
        }

        return response()->json($response);
    }
    public function podView(Request $request)
    {
        $this->prefix = request()->route()->getPrefix();

        $sessionperitem = Session::get('peritem');
        if (!empty($sessionperitem)) {
            $peritem = $sessionperitem;
        } else {
            $peritem = Config::get('variable.PER_PAGE');
        }

        $query = ConsignmentNote::query();

        if ($request->ajax()) {
            if (isset($request->resetfilter)) {
                Session::forget('peritem');
                $url = URL::to($this->prefix . '/' . $this->segment);
                return response()->json(['success' => true, 'redirect_url' => $url]);
            }

            $authuser = Auth::user();
            $role_id = Role::where('id', '=', $authuser->role_id)->first();
            $regclient = explode(',', $authuser->regionalclient_id);
            $cc = explode(',', $authuser->branch_id);
            $user = User::where('branch_id', $authuser->branch_id)->where('role_id', 2)->first();

            $query = $query
                ->where('status', '!=', 5)
                ->with(
                    'ConsignmentItems:id,consignment_id,order_id,invoice_no,invoice_date,invoice_amount'
                );

            if ($authuser->role_id == 1) {
                $query = $query;
            } elseif ($authuser->role_id == 4) {
                $query = $query->whereIn('regclient_id', $regclient);
            } else {
                $query = $query->whereIn('branch_id', $cc);
            }

            if (!empty($request->search)) {
                $search = $request->search;
                $searchT = str_replace("'", "", $search);
                $query->where(function ($query) use ($search, $searchT) {

                    $query->where('id', 'like', '%' . $search . '%');
                });
            }

            if ($request->peritem) {
                Session::put('peritem', $request->peritem);
            }

            $peritem = Session::get('peritem');
            if (!empty($peritem)) {
                $peritem = $peritem;
            } else {
                $peritem = Config::get('variable.PER_PAGE');
            }

            $startdate = $request->startdate;
            $enddate = $request->enddate;

            if (isset($startdate) && isset($enddate)) {
                $consignments = $query->whereBetween('consignment_date', [$startdate, $enddate])->orderby('created_at', 'DESC')->paginate($peritem);
            } else {
                $consignments = $query->orderBy('id', 'DESC')->paginate($peritem);
            }

            $html = view('consignments.pod-view-ajax', ['prefix' => $this->prefix, 'consignments' => $consignments, 'peritem' => $peritem])->render();
            // $consignments = $consignments->appends($request->query());

            return response()->json(['html' => $html]);
        }

        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);
        $user = User::where('branch_id', $authuser->branch_id)->where('role_id', 2)->first();

        $query = $query
            ->where('status', '!=', 5)
            ->with(
                'ConsignmentItems:id,consignment_id,order_id,invoice_no,invoice_date,invoice_amount'
            );

        if ($authuser->role_id == 1) {
            $query = $query;
        } elseif ($authuser->role_id == 4) {
            $query = $query->whereIn('regclient_id', $regclient);
        } else {
            $query = $query->whereIn('branch_id', $cc);
        }

        $consignments = $query->orderBy('id', 'DESC')->paginate($peritem);
        $consignments = $consignments->appends($request->query());

        return view('consignments.pod-view', ['consignments' => $consignments, 'prefix' => $this->prefix, 'peritem' => $peritem]);
    }

    //////////////////////////////New Create Lr Form ///////////////////////////////////
    public function createNewLrForm()
    {
        $this->prefix = request()->route()->getPrefix();
        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);

        if ($authuser->role_id == 2 || $authuser->role_id == 3) {
            if ($authuser->role_id == $role_id->id) {
                $consigners = Consigner::select('id', 'nick_name')->whereIn('branch_id', $cc)->get();
            } else {
                $consigners = Consigner::select('id', 'nick_name')->get();
            }
        } else if ($authuser->role_id != 2 || $authuser->role_id != 3) {
            if ($authuser->role_id != 1) {
                $consigners = Consigner::select('id', 'nick_name')->whereIn('regionalclient_id', $regclient)->get();
            } else {
                $consigners = Consigner::select('id', 'nick_name')->get();
            }
        } else {
            $consigners = Consigner::select('id', 'nick_name')->get();
        }

        $vehicles = Vehicle::where('status', '1')->select('id', 'regn_no')->get();
        $drivers = Driver::where('status', '1')->select('id', 'name', 'phone')->get();
        $vehicletypes = VehicleType::where('status', '1')->select('id', 'name')->get();
        $itemlists = ItemMaster::where('status', '1')->get();

        /////////////////////////////Bill to regional clients //////////////////////////

        if ($authuser->role_id == 2 || $authuser->role_id == 3) {
            $branch = $authuser->branch_id;
            $branch_loc = explode(',', $branch);
            $regionalclient = RegionalClient::whereIn('location_id', $branch_loc)->select('id', 'name')->get();
        } elseif ($authuser->role_id == 4) {
            $reg = $authuser->regionalclient_id;
            $regional = explode(',', $reg);
            $regionalclient = RegionalClient::whereIn('id', $regional)->select('id', 'name')->get();
        } else {
            $regionalclient = RegionalClient::select('id', 'name')->get();
        }

        return view('consignments.new-create-consignment', ['prefix' => $this->prefix, 'consigners' => $consigners, 'vehicles' => $vehicles, 'vehicletypes' => $vehicletypes, 'drivers' => $drivers, 'regionalclient' => $regionalclient, 'itemlists' => $itemlists]);
    }

    public function newStoreLr(Request $request)
    {
        try {
            DB::beginTransaction();

            $this->prefix = request()->route()->getPrefix();
            $rules = array(
                'consigner_id' => 'required',
                'consignee_id' => 'required',
                'ship_to_id' => 'required',
            );
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $errors = $validator->errors();
                $response['success'] = false;
                $response['validation'] = false;
                $response['formErrors'] = true;
                $response['errors'] = $errors;
                return response()->json($response);
            }

            $authuser = Auth::user();
            $cc = explode(',', $authuser->branch_id);

            if (empty($request->vehicle_id)) {
                $status = '2';
            } else {
                $status = '1';
            }

            $consignmentsave['regclient_id'] = $request->regclient_id;
            $consignmentsave['consigner_id'] = $request->consigner_id;
            $consignmentsave['consignee_id'] = $request->consignee_id;
            $consignmentsave['ship_to_id'] = $request->ship_to_id;
            $consignmentsave['is_salereturn'] = $request->is_salereturn;
            $consignmentsave['consignment_date'] = $request->consignment_date;
            $consignmentsave['payment_type'] = $request->payment_type;
            $consignmentsave['description'] = $request->description;
            $consignmentsave['packing_type'] = $request->packing_type;
            $consignmentsave['dispatch'] = $request->dispatch;
            // $consignmentsave['total_quantity'] = $request->total_quantity;
            // $consignmentsave['total_weight'] = $request->total_weight;
            // $consignmentsave['total_gross_weight'] = $request->total_gross_weight;
            // $consignmentsave['total_freight'] = $request->total_freight;
            $consignmentsave['transporter_name'] = $request->transporter_name;
            $consignmentsave['vehicle_type'] = $request->vehicle_type;
            $consignmentsave['purchase_price'] = $request->purchase_price;
            $consignmentsave['user_id'] = $authuser->id;
            $consignmentsave['vehicle_id'] = $request->vehicle_id;
            $consignmentsave['driver_id'] = $request->driver_id;
            $consignmentsave['branch_id'] = $authuser->branch_id;

            $consignmentsave['edd'] = $request->edd;
            $consignmentsave['status'] = $status;
            $consignmentsave['lr_type'] = $request->lr_type;
            if (!empty($request->vehicle_id)) {
                $consignmentsave['delivery_status'] = "Started";
            } else {
                $consignmentsave['delivery_status'] = "Unassigned";
            }

            $consignee = Consignee::where('id', $request->consignee_id)->first();
            $consignee_pincode = $consignee->postal_code;

            $getpin_transfer = Zone::where('postal_code', $consignee_pincode)->first();
            $get_zonebranch = $getpin_transfer->hub_transfer;
            $get_branch = Location::where('name', $get_zonebranch)->first();
            $consignmentsave['to_branch_id'] = $get_branch->id;

            $get_location = Location::where('id', $authuser->branch_id)->first();
            $chk_h2h_branch = $get_location->with_h2h;
            $location_name = $get_location->name;

            if ($request->lr_type == 1) {

                if ($chk_h2h_branch == 1) {
                    ///h2h branch check
                    if ($location_name == $get_zonebranch) {
                        if (!empty($request->vehicle_id)) {
                            $consignmentsave['delivery_status'] = "Started";
                        } else {
                            $consignmentsave['delivery_status'] = "Unassigned";
                        }
                        $consignmentsave['hrs_status'] = 2;
                        $consignmentsave['h2h_check'] = 'lm';
                        ///same location check
                        if ($request->invoice_check == 1 || $request->invoice_check == 2) {
                            $saveconsignment = ConsignmentNote::create($consignmentsave);
                            if (!empty($request->data)) {
                                $get_data = $request->data;
                                foreach ($get_data as $key => $save_data) {

                                    $save_data['consignment_id'] = $saveconsignment->id;
                                    $save_data['status'] = 1;
                                    $saveconsignmentitems = ConsignmentItem::create($save_data);

                                    if ($saveconsignmentitems) {
                                        // dd($save_data['item_data']);
                                        if (!empty($save_data['item_data'])) {
                                            $qty_array = array();
                                            $netwt_array = array();
                                            $grosswt_array = array();
                                            $chargewt_array = array();
                                            foreach ($save_data['item_data'] as $key => $save_itemdata) {
                                                // echo "<pre>"; print_r($save_itemdata); die;
                                                $qty_array[] = $save_itemdata['quantity'];
                                                $netwt_array[] = $save_itemdata['net_weight'];
                                                $grosswt_array[] = $save_itemdata['gross_weight'];
                                                $chargewt_array[] = $save_itemdata['chargeable_weight'];

                                                $save_itemdata['conitem_id'] = $saveconsignmentitems->id;
                                                $save_itemdata['status'] = 1;

                                                $savesubitems = ConsignmentSubItem::create($save_itemdata);
                                            }

                                            $quantity_sum = array_sum($qty_array);
                                            $netwt_sum = array_sum($netwt_array);
                                            $grosswt_sum = array_sum($grosswt_array);
                                            $chargewt_sum = array_sum($chargewt_array);

                                            ConsignmentItem::where('id', $savesubitems->conitem_id)->update(['quantity' => $quantity_sum, 'weight' => $netwt_sum, 'gross_weight' => $grosswt_sum, 'chargeable_weight' => $chargewt_sum]);

                                            ConsignmentNote::where('id', $saveconsignment->id)->update(['total_quantity' => $quantity_sum, 'total_weight' => $netwt_sum, 'total_gross_weight' => $grosswt_sum]);
                                        }
                                    }
                                }
                            }
                        } else {
                            $consignmentsave['total_quantity'] = $request->total_quantity;
                            $consignmentsave['total_weight'] = $request->total_weight;
                            $consignmentsave['total_gross_weight'] = $request->total_gross_weight;
                            $consignmentsave['total_freight'] = $request->total_freight;
                            $saveconsignment = ConsignmentNote::create($consignmentsave);

                            if (!empty($request->data)) {
                                $get_data = $request->data;
                                foreach ($get_data as $key => $save_data) {
                                    $save_data['consignment_id'] = $saveconsignment->id;
                                    $save_data['status'] = 1;
                                    $saveconsignmentitems = ConsignmentItem::create($save_data);
                                }
                            }
                        }
                    } else {

                        $consignmentsave['h2h_check'] = 'h2h';
                        $consignmentsave['hrs_status'] = 2;

                        if ($request->invoice_check == 1 || $request->invoice_check == 2) {
                            $saveconsignment = ConsignmentNote::create($consignmentsave);
                            if (!empty($request->data)) {
                                $get_data = $request->data;
                                foreach ($get_data as $key => $save_data) {
                                    $save_data['consignment_id'] = $saveconsignment->id;
                                    $save_data['status'] = 1;
                                    $saveconsignmentitems = ConsignmentItem::create($save_data);

                                    if ($saveconsignmentitems) {
                                        // dd($save_data['item_data']);
                                        if (!empty($save_data['item_data'])) {
                                            $qty_array = array();
                                            $netwt_array = array();
                                            $grosswt_array = array();
                                            $chargewt_array = array();
                                            foreach ($save_data['item_data'] as $key => $save_itemdata) {
                                                // echo "<pre>"; print_r($save_itemdata); die;
                                                $qty_array[] = $save_itemdata['quantity'];
                                                $netwt_array[] = $save_itemdata['net_weight'];
                                                $grosswt_array[] = $save_itemdata['gross_weight'];
                                                $chargewt_array[] = $save_itemdata['chargeable_weight'];

                                                $save_itemdata['conitem_id'] = $saveconsignmentitems->id;
                                                $save_itemdata['status'] = 1;

                                                $savesubitems = ConsignmentSubItem::create($save_itemdata);
                                            }

                                            $quantity_sum = array_sum($qty_array);
                                            $netwt_sum = array_sum($netwt_array);
                                            $grosswt_sum = array_sum($grosswt_array);
                                            $chargewt_sum = array_sum($chargewt_array);

                                            ConsignmentItem::where('id', $savesubitems->conitem_id)->update(['quantity' => $quantity_sum, 'weight' => $netwt_sum, 'gross_weight' => $grosswt_sum, 'chargeable_weight' => $chargewt_sum]);

                                            ConsignmentNote::where('id', $saveconsignment->id)->update(['total_quantity' => $quantity_sum, 'total_weight' => $netwt_sum, 'total_gross_weight' => $grosswt_sum]);
                                        }
                                    }
                                }
                            }
                        } else {
                            $consignmentsave['total_quantity'] = $request->total_quantity;
                            $consignmentsave['total_weight'] = $request->total_weight;
                            $consignmentsave['total_gross_weight'] = $request->total_gross_weight;
                            $consignmentsave['total_freight'] = $request->total_freight;
                            $saveconsignment = ConsignmentNote::create($consignmentsave);

                            if (!empty($request->data)) {
                                $get_data = $request->data;
                                foreach ($get_data as $key => $save_data) {
                                    $save_data['consignment_id'] = $saveconsignment->id;
                                    $save_data['status'] = 1;
                                    $saveconsignmentitems = ConsignmentItem::create($save_data);
                                }
                            }
                        }
                    }
                } else {
                    //regular same flow
                    //h2h branch check
                    if ($request->invoice_check == 1 || $request->invoice_check == 2) {
                        $saveconsignment = ConsignmentNote::create($consignmentsave);
                        if (!empty($request->data)) {
                            $get_data = $request->data;
                            foreach ($get_data as $key => $save_data) {
                                $save_data['consignment_id'] = $saveconsignment->id;
                                $save_data['status'] = 1;
                                $saveconsignmentitems = ConsignmentItem::create($save_data);
                                if ($saveconsignmentitems) {
                                    // dd($save_data['item_data']);
                                    if (!empty($save_data['item_data'])) {
                                        $qty_array = array();
                                        $netwt_array = array();
                                        $grosswt_array = array();
                                        $chargewt_array = array();
                                        foreach ($save_data['item_data'] as $key => $save_itemdata) {
                                            // echo "<pre>"; print_r($save_itemdata); die;
                                            $qty_array[] = $save_itemdata['quantity'];
                                            $netwt_array[] = $save_itemdata['net_weight'];
                                            $grosswt_array[] = $save_itemdata['gross_weight'];
                                            $chargewt_array[] = $save_itemdata['chargeable_weight'];

                                            $save_itemdata['conitem_id'] = $saveconsignmentitems->id;
                                            $save_itemdata['status'] = 1;

                                            $savesubitems = ConsignmentSubItem::create($save_itemdata);
                                        }
                                        $quantity_sum = array_sum($qty_array);
                                        $netwt_sum = array_sum($netwt_array);
                                        $grosswt_sum = array_sum($grosswt_array);
                                        $chargewt_sum = array_sum($chargewt_array);

                                        ConsignmentItem::where('id', $savesubitems->conitem_id)->update(['quantity' => $quantity_sum, 'weight' => $netwt_sum, 'gross_weight' => $grosswt_sum, 'chargeable_weight' => $chargewt_sum]);

                                        ConsignmentNote::where('id', $saveconsignment->id)->update(['total_quantity' => $quantity_sum, 'total_weight' => $netwt_sum, 'total_gross_weight' => $grosswt_sum]);
                                    }
                                }
                            }
                        }
                    } else {
                        $consignmentsave['total_quantity'] = $request->total_quantity;
                        $consignmentsave['total_weight'] = $request->total_weight;
                        $consignmentsave['total_gross_weight'] = $request->total_gross_weight;
                        $consignmentsave['total_freight'] = $request->total_freight;
                        $saveconsignment = ConsignmentNote::create($consignmentsave);

                        if (!empty($request->data)) {
                            $get_data = $request->data;
                            foreach ($get_data as $key => $save_data) {
                                $save_data['consignment_id'] = $saveconsignment->id;
                                $save_data['status'] = 1;
                                $saveconsignmentitems = ConsignmentItem::create($save_data);
                            }
                        }
                    }
                }
            } else {
                //regular same flow
                //h2h branch check
                if ($request->invoice_check == 1 || $request->invoice_check == 2) {
                    $saveconsignment = ConsignmentNote::create($consignmentsave);
                    if (!empty($request->data)) {
                        $get_data = $request->data;
                        foreach ($get_data as $key => $save_data) {

                            $save_data['consignment_id'] = $saveconsignment->id;
                            $save_data['status'] = 1;
                            $saveconsignmentitems = ConsignmentItem::create($save_data);

                            if ($saveconsignmentitems) {
                                // dd($save_data['item_data']);
                                if (!empty($save_data['item_data'])) {
                                    $qty_array = array();
                                    $netwt_array = array();
                                    $grosswt_array = array();
                                    $chargewt_array = array();
                                    foreach ($save_data['item_data'] as $key => $save_itemdata) {
                                        // echo "<pre>"; print_r($save_itemdata); die;
                                        $qty_array[] = $save_itemdata['quantity'];
                                        $netwt_array[] = $save_itemdata['net_weight'];
                                        $grosswt_array[] = $save_itemdata['gross_weight'];
                                        $chargewt_array[] = $save_itemdata['chargeable_weight'];

                                        $save_itemdata['conitem_id'] = $saveconsignmentitems->id;
                                        $save_itemdata['status'] = 1;

                                        $savesubitems = ConsignmentSubItem::create($save_itemdata);
                                    }
                                    $quantity_sum = array_sum($qty_array);
                                    $netwt_sum = array_sum($netwt_array);
                                    $grosswt_sum = array_sum($grosswt_array);
                                    $chargewt_sum = array_sum($chargewt_array);

                                    ConsignmentItem::where('id', $savesubitems->conitem_id)->update(['quantity' => $quantity_sum, 'weight' => $netwt_sum, 'gross_weight' => $grosswt_sum, 'chargeable_weight' => $chargewt_sum]);

                                    ConsignmentNote::where('id', $saveconsignment->id)->update(['total_quantity' => $quantity_sum, 'total_weight' => $netwt_sum, 'total_gross_weight' => $grosswt_sum]);
                                }
                            }
                        }
                    }
                } else {
                    $consignmentsave['total_quantity'] = $request->total_quantity;
                    $consignmentsave['total_weight'] = $request->total_weight;
                    $consignmentsave['total_gross_weight'] = $request->total_gross_weight;
                    $consignmentsave['total_freight'] = $request->total_freight;
                    $saveconsignment = ConsignmentNote::create($consignmentsave);

                    if (!empty($request->data)) {
                        $get_data = $request->data;
                        foreach ($get_data as $key => $save_data) {
                            $save_data['consignment_id'] = $saveconsignment->id;
                            $save_data['status'] = 1;
                            $saveconsignmentitems = ConsignmentItem::create($save_data);
                        }
                    }
                }
            }
            /////////////////// ///////////////////////////////////////// drs api push/////////////////////////////////////////////

            $consignment_id = $saveconsignment->id;
            //===================== Create DRS in LR ================================= //

            if (!empty($request->vehicle_id)) {
                $consignmentdrs = DB::table('consignment_notes')->select('consignment_notes.*', 'consigners.nick_name as consigner_name', 'consignees.nick_name as consignee_name', 'consignees.city as city', 'consignees.postal_code as pincode', 'vehicles.regn_no as regn_no', 'drivers.name as driver_name', 'drivers.phone as driver_phone')
                    ->join('consigners', 'consigners.id', '=', 'consignment_notes.consigner_id')
                    ->join('consignees', 'consignees.id', '=', 'consignment_notes.consignee_id')
                    ->leftjoin('vehicles', 'vehicles.id', '=', 'consignment_notes.vehicle_id')
                    ->leftjoin('drivers', 'drivers.id', '=', 'consignment_notes.driver_id')
                    ->where('consignment_notes.id', $consignment_id)
                    ->first(['consignees.city']);
                $simplyfy = json_decode(json_encode($consignmentdrs), true);

                $no_of_digit = 5;
                $drs = DB::table('transaction_sheets')->select('drs_no')->latest('drs_no')->first();
                $drs_no = json_decode(json_encode($drs), true);
                if (empty($drs_no) || $drs_no == null) {
                    $drs_no['drs_no'] = 0;
                }
                $number = $drs_no['drs_no'] + 1;
                $drs_no = str_pad($number, $no_of_digit, "0", STR_PAD_LEFT);

                $transaction = DB::table('transaction_sheets')->insert(['drs_no' => $drs_no, 'consignment_no' => $simplyfy['id'], 'consignee_id' => $simplyfy['consignee_name'], 'consignment_date' => $simplyfy['consignment_date'], 'branch_id' => $authuser->branch_id, 'city' => $simplyfy['city'], 'pincode' => $simplyfy['pincode'], 'total_quantity' => $simplyfy['total_quantity'], 'total_weight' => $simplyfy['total_weight'], 'vehicle_no' => $simplyfy['regn_no'], 'driver_name' => $simplyfy['driver_name'], 'driver_no' => $simplyfy['driver_phone'], 'order_no' => '1', 'delivery_status' => 'Assigned', 'status' => '1']);
            }
            //===========================End drs lr ================================= //
            // if ($saveconsignment) {

            /******* PUSH LR to Shadow if vehicle available & Driver has team & fleet ID   ********/
            $vn = $consignmentsave['vehicle_id'];
            $lid = $saveconsignment->id;
            $lrdata = DB::table('consignment_notes')->select('consignment_notes.*', 'consigners.nick_name as consigner_id', 'consignees.nick_name as consignee_name', 'consignees.phone as phone', 'consignees.email as email', 'vehicles.regn_no as vehicle_id', 'consignees.city as city', 'consignees.postal_code as pincode', 'drivers.name as driver_id', 'drivers.phone as driver_phone', 'drivers.team_id as team_id', 'drivers.fleet_id as fleet_id')
                ->join('consigners', 'consigners.id', '=', 'consignment_notes.consigner_id')
                ->join('consignees', 'consignees.id', '=', 'consignment_notes.consignee_id')
                ->join('vehicles', 'vehicles.id', '=', 'consignment_notes.vehicle_id')
                ->join('drivers', 'drivers.id', '=', 'consignment_notes.driver_id')
                ->where('consignment_notes.id', $lid)
                ->get();
            $simplyfy = json_decode(json_encode($lrdata), true);
            //echo "<pre>";print_r($simplyfy);die;
            //Send Data to API

            if (($request->edd) >= $request->consignment_date) {
                if (!empty($vn) && !empty($simplyfy[0]['team_id']) && !empty($simplyfy[0]['fleet_id'])) {
                    $createTask = $this->createTookanTasks($simplyfy);
                    $json = json_decode($createTask[0], true);
                    $job_id = $json['data']['job_id'];
                    $tracking_link = $json['data']['tracking_link'];
                    $update = DB::table('consignment_notes')->where('id', $lid)->update(['job_id' => $job_id, 'tracking_link' => $tracking_link]);
                }
            }

            $url = $this->prefix . '/consignments';
            $response['success'] = true;
            $response['success_message'] = "Consignment Added successfully";
            $response['error'] = false;
            // $response['resetform'] = true;
            $response['page'] = 'create-consignment';
            $response['redirect_url'] = $url;

            DB::commit();
        } catch (Exception $e) {
            $response['error'] = false;
            $response['error_message'] = $e;
            $response['success'] = false;
            $response['redirect_url'] = $url;
        }
        return response()->json($response);
    }

    public function exportPodFile(Request $request)
    {
        return Excel::download(new PodExport($request->startdate, $request->enddate), 'Pod.xlsx');
    }

    public function updatePod(Request $request)
    {
        try {

            $lr_no = $request->lr_no;
            $file = $request->file('pod');
            if (!empty($file)) {
                $filename = $file->getClientOriginalName();
                $file->move(public_path('drs/Image'), $filename);
            } else {
                $filename = null;
            }
            ConsignmentNote::where('id', $lr_no)->update(['signed_drs' => $filename, 'delivery_status' => 'Successful', 'delivery_date' => $request->delivery_date]);

            $response['success'] = true;
            $response['messages'] = 'POD uploaded successfully';
            return Response::json($response);
        } catch (\Exception $e) {
            $bug = $e->getMessage();
            $response['success'] = false;
            $response['messages'] = $bug;
            return Response::json($response);
        }
    }

    public function changePodMode(Request $request)
    {
        $lr_no = $request->lr_id;
        $mode = ConsignmentNote::where('id', $lr_no)->update(['change_mode_remarks' => $request->reason_to_change_mode, 'lr_mode' => 0, 'delivery_status' => 'Started', 'delivery_date' => null]);

        if ($mode) {
            $response['success'] = true;
            $response['messages'] = 'Mode Change successfully';
        } else {
            $response['success'] = false;
            $response['messages'] = 'Mode Change failed';
        }
        return Response::json($response);
    }

    public function deletePodStatus(Request $request)
    {

        $lr_no = $request->lr_id;
        $mode = ConsignmentNote::where('id', $lr_no)->update(['delivery_date' => null, 'delivery_status' => 'Started', 'signed_drs' => null]);

        if ($mode) {
            $response['success'] = true;
            $response['messages'] = 'POD Remove';
        } else {
            $response['success'] = false;
            $response['messages'] = 'failed';
        }
        return Response::json($response);
    }

    public function cropList(Request $request)
    {
        $this->prefix = request()->route()->getPrefix();
        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $baseclient = explode(',', $authuser->baseclient_id);
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);
        $user = User::where('branch_id', $authuser->branch_id)->where('role_id', 2)->first();

        $crops = Crop::where('status', 1)->get();
        return view('consignments.crop-list', ['prefix' => $this->prefix, 'title' => $this->title, 'crops' => $crops]);
    }
    public function addCrop(Request $request)
    {

        try {
            DB::beginTransaction();

            $this->prefix = request()->route()->getPrefix();
            $rules = array(
                'crop_name' => 'required|unique:crops',
            );
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $errors = $validator->errors();
                $response['success'] = false;
                $response['validation'] = false;
                $response['formErrors'] = true;
                $response['error_message'] = $errors;
                return response()->json($response);
            }

            $cropupload = $request->file('crop_image');
            $path = Storage::disk('s3')->put('crops', $cropupload);
            $crop_img_path_save = Storage::disk('s3')->url($path);

            $gstsave['crop_name'] = $request->crop_name;
            $gstsave['crop_price'] = $request->crop_price;
            $gstsave['crop_image'] = $crop_img_path_save;
            $gstsave['water_qty'] = $request->save_water;
            $gstsave['status'] = 1;

            $gstsave = Crop::create($gstsave);

            if ($gstsave) {
                $url = $this->prefix . '/settings/branch-address';
                $response['success'] = true;
                $response['success_message'] = "Crop Added successfully";
                $response['error'] = false;
                $response['redirect_url'] = $url;
            } else {
                $response['success'] = false;
                $response['error_message'] = "Can not created Vendor please try again";
                $response['error'] = true;
            }
            DB::commit();
        } catch (Exception $e) {
            $response['error'] = false;
            $response['error_message'] = $e;
            $response['success'] = false;
            $response['redirect_url'] = $url;
        }
        return response()->json($response);
    }

    public function updateCrop(Request $request)
    {

        try {
            DB::beginTransaction();

            $gstsave = Crop::where('id', $request->crop_id)->update(['crop_price' => $request->crop_price, 'water_qty' => $request->save_water]);
            if ($gstsave) {
                $url = $this->prefix . '/settings/branch-address';
                $response['success'] = true;
                $response['success_message'] = "Crop Updated successfully";
                $response['error'] = false;
                $response['redirect_url'] = $url;
            } else {
                $response['success'] = false;
                $response['error_message'] = "Can not created Vendor please try again";
                $response['error'] = true;
            }
            DB::commit();
        } catch (Exception $e) {
            $response['error'] = false;
            $response['error_message'] = $e;
            $response['success'] = false;
            $response['redirect_url'] = $url;
        }
        return response()->json($response);
    }

    public function getCropPrice(Request $request)
    {
        $get_crop = Crop::where('id', $request->crop_id)->first();

        $response['success'] = true;
        $response['get_crop'] = $get_crop;
        $response['success_message'] = 'Farmer Fetch successfully';
        $response['error'] = false;
        return response()->json($response);
    }

    public function orderList(Request $request)
    {
        $this->prefix = request()->route()->getPrefix();
        $peritem = Config::get('variable.PER_PAGE');
        $query = ConsignmentNote::query();

        if ($request->ajax()) {
            if (isset($request->resetfilter)) {
                Session::forget('peritem');
                $url = URL::to($this->prefix . '/' . $this->segment);
                return response()->json(['success' => true, 'redirect_url' => $url]);
            }
            if (isset($request->updatestatus)) {
                ConsignmentNote::where('id', $request->id)->update(['status' => $request->status, 'reason_to_cancel' => $request->reason_to_cancel, 'delivery_status' => 'Cancel']);
                ConsignmentItem::where('consignment_id', $request->id)->update(['status' => $request->status]);

                $url = $this->prefix . '/consignments';
                $response['success'] = true;
                $response['success_message'] = "Consignment updated successfully";
                $response['error'] = false;
                $response['page'] = 'consignment-updateupdate';
                $response['redirect_url'] = $url;

                return response()->json($response);
            }

            $authuser = Auth::user();
            $role_id = Role::where('id', '=', $authuser->role_id)->first();
            $baseclient = explode(',', $authuser->baseclient_id);
            $regclient = explode(',', $authuser->regionalclient_id);
            $cc = explode(',', $authuser->branch_id);

            $query = $query->where('status', '!=', 5)->with('ConsignmentItems', 'ConsignerDetail', 'ConsigneeDetail', 'VehicleDetail', 'DriverDetail', 'JobDetail', 'Crop', 'fallIn');

            if ($authuser->role_id == 1) {
                $query;
            } elseif ($authuser->role_id == 4) {
                $query = $query->whereIn('regclient_id', $regclient);
            } elseif ($authuser->role_id == 7) {
                $query = $query->where('user_id', $authuser->id);
            } else {
                $query = $query->whereIn('branch_id', $cc);
            }

            if (!empty($request->search)) {
                $search = $request->search;
                $searchT = str_replace("'", "", $search);
                $query->where(function ($query) use ($search, $searchT) {
                    $query->where('id', 'like', '%' . $search . '%')
                        ->orWhereHas('ConsignerDetail.GetRegClient', function ($regclientquery) use ($search) {
                            $regclientquery->where('name', 'like', '%' . $search . '%');
                        })
                        ->orWhereHas('ConsignerDetail', function ($query) use ($search, $searchT) {
                            $query->where(function ($cnrquery) use ($search, $searchT) {
                                $cnrquery->where('nick_name', 'like', '%' . $search . '%');
                            });
                        })
                        ->orWhereHas('ConsigneeDetail', function ($query) use ($search, $searchT) {
                            $query->where(function ($cneequery) use ($search, $searchT) {
                                $cneequery->where('nick_name', 'like', '%' . $search . '%');
                            });
                        });
                });
                // ->orWhereHas('ConsignmentItem',function( $query ) use($search,$searchT){
                //     $query->where(function ($invcquery)use($search,$searchT) {
                //         $invcquery->where('invoice_no', 'like', '%' . $search . '%');
                //     });
                // });

                // });
            }

            if ($request->peritem) {
                Session::put('peritem', $request->peritem);
            }

            $peritem = Session::get('peritem');
            if (!empty($peritem)) {
                $peritem = $peritem;
            } else {
                $peritem = Config::get('variable.PER_PAGE');
            }

            $consignments = $query->orderBy('id', 'DESC')->paginate($peritem);
            $consignments = $consignments->appends($request->query());

            $html = view('consignments.order-list-ajax', ['prefix' => $this->prefix, 'consignments' => $consignments, 'peritem' => $peritem])->render();

            return response()->json(['html' => $html]);
        }

        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $baseclient = explode(',', $authuser->baseclient_id);
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);

        $query = $query->where('status', '!=', 5)->with('ConsignmentItems', 'ConsignerDetail', 'ConsigneeDetail', 'VehicleDetail', 'DriverDetail', 'JobDetail');

        if ($authuser->role_id == 1) {
            $query;
        } elseif ($authuser->role_id == 4) {
            $query = $query->whereIn('regclient_id', $regclient);
        } elseif ($authuser->role_id == 7) {
            $query = $query->where('user_id', $authuser->id);
        } else {

            $query = $query;
            // $query = $query->whereIn('branch_id', $cc)->orWhereIn('fall_in', $cc);
        }
        $consignments = $query->orderBy('id', 'DESC')->paginate($peritem);
        $consignments = $consignments->appends($request->query());

        return view('consignments.order-list', ['consignments' => $consignments, 'peritem' => $peritem, 'prefix' => $this->prefix, 'segment' => $this->segment]);
    }

    public function uploadNoc(Request $request)
    {

        if (!empty($request->file('noc_upload'))) {
            $nocupload = $request->file('noc_upload');
            $path = Storage::disk('s3')->put('agri-wings/noc', $nocupload);
            $noc_file_name = Storage::disk('s3')->url($path);
            $url_chng = explode(':', $noc_file_name);
            $url_chng = $url_chng[0] . 's:' . $url_chng[1];
        } else {
            $url_chng = null;
        }

        $uploadNoc = ConsignmentNote::where('id', $request->order_id)->update(['noc_upload' => $url_chng]);

        if ($uploadNoc) {
            $response['success'] = true;
            $response['success_message'] = 'Noc Uploaded successfully';
            $response['error'] = false;
        } else {
            $response['success'] = false;
            $response['success_message'] = 'Something went wrong';
            $response['error'] = true;
        }

        return response()->json($response);
    }

    public function nocPrint(Request $request, $id)
    {

        $data = [
            'date' => date('Y-m-d'), // Replace this with your desired date format
            'ownerName' => 'Your Owner Name', // Replace this with the actual owner's name
            'relativeName' => 'Your Relative Name', // Replace this with the actual relative's name
            // Add more dynamic data as needed...
        ];

        $html = view('noc-template', $data)->render();

        $pdf = \App::make('dompdf.wrapper');
        // Set the font directory for Dompdf
        $fontDirectory = public_path('font');
        $pdf->getDomPDF()->getOptions()->set('fontDir', $fontDirectory);
        $pdf->getDomPDF()->getOptions()->set('fontCache', $fontDirectory);
        $pdf->loadHTML($html);
        $pdf->setPaper('legal', 'portrait');
        return $pdf->download('Noc.pdf');
    }

    public function batteryList(Request $request)
    {
        $this->prefix = request()->route()->getPrefix();
        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $baseclient = explode(',', $authuser->baseclient_id);
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);
        $user = User::where('branch_id', $authuser->branch_id)->where('role_id', 2)->first();

        $batterys = Battery::where('status', 1)->get();
        return view('consignments.battery-list', ['prefix' => $this->prefix, 'title' => $this->title, 'batterys' => $batterys]);
    }

    public function addBattery(Request $request)
    {

        try {
            DB::beginTransaction();

            $this->prefix = request()->route()->getPrefix();
            $rules = array(
                // 'crop_name' => 'required|unique:crops',
            );
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $errors = $validator->errors();
                $response['success'] = false;
                $response['validation'] = false;
                $response['formErrors'] = true;
                $response['error_message'] = $errors;
                return response()->json($response);
            }

            $batterysave['battery_no'] = $request->battery_no;
            $batterysave['type'] = $request->type;
            $batterysave['est_acers'] = $request->est_acers;
            $batterysave['battery_cycle'] = $request->battery_cycle;

            $gstsave = Battery::create($batterysave);

            if ($gstsave) {
                $url = $this->prefix . '/settings/branch-address';
                $response['success'] = true;
                $response['success_message'] = "Battery Added successfully";
                $response['error'] = false;
                $response['redirect_url'] = $url;
            } else {
                $response['success'] = false;
                $response['error_message'] = "Can not created Vendor please try again";
                $response['error'] = true;
            }
            DB::commit();
        } catch (Exception $e) {
            $response['error'] = false;
            $response['error_message'] = $e;
            $response['success'] = false;
            $response['redirect_url'] = $url;
        }
        return response()->json($response);
    }

    public function updateBattery(Request $request)
    {

        try {
            DB::beginTransaction();

            $this->prefix = request()->route()->getPrefix();

            $gstsave = Battery::where('id', $request->battery_id)->update(['battery_no' => $request->battery_no, 'type' => $request->type, 'est_acers' => $request->est_acers, 'battery_cycle' => $request->battery_cycle]);

            if ($gstsave) {
                $url = $this->prefix . '/settings/branch-address';
                $response['success'] = true;
                $response['success_message'] = "Battery Updated successfully";
                $response['error'] = false;
                $response['redirect_url'] = $url;
            } else {
                $response['success'] = false;
                $response['error_message'] = "Can not created Vendor please try again";
                $response['error'] = true;
            }
            DB::commit();
        } catch (Exception $e) {
            $response['error'] = false;
            $response['error_message'] = $e;
            $response['success'] = false;
            $response['redirect_url'] = $url;
        }
        return response()->json($response);
    }

    public function SendTSMS($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_POST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // change to 1 to verify cert
        curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
        $result = curl_exec($ch);
    }
    public function sendNotification($request)
    {

        $firebaseToken = Driver::where('id', $request)->whereNotNull('device_token')->pluck('device_token')->all();

        $SERVER_API_KEY = "AAAAfCjqgg4:APA91bFkJDq6mhpjpyW3EW4EdmZt1iFbpqQbn_BVL3sTIKMiXqCcQaE3e4xFyxmwzysz1ZxK-gAGzJQQFJ_woaRRs6y0iePnyGZpIUV5sCngu80DAYjqFs8b1XXFsLj5Z5Ab1oXLywhC";

        $data_json = ['type' => 'Assigned', 'status' => 1];

        $data = [
            "registration_ids" => $firebaseToken,
            "notification" => [
                "title" => "Task Assigned",
                "body" => "New Task assigned to you, please check",
            ],
            "data" => $data_json,
        ];
        $dataString = json_encode($data);

        $headers = [
            'Authorization: key=' . $SERVER_API_KEY,
            'Content-Type: application/json',
        ];

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, 'https://fcm.googleapis.com/fcm/send');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataString);

        $response = curl_exec($ch);

        return $response;
    }

    public function cropSchemeList(Request $request)
    {
        $this->prefix = request()->route()->getPrefix();
        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $baseclient = explode(',', $authuser->baseclient_id);
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);
        $user = User::where('branch_id', $authuser->branch_id)->where('role_id', 2)->first();

        $crop_price_schemes = CropPriceScheme::with('Crops', 'BaseClient')->orderBy('id', 'desc')->get();
        $crops = Crop::where('status', 1)->get();
        $base_clients = BaseClient::where('status', 1)->get();
        return view('consignments.crop-price-scheme', ['prefix' => $this->prefix, 'title' => $this->title, 'crop_price_schemes' => $crop_price_schemes, 'crops' => $crops, 'base_clients' => $base_clients]);
    }

    public function addCropScheme(Request $request)
    {

        try {
            DB::beginTransaction();

            $this->prefix = request()->route()->getPrefix();
            $rules = array(
                // 'crop_name' => 'required|unique:crops',
            );
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $errors = $validator->errors();
                $response['success'] = false;
                $response['validation'] = false;
                $response['formErrors'] = true;
                $response['error_message'] = $errors;
                return response()->json($response);
            }

            // $check_old_scheme = CropPriceScheme::where('crop_id', $request->crop_id)->update(['status' => 0]);
            $crop_scheme = DB::table('crop_price_schemes')->select('scheme_no')->latest('scheme_no')->first();
            $crop_scheme_no = json_decode(json_encode($crop_scheme), true);

            if (empty($crop_scheme_no['scheme_no']) || $crop_scheme_no['scheme_no'] == null) {
                $scheme_no = 101;
            } else {
                $scheme_no = $crop_scheme_no['scheme_no'] + 1;
            }

            $discountsave['crop_id'] = $request->crop_id;
            $discountsave['scheme_no'] = $scheme_no;
            $discountsave['from_date'] = $request->from_date;
            $discountsave['to_date'] = $request->to_date;
            $discountsave['crop_price'] = $request->crop_price;
            $discountsave['discount_price'] = $request->discount_price;
            $discountsave['min_acerage'] = $request->min;
            $discountsave['max_acerage'] = $request->max;
            $discountsave['type'] = $request->scheme_type;
            $discountsave['name'] = $request->scheme_name;
            $discountsave['client'] = $request->client_id;

            $get_scheme_details = CropPriceScheme::where('crop_id', $request->crop_id)
                ->where('type', $request->scheme_type)
                ->where('client', $request->client_id)
                ->where(function ($query) use ($request) {
                    $query->whereDate('from_date', '<=', $request->from_date)
                        ->whereDate('to_date', '>=', $request->from_date)
                        ->orWhereDate('from_date', '<=', $request->to_date)
                        ->whereDate('to_date', '>=', $request->to_date);
                })
                ->where('status', 1)
                ->orderBy('id', 'desc')
                ->get();

            foreach ($get_scheme_details as $scheme) {
                if (($request->from_date >= $scheme->from_date && $request->from_date <= $scheme->to_date) ||
                    ($request->to_date >= $scheme->from_date && $request->to_date <= $scheme->to_date) ||
                    ($request->from_date <= $scheme->from_date && $request->to_date >= $scheme->to_date)
                ) {
                    if (($request->min >= $scheme->min_acerage && $request->min <= $scheme->max_acerage) ||
                        ($request->max >= $scheme->min_acerage && $request->max <= $scheme->max_acerage) ||
                        ($request->min <= $scheme->min_acerage && $request->max >= $scheme->min_acerage)
                    ) {
                        $response['success'] = false;
                        $response['error_message'] = "Scheme Already Exists for this Range";
                        $response['error'] = true;
                        return response()->json($response);
                    }
                }
            }

            $gstsave = CropPriceScheme::create($discountsave);

            if ($gstsave) {
                $url = $this->prefix . '/settings/branch-address';
                $response['success'] = true;
                $response['success_message'] = "Crop Scheme Added successfully";
                $response['error'] = false;
                $response['redirect_url'] = $url;
            } else {
                $response['success'] = false;
                $response['error_message'] = "Can not created Vendor please try again";
                $response['error'] = true;
            }
            DB::commit();
        } catch (Exception $e) {
            $response['error'] = false;
            $response['error_message'] = $e;
            $response['success'] = false;
            $response['redirect_url'] = $url;
        }
        return response()->json($response);
    }

    public function checkPriceScheme(Request $request)
    {

        $get_client_id = RegionalClient::where('id', $request->client_id)->first();

        $get_client = BaseClient::where('id', $get_client_id->baseclient_id)->first();

        $acerage = $request->acerage;
        $today = date('Y-m-d');

        $get_scheme_details = CropPriceScheme::where('crop_id', $request->crop_id)
            ->where('type', 'Crop Specific')
            ->whereDate('from_date', '<=', $today)
            ->whereDate('to_date', '>=', $today)
            ->where('status', 1)->orderBy('id', 'desc')->get();
        $crop_scheme = array();
        for ($i = 0; $i < sizeof($get_scheme_details); $i++) {
            if ($acerage >= $get_scheme_details[$i]->min_acerage) {

                if ($acerage <= $get_scheme_details[$i]->max_acerage) {
                    $crop_scheme = $get_scheme_details[$i];
                }
            }
        }

        if (!empty($crop_scheme)) {

            $get_scheme_client = CropPriceScheme::where('crop_id', $request->crop_id)
                ->whereIn('type', ['Client Specific', 'Subvention'])
                ->where('client', @$get_client->id)
                ->whereDate('from_date', '<=', $today)
                ->whereDate('to_date', '>=', $today)
                ->where('status', 1)->orderBy('id', 'desc')->get();
            $client_specific = array();
            for ($i = 0; $i < sizeof($get_scheme_client); $i++) {
                if ($acerage >= $get_scheme_client[$i]->min_acerage) {

                    if ($acerage <= $get_scheme_client[$i]->max_acerage) {
                        $client_specific[] = json_decode($get_scheme_client[$i]);
                    }
                }
            }

            $all_scheme = array_push($client_specific, json_decode($crop_scheme));

            $response['success'] = true;
            $response['get_scheme_details'] = $client_specific;
            $response['error'] = false;
            return response()->json($response);
        } else {
            if (!empty($get_client->id)) {
                $get_scheme_client = CropPriceScheme::where('crop_id', $request->crop_id)
                    ->whereIn('type', ['Client Specific', 'Subvention'])
                    ->where('client', $get_client->id)
                    ->whereDate('from_date', '<=', $today)
                    ->whereDate('to_date', '>=', $today)
                    ->where('status', 1)->orderBy('id', 'desc')->get();
                $client_specific = array();
                for ($i = 0; $i < sizeof($get_scheme_client); $i++) {
                    if ($acerage >= $get_scheme_client[$i]->min_acerage) {

                        if ($acerage <= $get_scheme_client[$i]->max_acerage) {
                            $client_specific[] = json_decode($get_scheme_client[$i]);
                        }
                    }
                }
            }
            if (!empty($client_specific)) {

                array_push($client_specific, $crop_scheme);

                $response['success'] = true;
                $response['get_scheme_details'] = $client_specific;
                $response['error'] = false;
                return response()->json($response);
            }

            $response['success'] = true;
            $response['get_scheme_details'] = '';
            $response['error'] = false;
            return response()->json($response);
        }
    }

    public function rating(Request $request, $id)
    {
        $check_feedback = Feedback::where('order_id', $id)->first();
        if (empty($check_feedback)) {
            return view('rating', ['order_id' => $id]);
        } else {
            return view('already-feedback', ['order_id' => $id]);
        }
    }

    public function addFeedback(Request $request)
    {
        try {
            DB::beginTransaction();

            $this->prefix = request()->route()->getPrefix();
            $rules = array(
                // 'crop_name' => 'required|unique:crops',
            );
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $errors = $validator->errors();
                $response['success'] = false;
                $response['validation'] = false;
                $response['formErrors'] = true;
                $response['error_message'] = $errors;
                return response()->json($response);
            }

            $feedbacksave['feedback'] = $request->feedback;
            $feedbacksave['stars'] = $request->stars;
            $feedbacksave['order_id'] = $request->order_id;

            $savefeedback = Feedback::create($feedbacksave);

            if ($savefeedback) {
                $response['success'] = true;
                $response['success_message'] = "Feedback Added successfully";
                $response['error'] = false;
            } else {
                $response['success'] = false;
                $response['error_message'] = "Can not created Vendor please try again";
                $response['error'] = true;
            }
            DB::commit();
        } catch (Exception $e) {
            $response['error'] = false;
            $response['error_message'] = $e;
            $response['success'] = false;
            $response['redirect_url'] = $url;
        }
        return response()->json($response);
    }

    public function downloadApp()
    {

        return view('app-download');
    }

    public function downloadAppFile()
    {
        $path = public_path('assets/agri-app.apk');
        return response()->file($path, [
            'Content-Type' => 'application/vnd.android.package-archive',
            'Content-Disposition' => 'attachment; filename="android.apk"',
        ]);
    }

    public function downloadPPTFile()
    {
        $path = public_path('assets/CSO-Policy.pdf');
        return response()->download($path);
    }

    public function orderEditacerage(Request $request)
    {
        try {
            DB::beginTransaction();
            $order_details = ConsignmentNote::with('Orderactivity')->where('id', $request->order_id)->first();
            $crop_price = $order_details->Orderactivity->base_price * $request->acerage;

            $all_discount_type = $order_details->Orderactivity->discount_price + $order_details->Orderactivity->client_specific + $order_details->Orderactivity->subvention;

            $discount_price = $all_discount_type * $request->acerage;
            $cropPriceWithDiscount = $order_details->Orderactivity->base_price - $all_discount_type;
            $totalPrice = $crop_price - $discount_price;

            $updateOrderFarm = OrderFarm::where('order_id', $request->order_id)->update(['acreage' => $request->acerage, 'crop_price' => $crop_price, 'discount' => $discount_price, 'total_price' => $totalPrice]);

            if ($updateOrderFarm) {

                ConsignmentNote::where('id', $request->order_id)->update(['total_amount' => $totalPrice, 'total_acerage' => $request->acerage, 'acerage_reason' => $request->remarks]);
                $response['success'] = true;
                $response['success_message'] = "Acerage Updated successfully";
                $response['error'] = false;
            } else {
                $response['success'] = false;
                $response['error_message'] = "Can not created Vendor please try again";
                $response['error'] = true;
            }

            DB::commit();
        } catch (Exception $e) {
            $response['error'] = false;
            $response['error_message'] = $e;
            $response['success'] = false;
            $response['redirect_url'] = $url;
        }
        return response()->json($response);
    }

    public function deactivateScheme(Request $request)
    {
        try {
            DB::beginTransaction();

            $this->prefix = request()->route()->getPrefix();

            $deactivate_scheme = CropPriceScheme::where('id', $request->crop_id)->update(['status' => 0]);

            if ($deactivate_scheme) {
                $response['success'] = true;
                $response['success_message'] = "Scheme Deactivated";
                $response['error'] = false;
            } else {
                $response['success'] = false;
                $response['error_message'] = "Can not please try again";
                $response['error'] = true;
            }
            DB::commit();
        } catch (Exception $e) {
            $response['error'] = false;
            $response['error_message'] = $e;
            $response['success'] = false;
            $response['redirect_url'] = $url;
        }
        return response()->json($response);
    }

    // ================================ Payment Settlement Function ============================== //

    public function paymentSettlement(Request $request)
    {

        $this->prefix = request()->route()->getPrefix();
        $sessionperitem = Session::get('peritem');
        if (!empty($sessionperitem)) {
            $peritem = $sessionperitem;
        } else {
            $peritem = Config::get('variable.PER_PAGE');
        }

        $query = ConsignmentNote::query();

        if ($request->ajax()) {
            $searchids = [];

            if (isset($request->resetfilter)) {
                Session::forget('searchvehicle');
                Session::forget('peritem');
                $url = URL::to($this->prefix . '/' . $this->segment);
                return response()->json(['success' => true, 'redirect_url' => $url]);
            }

            $authuser = Auth::user();
            $role_id = Role::where('id', '=', $authuser->role_id)->first();
            $regclient = explode(',', $authuser->regionalclient_id);
            $cc = explode(',', $authuser->branch_id);

            $query = $query->with('DriverDetail')->where('status', 1)->whereIn('payment_settlement', [0, 1]);
            $query = $query->whereIn('branch_id', $cc);

            if (!empty($request->search)) {
                $search = $request->search;
                $searchT = str_replace("'", "", $search);
                $query->where(function ($query) use ($search, $searchT) {
                    $query->where('vehicle_no', 'like', '%' . $search . '%')
                        ->orWhere('drs_no', 'like', '%' . $search . '%');
                });
            }

            /// search with vehicle no

            if ($request->searchvehicle) {
                Session::put('searchvehicle', $request->searchvehicle);
            }
            $searchvehicle = Session::get('searchvehicle');
            if (isset($searchvehicle)) {
                $query = $query->whereIn('vehicle_no', $searchvehicle);
            }

            if ($request->peritem) {
                Session::put('peritem', $request->peritem);
            }

            $peritem = Session::get('peritem');
            if (!empty($peritem)) {
                $peritem = $peritem;
            } else {
                $peritem = Config::get('variable.PER_PAGE');
            }

            $vehicles = TransactionSheet::select('vehicle_no')->distinct()->get();

            $paymentlist = $query->orderby('id', 'DESC')->paginate($peritem);

            $html = view('payment-settlement.payment-settlement-ajax', ['prefix' => $this->prefix, 'paymentlist' => $paymentlist, 'peritem' => $peritem, 'vehicles' => $vehicles])->render();
            $paymentlist = $paymentlist->appends($request->query());

            return response()->json(['html' => $html]);
        }

        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);
        $branchs = Location::select('id', 'name')->whereIn('id', $cc)->get();

        $query = $query->with('DriverDetail')->where('status', 1)->whereIn('payment_settlement', [0, 1]);
        if ($authuser->role_id == 7) {
            $query = $query->where('user_id', $authuser->id);
        } elseif ($authuser->role_id == 3) {
            $query = $query->whereIn('branch_id', $cc);
        } else {
            $query = $query;
        }

        $vehicles = TransactionSheet::select('vehicle_no')->distinct()->get();

        $paymentlist = $query->orderBy('id', 'DESC')->paginate($peritem);
        $paymentlist = $paymentlist->appends($request->query());
        // $vehicles    = Vehicle::select('id', 'regn_no')->get();
        $vehicletype = VehicleType::select('id', 'name')->get();

        return view('payment-settlement.payment-settlement', ['prefix' => $this->prefix, 'paymentlist' => $paymentlist, 'peritem' => $peritem, 'vehicles' => $vehicles, 'vehicletype' => $vehicletype, 'branchs' => $branchs]);
    }

    public function createSettlement(Request $request)
    {
        try {
            DB::beginTransaction();
            $authuser = Auth::user();

            $order_id = explode(',', $request->order_no);

            $transaction = DB::table('payment_settlements')->select('transaction_id')->latest('transaction_id')->first();
            $transaction_no = json_decode(json_encode($transaction), true);

            if (empty($transaction_no['transaction_id']) || $transaction_no['transaction_id'] == null) {
                $scheme_no = 90000101;
            } else {
                $scheme_no = $transaction_no['transaction_id'] + 1;
            }

            foreach ($order_id as $order) {

                $get_invoice_price = OrderFarm::where('order_id', $order)->first();

                $all_discount = $get_invoice_price->discount_price + @$get_invoice_price->client_specific + @$get_invoice_price->client_specific->subvention;

                $offer_price = $get_invoice_price->base_price - $all_discount;

                $total_payables = $offer_price * @$get_invoice_price->acreage;

                $paymentdetails['order_id'] = $order;
                $paymentdetails['transaction_id'] = $scheme_no;
                $paymentdetails['date_of_deposite'] = $request->date_of_deposite;
                $paymentdetails['bank_name'] = $request->bank_name;
                $paymentdetails['branch_location'] = $request->branch_location;
                $paymentdetails['transaction_amount'] = $request->amount_deposite;
                $paymentdetails['invoice_amount'] = $total_payables;
                $paymentdetails['user_id'] = $authuser->id;
                $paymentdetails['payment_settlement'] = 1;

                $savefeedback = PaymentSettlement::create($paymentdetails);
            }
            $settled = ConsignmentNote::whereIn('id', $order_id)->update(['payment_settlement' => 1]);

            if ($settled) {
                $response['success'] = true;
                $response['success_message'] = "Payment Settled";
                $response['error'] = false;
            } else {
                $response['success'] = false;
                $response['error_message'] = "Can not please try again";
                $response['error'] = true;
            }

            DB::commit();
        } catch (Exception $e) {
            $response['error'] = false;
            $response['error_message'] = $e;
            $response['success'] = false;
            $response['redirect_url'] = $url;
        }
        return response()->json($response);
    }

    public function paymentSettled(Request $request)
    {

        $this->prefix = request()->route()->getPrefix();
        $sessionperitem = Session::get('peritem');
        if (!empty($sessionperitem)) {
            $peritem = $sessionperitem;
        } else {
            $peritem = Config::get('variable.PER_PAGE');
        }

        $query = ConsignmentNote::query();

        if ($request->ajax()) {
            $searchids = [];

            if (isset($request->resetfilter)) {
                Session::forget('searchvehicle');
                Session::forget('peritem');
                $url = URL::to($this->prefix . '/' . $this->segment);
                return response()->json(['success' => true, 'redirect_url' => $url]);
            }

            $authuser = Auth::user();
            $role_id = Role::where('id', '=', $authuser->role_id)->first();
            $regclient = explode(',', $authuser->regionalclient_id);
            $cc = explode(',', $authuser->branch_id);

            $query = $query->with('DriverDetail')->where('status', 1)->where('payment_settlement', 2);
            $query = $query->whereIn('branch_id', $cc);

            if (!empty($request->search)) {
                $search = $request->search;
                $searchT = str_replace("'", "", $search);
                $query->where(function ($query) use ($search, $searchT) {
                    $query->where('vehicle_no', 'like', '%' . $search . '%')
                        ->orWhere('drs_no', 'like', '%' . $search . '%');
                });
            }

            /// search with vehicle no

            if ($request->searchvehicle) {
                Session::put('searchvehicle', $request->searchvehicle);
            }
            $searchvehicle = Session::get('searchvehicle');
            if (isset($searchvehicle)) {
                $query = $query->whereIn('vehicle_no', $searchvehicle);
            }

            if ($request->peritem) {
                Session::put('peritem', $request->peritem);
            }

            $peritem = Session::get('peritem');
            if (!empty($peritem)) {
                $peritem = $peritem;
            } else {
                $peritem = Config::get('variable.PER_PAGE');
            }

            $vehicles = TransactionSheet::select('vehicle_no')->distinct()->get();

            $paymentlist = $query->orderby('id', 'DESC')->paginate($peritem);

            $html = view('payment-settlement.payment-settled-ajax', ['prefix' => $this->prefix, 'paymentlist' => $paymentlist, 'peritem' => $peritem, 'vehicles' => $vehicles])->render();
            $paymentlist = $paymentlist->appends($request->query());

            return response()->json(['html' => $html]);
        }

        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);
        $branchs = Location::select('id', 'name')->whereIn('id', $cc)->get();

        $query = $query->with('DriverDetail')->where('status', 1)->where('payment_settlement', 2);
        if ($authuser->role_id == 7) {
            $query = $query->where('user_id', $authuser->id);
        } elseif ($authuser->role_id == 3) {
            $query = $query->whereIn('branch_id', $cc);
        } else {
            $query = $query;
        }

        $vehicles = TransactionSheet::select('vehicle_no')->distinct()->get();

        $paymentlist = $query->orderBy('id', 'DESC')->paginate($peritem);
        $paymentlist = $paymentlist->appends($request->query());
        // $vehicles    = Vehicle::select('id', 'regn_no')->get();
        $vehicletype = VehicleType::select('id', 'name')->get();

        return view('payment-settlement.payment-settled', ['prefix' => $this->prefix, 'paymentlist' => $paymentlist, 'peritem' => $peritem, 'vehicles' => $vehicles, 'vehicletype' => $vehicletype, 'branchs' => $branchs]);
    }

    public function verifiePayment(Request $request)
    {
        try {
            DB::beginTransaction();
            $authuser = Auth::user();

            $order_id = explode(',', $request->verify_order_no);

            $pymt_settle = ConsignmentNote::whereIn('id', $order_id)->update(['payment_settlement' => 2]);

            $settled = PaymentSettlement::whereIn('order_id', $order_id)->update(['verify_date' => $request->verify_date, 'payment_id' => $request->payment_id]);

            if ($settled) {
                $response['success'] = true;
                $response['success_message'] = "Payment Verified";
                $response['error'] = false;
            } else {
                $response['success'] = false;
                $response['error_message'] = "Can not please try again";
                $response['error'] = true;
            }

            DB::commit();
        } catch (Exception $e) {
            $response['error'] = false;
            $response['error_message'] = $e;
            $response['success'] = false;
            $response['redirect_url'] = $url;
        }
        return response()->json($response);
    }

    public function subventionOrderList(Request $request)
    {

        $this->prefix = request()->route()->getPrefix();
        $sessionperitem = Session::get('peritem');
        if (!empty($sessionperitem)) {
            $peritem = $sessionperitem;
        } else {
            $peritem = Config::get('variable.PER_PAGE');
        }

        $query = ConsignmentNote::query();

        if ($request->ajax()) {
            $searchids = [];

            if (isset($request->resetfilter)) {
                Session::forget('searchvehicle');
                Session::forget('peritem');
                $url = URL::to($this->prefix . '/' . $this->segment);
                return response()->json(['success' => true, 'redirect_url' => $url]);
            }

            $authuser = Auth::user();
            $role_id = Role::where('id', '=', $authuser->role_id)->first();
            $regclient = explode(',', $authuser->regionalclient_id);
            $cc = explode(',', $authuser->branch_id);

            $query = $query->with('DriverDetail', 'Orderactivity')->where('status', 1)->where('client_settlement', 0)->where('regclient_id', $request->client_id)
                ->whereHas('Orderactivity', function ($query) {
                    $query->where('subvention_id', '!=', null);
                });
            if ($authuser->role_id == 7) {
                $query = $query->where('user_id', $authuser->id);
            } elseif ($authuser->role_id == 3) {
                $query = $query->whereIn('branch_id', $cc);
            } else {
                $query = $query;
            }

            // $query = $query->whereIn('branch_id', $cc);

            if (!empty($request->search)) {
                $search = $request->search;
                $searchT = str_replace("'", "", $search);
                $query->where(function ($query) use ($search, $searchT) {
                    $query->where('vehicle_no', 'like', '%' . $search . '%')
                        ->orWhere('drs_no', 'like', '%' . $search . '%');
                });
            }

            /// search with vehicle no

            if ($request->searchvehicle) {
                Session::put('searchvehicle', $request->searchvehicle);
            }
            $searchvehicle = Session::get('searchvehicle');
            if (isset($searchvehicle)) {
                $query = $query->whereIn('vehicle_no', $searchvehicle);
            }

            if ($request->peritem) {
                Session::put('peritem', $request->peritem);
            }

            $peritem = Session::get('peritem');
            if (!empty($peritem)) {
                $peritem = $peritem;
            } else {
                $peritem = Config::get('variable.PER_PAGE');
            }

            $vehicles = TransactionSheet::select('vehicle_no')->distinct()->get();

            $paymentlist = $query->orderby('id', 'DESC')->paginate($peritem);

            $html = view('payment-settlement.subvention-order-ajax', ['prefix' => $this->prefix, 'paymentlist' => $paymentlist, 'peritem' => $peritem, 'vehicles' => $vehicles])->render();
            $paymentlist = $paymentlist->appends($request->query());

            return response()->json(['html' => $html]);
        }

        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);
        $branchs = Location::select('id', 'name')->whereIn('id', $cc)->get();

        $query = $query->with('DriverDetail', 'Orderactivity')->where('status', 1)->where('client_settlement', 0)
            ->whereHas('Orderactivity', function ($query) {
                $query->where('subvention_id', '!=', null);
            });
        if ($authuser->role_id == 7) {
            $query = $query->where('user_id', $authuser->id);
        } elseif ($authuser->role_id == 3) {
            $query = $query->whereIn('branch_id', $cc);
        } else {
            $query = $query;
        }

        $vehicles = TransactionSheet::select('vehicle_no')->distinct()->get();

        $paymentlist = $query->orderBy('id', 'DESC')->paginate($peritem);
        $paymentlist = $paymentlist->appends($request->query());
        // $vehicles    = Vehicle::select('id', 'regn_no')->get();
        $vehicletype = VehicleType::select('id', 'name')->get();
        $clients = RegionalClient::all();

        return view('payment-settlement.subvention-order', ['prefix' => $this->prefix, 'paymentlist' => $paymentlist, 'peritem' => $peritem, 'vehicles' => $vehicles, 'vehicletype' => $vehicletype, 'branchs' => $branchs, 'clients' => $clients]);
    }

    public function subventionSettlement(Request $request)
    {
        try {
            DB::beginTransaction();
            $authuser = Auth::user();
            $order_id = explode(',', $request->order_no);

            foreach ($order_id as $order) {

                $get_invoice_price = OrderFarm::where('order_id', $order)->first();

                $subvention_discount = $get_invoice_price->subvention * $get_invoice_price->acreage;

                $paymentdetails['order_id'] = $order;
                $paymentdetails['invoice_no'] = $request->invoice_no;
                $paymentdetails['invoice_date'] = $request->invoice_date;
                $paymentdetails['invoice_amount'] = $request->invoice_amount;
                $paymentdetails['subvention_amount'] = $subvention_discount;
                $paymentdetails['user_id'] = $authuser->id;
                $paymentdetails['status'] = 1;

                $savefeedback = SubventionSettlement::create($paymentdetails);
            }
            $settled = ConsignmentNote::whereIn('id', $order_id)->update(['client_settlement' => 1]);

            if ($savefeedback) {
                $response['success'] = true;
                $response['success_message'] = "Payment Settled";
                $response['error'] = false;
            } else {
                $response['success'] = false;
                $response['error_message'] = "Can not please try again";
                $response['error'] = true;
            }

            DB::commit();
        } catch (Exception $e) {
            $response['error'] = false;
            $response['error_message'] = $e;
            $response['success'] = false;
            $response['redirect_url'] = $url;
        }
        return response()->json($response);
    }

    public function subventionSettledList(Request $request)
    {

        $this->prefix = request()->route()->getPrefix();
        $sessionperitem = Session::get('peritem');
        if (!empty($sessionperitem)) {
            $peritem = $sessionperitem;
        } else {
            $peritem = Config::get('variable.PER_PAGE');
        }

        $query = ConsignmentNote::query();

        if ($request->ajax()) {
            $searchids = [];

            if (isset($request->resetfilter)) {
                Session::forget('searchvehicle');
                Session::forget('peritem');
                $url = URL::to($this->prefix . '/' . $this->segment);
                return response()->json(['success' => true, 'redirect_url' => $url]);
            }

            $authuser = Auth::user();
            $role_id = Role::where('id', '=', $authuser->role_id)->first();
            $regclient = explode(',', $authuser->regionalclient_id);
            $cc = explode(',', $authuser->branch_id);

            $query = $query->with('DriverDetail', 'Orderactivity')->where('status', 1)->where('client_settlement', 1)->where('regclient_id', $request->client_id)
                ->whereHas('Orderactivity', function ($query) {
                    $query->where('subvention_id', '!=', null);
                });
            if ($authuser->role_id == 7) {
                $query = $query->where('user_id', $authuser->id);
            } elseif ($authuser->role_id == 3) {
                $query = $query->whereIn('branch_id', $cc);
            } else {
                $query = $query;
            }

            // $query = $query->whereIn('branch_id', $cc);

            if (!empty($request->search)) {
                $search = $request->search;
                $searchT = str_replace("'", "", $search);
                $query->where(function ($query) use ($search, $searchT) {
                    $query->where('vehicle_no', 'like', '%' . $search . '%')
                        ->orWhere('drs_no', 'like', '%' . $search . '%');
                });
            }

            /// search with vehicle no

            if ($request->searchvehicle) {
                Session::put('searchvehicle', $request->searchvehicle);
            }
            $searchvehicle = Session::get('searchvehicle');
            if (isset($searchvehicle)) {
                $query = $query->whereIn('vehicle_no', $searchvehicle);
            }

            if ($request->peritem) {
                Session::put('peritem', $request->peritem);
            }

            $peritem = Session::get('peritem');
            if (!empty($peritem)) {
                $peritem = $peritem;
            } else {
                $peritem = Config::get('variable.PER_PAGE');
            }

            $vehicles = TransactionSheet::select('vehicle_no')->distinct()->get();

            $paymentlist = $query->orderby('id', 'DESC')->paginate($peritem);

            $html = view('payment-settlement.subvention-settled-ajax', ['prefix' => $this->prefix, 'paymentlist' => $paymentlist, 'peritem' => $peritem, 'vehicles' => $vehicles])->render();
            $paymentlist = $paymentlist->appends($request->query());

            return response()->json(['html' => $html]);
        }

        $authuser = Auth::user();
        $role_id = Role::where('id', '=', $authuser->role_id)->first();
        $regclient = explode(',', $authuser->regionalclient_id);
        $cc = explode(',', $authuser->branch_id);
        $branchs = Location::select('id', 'name')->whereIn('id', $cc)->get();

        $query = $query->with('DriverDetail', 'Orderactivity', 'SubventionSettlementDetails')->where('status', 1)->where('client_settlement', 1)
            ->whereHas('Orderactivity', function ($query) {
                $query->where('subvention_id', '!=', null);
            });
        if ($authuser->role_id == 7) {
            $query = $query->where('user_id', $authuser->id);
        } elseif ($authuser->role_id == 3) {
            $query = $query->whereIn('branch_id', $cc);
        } else {
            $query = $query;
        }

        $vehicles = TransactionSheet::select('vehicle_no')->distinct()->get();

        $paymentlist = $query->orderBy('id', 'DESC')->paginate($peritem);
        $paymentlist = $paymentlist->appends($request->query());
        // $vehicles    = Vehicle::select('id', 'regn_no')->get();
        $vehicletype = VehicleType::select('id', 'name')->get();
        $clients = RegionalClient::all();

        return view('payment-settlement.subvention-settled', ['prefix' => $this->prefix, 'paymentlist' => $paymentlist, 'peritem' => $peritem, 'vehicles' => $vehicles, 'vehicletype' => $vehicletype, 'branchs' => $branchs, 'clients' => $clients]);
    }

    public function unverifiedOrderCancel(Request $request)
    {
        $settled = ConsignmentNote::where('id', $request->order_id)->update(['status' => '0', 'reason_to_cancel' => $request->reason]);

        if ($settled) {
            $response['success'] = true;
            $response['success_message'] = "Order Canceled";
            $response['error'] = false;
        } else {
            $response['success'] = false;
            $response['error_message'] = "Can not please try again";
            $response['error'] = true;
        }


        return response()->json($response);
    }

    public function privacyPolicy()
    {
        return view('privacy-policy');
    }
}
