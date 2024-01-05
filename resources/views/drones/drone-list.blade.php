@extends('layouts.main')
@section('title', 'Drone List')
@section('content')
<!--  BEGIN MAIN CONTAINER  -->
<div class="main-container" id="container">

    <div class="overlay"></div>
    <div class="cs-overlay"></div>
    <div class="search-overlay"></div>



    <!--  BEGIN CONTENT AREA  -->
    <div id="content" class="main-content">
        <div class="layout-px-spacing">



            <div class="row layout-top-spacing" id="cancel-row">

                <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
                    <div class="widget-content widget-content-area br-6">
                        <div class="btn-group relative">
                            <a class="btn-primary btn-cstm btn w-100" id="add_role" href="{{'add_drone'}}" style="font-size: 12px; padding: 8px 0px;"><span><i class="fa fa-plus"></i> Add New</span></a>
                        </div>
                        <div class="btn-group relat">
                            <a href="{{ route('export_farmer_details.route') }}" class="btn btn-primary" style="font-size: 12px; padding: 8px 0px;">
                                <span><i class="fa fa-download"></i> Export</span></a>
                        </div>
                        <table id="zero-config" class="table table-striped" style="width:100%">
                            <thead>
                                <tr>
                                    <th>S.NO.</th>
                                    <th>Drone ID</th>
                                    <th>Drone Model</th>
                                    <th>Drone UIN</th>
                                    <th>MANUFACTURING YEAR</th>
                                    <th>DRONE CAPACITY</th>
                                    <th>Status</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(count($drone_details) < 1) <tr>
                                    <td colspan="6">
                                        <div class="d-flex justify-content-center align-items-center" style="min-height: min(45vh, 400px)">
                                            No data to display
                                        </div>
                                    </td>
                                    </tr>
                                    @else
                                    @foreach ($drone_details as $key => $drone_detail)
                                    <tr>
                                        <td>{{$key+1}}</td>
                                        <td>{{$drone_detail->drone_id}}</td>
                                        <td>{{$drone_detail->model}}</td>
                                        <td>{{$drone_detail->uin}}</td>
                                        <td>{{$drone_detail->mfg_year}}</td>
                                        <td>{{$drone_detail->capacity}}</td>
                                        @if($drone_detail->status == 1)
                                        <td>Active</td>
                                        @else
                                        <td>Inactive</td>
                                        @endif

                                        <td><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x-circle table-cancel">
                                                <circle cx="12" cy="12" r="10"></circle>
                                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                                <line x1="9" y1="9" x2="15" y2="15"></line>
                                            </svg></td>
                                    </tr>

                                    @endforeach
                                    @endif
                            </tbody>
                        </table>
                    </div>
                </div>

            </div>

        </div>
        <div class="footer-wrapper">
            <div class="footer-section f-section-1">
                <p class="">Copyright © 2021 <a target="_blank" href="https://designreset.com">DesignReset</a>, All rights reserved.</p>
            </div>
            <div class="footer-section f-section-2">
                <p class="">Coded with <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-heart">
                        <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                    </svg></p>
            </div>
        </div>
    </div>
    <!--  END CONTENT AREA  -->

</div>
<!-- END MAIN CONTAINER -->
@endsection