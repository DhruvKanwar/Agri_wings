@extends('layouts.main')
@section('title', 'Add Farmers')
@section('content')

<div class="layout-px-spacing" id="divbox">
    <div class="row layout-top-spacing">
        <div class="col-xl-12 col-lg-12 col-sm-12  layout-spacing">
            <div class="page-header">
                <nav class="breadcrumb-one" aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="javascript:void(0);">Farmers</a></li>
                        <li class="breadcrumb-item active" aria-current="page"><a href="javascript:void(0);"> Create
                                Farmer</a></li>
                    </ol>
                </nav>
            </div>
            <div class="widget-content widget-content-area br-6">
                <div class="page-breadcrumb d-none d-sm-flex align-items-center mb-3">
                    <!-- <div class="breadcrumb-title pe-3"><h5>Create Consignee</h5></div> -->
                </div>
                <div class="col-lg-12 col-12 layout-spacing">
                    <div class="statbox widget box box-shadow">
                        <form class="general_form" method="POST" id="createconsignee" autocomplete="off">
                            <h4>Farmer Name and Address</h4>
                            <div class="form-row mb-0">


                                <div class="form-group col-md-4">
                                    <label for="farmer_name">Farmer Name<span class="text-danger">*</span></label>
                                    <input type="text" id="farmer_name" class="form-control  form-control-sm" name="farmer_name" value="" v-model="farmer_details.farmer_name" autocomplete="false" required />
                                </div>
                                <div class="form-group col-md-4">
                                    <label for="farmer_phone">Mobile No.<span class="text-danger">*</span></label>
                                    <input type="tel" class="form-control  form-control-sm mbCheckNm" name="phone" placeholder="Enter 10 digit mobile no" v-model="farmer_details.farmer_mobile_no" maxlength="10" id="farmer_phone" required>
                                    <span id="phone_error" style="color:red;"></span>
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="postal_code">Pincode</label>
                                    <input type="number" class="form-control  form-control-sm" id="postal_code" name="postal_code" placeholder="Pincode" @keyup="getAreaDetails()" v-model="farmer_details.farmer_pincode" maxlength="6" required />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="city">Village/City</label>
                                    <input type="text" class="form-control  form-control-sm" id="city" name="city" placeholder="City" v-model="farmer_details.farmer_city" required />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="district">District</label>
                                    <input type="text" class="form-control  form-control-sm" id="district" name="district" placeholder="District" v-model="farmer_details.farmer_district" readonly />
                                </div>
                                <div class="form-group col-md-3">
                                    <label for="state">State</label>
                                    <input type="text" class="form-control  form-control-sm" id="state" name="state_id" placeholder="" v-model="farmer_details.farmer_state" readonly>
                                </div>

                                <div class="form-group col-md-12">
                                    <label for="address_line1">Address</label>
                                    <textarea type="text" class="form-control form-control-sm" id="address_line1" name="address_line1" rows="3" style="resize: none" v-model="farmer_details.farmer_address" placeholder="Enter address here..."></textarea>
                                </div>
                            </div>

                            <!-- <div class="addressRows">
                                <h4>Add Farm Address</h4>
                                <table id="myTable">
                                    <tbody>
                                        <tr>
                                            <th><span>Farm<span class="text-danger">*</span></span></th>
                                            <th><span>Pin Code<span class="text-danger">*</span></span></th>
                                            <th><span>City<span class="text-danger">*</span></span></th>
                                            <th><span>District<span class="text-danger">*</span></span></th>
                                            <th><span>State<span class="text-danger">*</span></span></th>
                                            <th><span>Address<span class="text-danger">*</span></span></th>
                                        </tr>
                                        <tr class="rowcls">
                                            <td>
                                                <input type="text" class="form-control form-control-sm name" name="data[1][field_area]" placeholder="" value="Farm 1" autocomplete="false" readonly />
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm name" name="data[1][pin_code]" v-model="farmer_details.farm_pincode" placeholder="" autocomplete="false" required />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm name" name="data[1][city]" placeholder="" autocomplete="false" required />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm name" name="data[1][district]" placeholder="" autocomplete="false" required />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm name" name="data[1][state]" placeholder="" autocomplete="false" required />
                                            </td>
                                            <td>
                                                <textarea type="text" class="form-control form-control-sm name" name="data[1][address]" style="resize: none" rows="1" placeholder="" autocomplete="new city" required></textarea>
                                            </td>

                                            <td>
                                                <button type="button" class="btn btn-primary rowActionButtons" id="addRow" onclick="addrow()">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-plus">
                                                        <line x1="12" y1="5" x2="12" y2="19" />
                                                        <line x1="5" y1="12" x2="19" y2="12" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div> -->
                            <div class="addressRows">
                                <h4>Add Farm Address</h4>
                                <table id="myTable">
                                    <tbody>
                                        <tr>
                                            <th><span>Farm<span class="text-danger">*</span></span></th>
                                            <th><span>Pin Code<span class="text-danger">*</span></span></th>
                                            <th><span>City<span class="text-danger">*</span></span></th>
                                            <th><span>District<span class="text-danger">*</span></span></th>
                                            <th><span>State<span class="text-danger">*</span></span></th>
                                            <th><span>Address<span class="text-danger">*</span></span></th>
                                            <th><span>Action</span></th>
                                        </tr>
                                        <tr v-for="(row, index) in farmer_details.farm_addresses" :key="index" class="rowcls">
                                            <td>
                                                <input type="text" class="form-control form-control-sm name" :name="'data[' + (index + 1) + '][field_area]'" :value="'Farm ' + (index + 1)" autocomplete="false" readonly />
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm name" :name="'data[' + (index + 1) + '][pin_code]'" v-model="row.pin_code" autocomplete="false" required />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm name" :name="'data[' + (index + 1) + '][city]'" v-model="row.city" autocomplete="false" required />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm name" :name="'data[' + (index + 1) + '][district]'" v-model="row.district" autocomplete="false" required />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm name" :name="'data[' + (index + 1) + '][state]'" v-model="row.state" autocomplete="false" required />
                                            </td>
                                            <td>
                                                <textarea type="text" class="form-control form-control-sm name" :name="'data[' + (index + 1) + '][address]'" style="resize: none" rows="1" autocomplete="new city" v-model="row.address" required></textarea>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-primary rowActionButtons" @click="removeRow(index)">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#000000" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-minus">
                                                        <line x1="5" y1="12" x2="19" y2="12" />
                                                    </svg>
                                                </button>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <button type="button" @click="addRow">
                                    Add Row
                                </button>
                            </div>
                            <button type="button" class="mt-4 mb-4 btn btn-primary" @click="add_farmer_details">Submit</button>
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