@extends('layouts.main')
@section('title', 'Add Pilot')
@section('content')
<style>
    .row.layout-top-spacing {
        width: 80%;
        margin: auto;

    }
</style>

<div class="layout-px-spacing">
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="page-header">
                <nav class="breadcrumb-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Pilots</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="javascript:void(0);">Create Pilot</a></li>
                    </ol>
                </nav>
            </div>
            <div class="widget-content widget-content-area br-6">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <!-- <div class="breadcrumb-title pe-3"><h5>Create Driver</h5></div> -->
                </div>
                <div class="col-lg-12 col-12 layout-spacing">
                    <div class="statbox widget box box-shadow">
                        <form class="general_form" method="POST" id="createdriver">
                            <div class="form-row mb-0">
                                <div class="form-group col-md-6">
                                    <label for="exampleFormControlInput2">Pilot Name<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="name" placeholder="Name">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="exampleFormControlInput2">Pilot Phone<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control mbCheckNm" name="phone" id="phone" placeholder="Phone" maxlength="10">
                                </div>
                            </div>
                            <div class="form-row mb-0">
                                <div class="form-group col-md-6">
                                    <label for="exampleFormControlInput2">RPC(Remote pilot Certificate No.)<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="license_number" placeholder="">
                                </div>

                            </div>

                            <div class="form-row mb-0">
                                <div class="form-group col-md-6">
                                    <label for="exampleFormControlInput2">Pilot License File(Optional)</label>
                                    <input type="file" class="form-control license_image" name="license_image" accept="image/*">
                                </div>
                                <div class="form-group col-md-6">
                                    <div class="image_upload"><img src="{{url("/assets/img/upload-img.png")}}" class="licenseshow image-fluid" id="img-tag" width="320" height="240"></div>
                                </div>
                            </div>
                            <h5 class="form-row mb-2">Login Details</h5>
                            <div class="form-row mb-0">
                                <div class="form-group col-md-6">
                                    <label for="exampleFormControlInput2">Login Id</label>
                                    <input type="text" class="form-control" name="login_id" id="login_id" placeholder="" readonly>
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="exampleFormControlInput2">Password</label>
                                    <input type="password" class="form-control" name="password" placeholder="">
                                </div>
                            </div>
                            <button type="submit" class="mt-4 mb-4 btn btn-primary">Submit</button>
                        </form>
                    </div>
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
            farmer_details: {
                farm_addresses: [{
                    field_area: '',
                    pin_code: '',
                    city: '',
                    district: '',
                    state: '',
                    address: ''
                }]
            },
            areaDetails: {},
            timer: "",
            length: 0,

        },
        created: function() {
            // alert(this.got_details)
            // alert('hello');
        },
        methods: {
            addRow: function() {
                const newIndex = this.farmer_details.farm_addresses.length + 1;
                this.farmer_details.farm_addresses.push({
                    field_area: 'Farm ' + newIndex,
                    pin_code: '',
                    city: '',
                    district: '',
                    state: '',
                    address: ''
                });
                // console.log(this.farmer_details.farm_addresses)
            },
            removeRow(index) {
                this.farmer_details.farm_addresses.splice(index, 1);
            },
            getAreaDetails() {
                // Check if the postal code is exactly 6 digits
                if (/^\d{6}$/.test(this.farmer_details.farmer_pincode)) {
                    // Make a GET request using Axios
                    axios.get(`/get_area_details/${this.farmer_details.farmer_pincode}`)
                        .then(response => {
                            // Update the areaDetails data property with the response
                            this.areaDetails = response.data;

                            // Update values only if the postal code is valid
                            document.getElementById('district').value = this.areaDetails.data.district;
                            document.getElementById('state').value = this.areaDetails.data.state;

                            this.farmer_details.farmer_district = this.areaDetails.data.district;
                            this.farmer_details.farmer_state = this.areaDetails.data.state;
                        })
                        .catch(error => {
                            // Handle errors, e.g., display an error message
                            console.error('Error fetching area details:', error);
                        });
                } else {
                    // Handle the case when the postal code is not 6 digits
                    console.warn('Postal code must be exactly 6 digits.');
                }

                document.getElementById('district').value = "";
                document.getElementById('state').value = "";

                this.farmer_details.farmer_district = "";
                this.farmer_details.farmer_state = "";
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
@section('js')
<script>
    $(document).on("click", ".remove_licensefield", function(e) { //user click on remove text
        var getUrl = window.location;
        var baseurl = getUrl.origin + '/' + getUrl.pathname.split('/')[0];
        var imgurl = baseurl + 'assets/img/upload-img.png';

        $(this).parent().children(".image_upload").children().attr('src', imgurl);
        $(this).parent().children("input").val('');;
        // $(this).parent().children('div').children('h4').text('Add Image');
        // $(this).parent().children('div').children('h4').css("display", "block");
        $(this).css("display", "none");
    });

    function readURL1(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function(e) {
                $('.licenseshow').attr('src', e.target.result);
                $(".remove_licensefield").css("display", "block");
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    $(document).on("change", '.license_image', function(e) {
        var fileName = this.files[0].name;
        // $(this).parent().parent().find('.file_graph').text(fileName);

        readURL1(this);
    });

    $("#phone").blur(function() {
        $("#login_id").val($(this).val());
    });
</script>
@endsection