@extends('layouts.main')
@section('title', 'Pilots List')
@section('content')
<!-- BEGIN PAGE LEVEL CUSTOM STYLES -->
<link rel="stylesheet" type="text/css" href="{{asset('plugins/table/datatable/datatables.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('plugins/table/datatable/custom_dt_html5.css')}}">
<link rel="stylesheet" type="text/css" href="{{asset('plugins/table/datatable/dt-global_style.css')}}">
<!-- END PAGE LEVEL CUSTOM STYLES -->
<style>
    .dt--top-section {
        margin: none;
    }

    div.relative {
        position: absolute;
        left: 269px;
        top: 24px;
        z-index: 1;
        width: 83px;
        height: 35px;
    }

    /* .table > tbody > tr > td {
    color: #4361ee;
} */
    .dt-buttons .dt-button {
        width: 83px;
        height: 38px;
        font-size: 13px;
    }

    .btn-group>.btn,
    .btn-group .btn {
        padding: 0px 0px;
        padding: 10px;
    }

    div.relat {
        position: absolute;
        left: 181px;
        top: 23px;
        z-index: 1;
        width: 83px;
        height: 35px;
    }

    .btn {

        font-size: 10px;
    }
</style>
<div class="layout-px-spacing">
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="page-header">
                <nav class="breadcrumb-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Pilot</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="javascript:void(0);">Pilot List</a></li>
                    </ol>
                </nav>
            </div>
            <div class="widget-content widget-content-area br-6">
                <div class="table-responsive mb-4 mt-4">
                    @csrf
                    <table id="drivertable" class="table table-hover" style="width:100%">
                        <div class="btn-group relative" style="margin-top:-42px">
                            <a class="btn-primary btn-cstm btn w-100" id="add_role" href="{{'add_pilot'}}" style="font-size: 12px; padding: 8px 0px;"><span><i class="fa fa-plus"></i> Add New</span></a>
                        </div>
                        <?php $authuser = Auth::user();
                        if ($authuser->role_id == 1 || $authuser->role_id == 3) { ?>
                            <div class="btn-group relat">
                                <a style="font-size: 12px; padding: 8px 0px;" href="<?php echo URL::to($prefix . '/' . $segment . '/export/excel'); ?>" class="downloadEx btn btn-primary pull-right" data-action="<?php echo URL::to($prefix . '/' . $segment . '/export/excel'); ?>" download>
                                    <span><i class="fa fa-download"></i> Export</span></a>
                            </div>
                        <?php } ?>
                        <thead>
                            <tr>
                                <th>Pilot Name</th>
                                <th>Pilot Phone</th>
                                <th>RPC(Remote pilot Certificate No.)</th>
                                <th>Image</th>
                                <th>Status</th>
                                <th>Action</th>

                            </tr>
                        </thead>
                        <tbody>

                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection