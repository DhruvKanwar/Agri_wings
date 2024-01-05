@extends('layouts.main')
@section('title', 'Import Data')
@section('content')
<style>
    @media only screen and (max-width: 767px) {
        .head {
            margin-left: 10rem;
        }
    }
</style>

<div id="divbox">

    <!--  BEGIN CONTENT AREA  -->
    <div class="container">
        <div class="container">
            <div class="page-header">
                <nav class="breadcrumb-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Dashboard</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="javascript:void(0);">Import Masters</a></li>
                    </ol>
                </nav>
                <!-- Lage modal -->
                <button type="button" class="btn btn-primary mb-2 mr-2" data-toggle="modal" data-target=".bd-example-modal-lg">Sample</button>
            </div>

            <div class="row layout-top-spacing">
                <div id="modalVerticallyCentered" class="col-lg-12 layout-spacing">
                    <div class="statbox widget box box-shadow">
                        <div class="widget-header">
                            <div class="row">
                                <div class="col-xl-12 col-md-12 col-sm-12 col-12">
                                    <p class="mb-1" style="text-align: center; font-size: 20px;
                                font-weight: 800;">Upload Data</p>
                                </div>
                            </div>
                        </div>
                        <div class="widget-content widget-content-area">
                            <p class="mb-3" style="text-align: center;">Click on the below buttons to upload a Excel File </p>
                            <div class="text-center">
                                <!-- Button trigger modal -->
                                <button type="button" class="btn btn-primary mb-2 mr-2" data-toggle="modal" data-target="#exampleModalCenter">
                                    Upload New File
                                </button>
                            </div>

                            <!-- Modal -->
                            <div class="modal fade" id="exampleModalCenter" tabindex="-1" role="dialog" aria-labelledby="exampleModalCenterTitle" aria-hidden="true">
                                <div class="modal-dialog modal-dialog-centered" role="document">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title" id="exampleModalCenterTitle">Upload New</h5>
                                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x">
                                                    <line x1="18" y1="6" x2="6" y2="18"></line>
                                                    <line x1="6" y1="6" x2="18" y2="18"></line>
                                                </svg>
                                            </button>
                                        </div>
                                        <form id="new_sender_import" enctype="multipart/form-data">
                                            @csrf
                                            <div class="modal-body">
                                                <div class="form-row mb-4">
                                                    <label for="inputState">Import Type</label>
                                                    <select id="itype" class="form-control" name="import_type">
                                                        <option selected disabled>Choose...</option>
                                                        <option value="1">State and Sub District</option>
                                                        <option value="2">Sender Name & Details</option>
                                                        <!-- <option value="9">Manually Paid TER</option> -->
                                                        <!-- <option value="10">Finfect to Paid TER</option> -->
                                                        <!-- <option value="6">Update Old Ids</option> -->
                                                        <option value="7">Update PFU</option>
                                                        <!-- <option value="8">Update Beneficiary Details</option> -->
                                                    </select>
                                                </div>
                                                <div class="custom-file-container" data-upload-id="myFirstImage">
                                                    <label>Upload (Single File) <a href="javascript:void(0)" class="custom-file-container__image-clear" title="Clear Image">x</a></label>
                                                    <label class="custom-file-container__custom-file">
                                                        <input type="file" class="custom-file-container__custom-file__custom-file-input" id="myxls" name="file" v-on:change="upload_file($event)" accept=".xlsx">
                                                        <span class="custom-file-container__custom-file__custom-file-control"></span>
                                                    </label>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                                                <button type="button" class="btn btn-primary" @click="submitForm()">
                                                    <span class="indicator-label">Save</span>
                                                    <span class="indicator-progress" style="display: none;">Please wait...
                                                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                                    </span>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!--  END CONTENT AREA  -->

    <div class="modal fade bd-example-modal-lg" tabindex="-1" role="dialog" aria-labelledby="myLargeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="myLargeModalLabel">Sample</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <svg aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x">
                            <line x1="18" y1="6" x2="6" y2="18"></line>
                            <line x1="6" y1="6" x2="18" y2="18"></line>
                        </svg>
                    </button>
                </div>
                <div class="modal-body">
                    <a class="btn btn-primary" href="{{url('/sample-spine-hr-dump')}}" role="button" style="margin-left: 103px;">Spine HR Dump</a> ||
                    <a class="btn btn-primary" href="{{url('/sample-sender')}}" role="button" style="margin-left: 103px;">Sender Import</a> ||
                    <a class="btn btn-primary" href="{{url('/sample-courier')}}" role="button">Courier Companies</a> ||
                    <a class="btn btn-primary" href="{{url('/sample-category')}}" role="button">Catagories</a> ||
                    <a class="btn btn-primary" href="{{url('/sample-for')}}" role="button">Receiving Company</a>
                </div>
                <div class="modal-footer">
                    <button class="btn" data-dismiss="modal"><i class="flaticon-cancel-12"></i> Discard</button>
                    <button type="button" class="btn btn-primary">Save</button>
                </div>
            </div>
        </div>
    </div>

</div>
<script>
    new Vue({
        el: '#divbox',
        // components: {  
        //   ValidationProvider
        // },
        data: {
            file: "",
            selected_option: ""

        },
        created: function() {
            // alert(this.got_details)
            // alert('hello');
        },
        methods: {
            upload_file(e) {
                this.file = e.target.files[0];
            },
            submitForm() {
                this.selected_option = $("#itype").val();

                if (this.selected_option == "") {
                    alert("Please select import type");
                    return;
                }

                if (this.file == "") {
                    alert("No file, please upload an import file");
                    return;
                }

                // alert(this.selected_option);

                // Remove the 'return' statement here to allow the following code to execute

                // Check if this.file is not empty before proceeding with axios request
                if (this.file != "") {
                    const config = {
                        headers: {
                            'content-type': 'multipart/form-data',
                        }
                    };

                    let formData = new FormData();
                    formData.append('file', this.file);
                    formData.append('selected_option', this.selected_option);

                    axios.post('/import_data', formData, config)
                        .then(response => {
                            console.log(response.data);
                            return 1; // If needed, adjust this return statement based on your logic
                        })
                        .catch((error) => {
                            this.hideProgress();
                            this.showError("Error");
                        });
                }
            },
            add_farmer_details: function() {
                if (this.hr_remarks != "") {
                    axios.post('/submit_farmer_details', {
                            'farmer_details': this.farmer_details
                        })
                        .then(response => {
                            console.log(response.data);
                        }).catch(error => {

                            console.log(response)
                            this.apply_offer_btn = 'Apply';

                        })
                } else {
                    swal('error', 'Hr Remarks Required', 'error')
                }
            },

        }


    })
</script>
@endsection