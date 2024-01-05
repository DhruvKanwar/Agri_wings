@extends('layouts.main')
@section('title', 'Add New Drone')
@section('content')
<style>
    .row.layout-top-spacing {
        width: 80%;
        margin: auto;
    }
</style>

<div class="layout-px-spacing" id="divbox">
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="page-header">
                <nav class="breadcrumb-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Drones</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="javascript:void(0);">Create Drone</a></li>
                    </ol>
                </nav>
            </div>
            <div class="widget-content widget-content-area br-6">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <!-- <div class="breadcrumb-title pe-3"><h5>Create Vehicle</h5></div> -->
                </div>
                <div class="col-lg-12 col-12 layout-spacing">
                    <div class="statbox widget box box-shadow">
                        <form class="general_form" id="createvehicle">
                            <div class="form-row mb-0">
                                <div class="form-group col-md-6">
                                    <label for="exampleFormControlInput2">UIN<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="regn_no" name="regn_no" placeholder="" v-model="drone_details.uin" autocomplete="off">
                                </div>
                                <div class="form-group col-md-6">
                                    <label for="exampleFormControlInput2">Manufacturing year<span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" name="mfg" placeholder="" v-model="drone_details.mfg_year" autocomplete="off">
                                </div>
                            </div>
                            <div class="form-row mb-0">
                                <div class="form-group col-md-6">
                                    <label for="exampleFormControlSelect1">Drone Model<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="make" placeholder="" v-model="drone_details.model" autocomplete="off">
                                </div>
                                <!-- <div class="form-group col-md-6">
                                    <label for="exampleFormControlSelect1">Engine No.<span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="engine_no" placeholder="">
                                </div> -->
                                <div class="form-group col-md-6">
                                    <label for="exampleFormControlInput2">Drone Capacity(Ltrs)</label>
                                    <input type="number" class="form-control" id="gross_vehicle_weight" name="gross_vehicle_weight" v-model="drone_details.capacity" placeholder="">
                                </div>
                            </div>
                            <div class="form-row mb-0">

                            </div>

                            <button type="button" class="mt-4 mb-4 btn btn-primary" @click="add_drone_details()">Submit</button>
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
            drone_details: {},


        },
        created: function() {
            // alert(this.got_details)
            // alert('hello');
            // console.log(this.location_datas)
        },
        methods: {
            add_drone_details: function() {
                // console.log(this.drone_details)
                // return 1;

                axios.post('/submit_drone_details', {
                        'drone_details': this.drone_details
                    })
                    .then(response => {
                        console.log(response.data);
                    }).catch(error => {

                        console.log(response)
                        this.apply_offer_btn = 'Apply';

                    })

            },

        }


    })
</script>
@endsection
@section('js')
<script>
    function readURL1(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();

            reader.onload = function(e) {
                $('.rcshow').attr('src', e.target.result);
                // $(".remove_licensefield").css("display", "block");
            }
            reader.readAsDataURL(input.files[0]);
        }
    }

    $(document).on("change", '.rc_image', function(e) {
        var fileName = this.files[0].name;
        readURL1(this);
    });
</script>
@endsection