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
                                    <input type="number" class="form-control  form-control-sm" id="postal_code" name="postal_code" placeholder="Pincode" v-model="farmer_details.farmer_pincode" maxlength="6" required />
                                </div>

                                <div class="form-group col-md-3">
                                    <label for="exampleFormControlInput2">Subdistrict<span class="text-danger">*</span></label>
                                    <select class="form-control my-select2" name="subdistrict" v-on:change="fetchDistricts" v-model="selectedSubdistrict" id="subdistrict" required>
                                        <option value="">--select--</option>
                                        <option v-for="location_data in location_datas" :key="location_data.id" :value="location_data.subdistrict_name" :data-state="location_data.state_name">
                                            @{{ location_data.subdistrict_name }} (@{{ location_data.state_name }})
                                        </option>
                                    </select>
                                    <span id="subdistrict_error" style="color:red;"></span>
                                </div>

                                <div class="form-group col-md-3">
                                    <label for="state">Village</label>
                                    <input type="text" class="form-control  form-control-sm" id="state" name="state_id" placeholder="" v-model="village_name" @change="get_village()">
                                </div>

                                <div class="form-group col-md-3">
                                    <label for="exampleFormControlInput2">Village/ Town<span class="text-danger">*</span></label>
                                    <select class="form-control my-select2" name="village" id="village" required v-model="farmer_details.selected_villages">
                                        <option v-for="village in districts" :key="village.vil_town_code.vil_town_name" :value="village">@{{ village.vil_town_name }}</option>
                                    </select>
                                    <span id="village_error" style="color:red;"></span>
                                </div>
                                <!-- <div class="form-group col-md-3">
                                    <label for="city">Village/Town*</label>
                                    <input type="text" class="form-control  form-control-sm" id="city" name="city" placeholder="City" v-model="farmer_details.farmer_city" required />
                                </div> -->
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
                                            <th><span>Subdistrict<span class="text-danger">*</span></span></th>
                                            <th><span>Village/Town<span class="text-danger">*</span></span></th>
                                            <th><span>District<span class="text-danger">*</span></span></th>
                                            <th><span>State<span class="text-danger">*</span></span></th>
                                            <th><span>Pin Code<span class="text-danger"></span></span></th>
                                            <th><span>Address<span class="text-danger"></span></span></th>
                                            <th><span>Acerage<span class="text-danger">*</span></span></th>
                                            <th><span>Action</span></th>
                                        </tr>
                                        <tr v-for="(row, index) in farmer_details.farm_addresses" :key="index" class="rowcls">
                                            <td>
                                                <input type="text" class="form-control form-control-sm name" :name="'data[' + (index + 1) + '][field_area]'" :value="'Farm ' + (index + 1)" autocomplete="false" readonly />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm name" :name="'data[' + (index + 1) + '][sub_district]'" v-model="row.sub_district" autocomplete="false" />

                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm name" :name="'data[' + (index + 1) + '][village]'" v-model="row.village" autocomplete="false" required />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm name" :name="'data[' + (index + 1) + '][district]'" v-model="row.district" autocomplete="false" required />
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm name" :name="'data[' + (index + 1) + '][state]'" v-model="row.state" autocomplete="false" required />
                                            </td>
                                            <td>
                                                <input type="number" class="form-control form-control-sm name" :name="'data[' + (index + 1) + '][pin_code]'" v-model="row.pin_code" autocomplete="false" required />
                                            </td>

                                            <td>
                                                <textarea type="text" class="form-control form-control-sm name" :name="'data[' + (index + 1) + '][address]'" style="resize: none" rows="1" autocomplete="new city" v-model="row.address" required></textarea>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control form-control-sm name" :name="'data[' + (index + 1) + '][acerage]'" v-model="row.acerage" autocomplete="false" required />
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
                            <br />
                            <br />
                            <!-- start farmer profile -->
                            <h4>Farmer Profile</h4>
                            <label>Demographic Info</label><br>
                            <div class="form-row mb-0">

                                <div class="form-group col-md-2">
                                    <label for="gender">Gender<span class="text-danger">*</span></label>
                                    <select class="form-control form-control-sm" id="gender" name="gender" v-model="farmer_details.profile.gender" required>
                                        <option value="">--Select--</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>

                                <div class="form-group col-md-2">
                                    <label for="income">Income<span class="text-danger">*</span></label>
                                    <input type="text" id="income" class="form-control form-control-sm" name="income" v-model="farmer_details.profile.income" autocomplete="false" required />
                                </div>

                                <div class="form-group col-md-2">
                                    <label for="education_level">Education Level<span class="text-danger">*</span></label>
                                    <select class="form-control form-control-sm" id="education_level" name="education_level" v-model="farmer_details.profile.education_level" required>
                                        <option value="">--Select--</option>
                                        <option value="secondary">Secondary</option>
                                        <option value="senior_secondary">Senior Secondary</option>
                                        <option value="graduate">Graduate</option>
                                        <option value="post_graduate">Post-Graduate</option>
                                    </select>
                                </div>

                                <div class="form-group col-md-2">
                                    <label for="date_of_birth">Date of Birth<span class="text-danger">*</span></label>
                                    <input type="date" id="date_of_birth" class="form-control form-control-sm" name="date_of_birth" v-model="farmer_details.profile.date_of_birth" autocomplete="false" required />
                                </div>
                            </div><br />
                            <div class="form-group col-md-2">
                                <label for="wedding_anniversary">Wedding Anniversary<span class="text-danger">*</span></label>
                                <input type="date" id="wedding_anniversary" class="form-control form-control-sm" name="wedding_anniversary" v-model="farmer_details.profile.wedding_anniversary" autocomplete="false" required />
                            </div>

                            <!-- Continue adding fields for attitude, lifestyle, professional_info, hobbies, favourite_activities, interests, mobile_phone_used, social_media_platform, tech_proficiency, preferred_communication, email_id, ratings, suggestion_for_improvement -->
                            <div class="form-group col-md-2">
                                <label for="attitude">Influence</label>
                                <input type="text" id="attitude" class="form-control form-control-sm" name="attitude" v-model="farmer_details.profile.influence" autocomplete="false" />
                            </div>

                            <div class="form-group col-md-2">
                                <label for="attitude">Attitude</label>
                                <input type="text" id="attitude" class="form-control form-control-sm" name="attitude" v-model="farmer_details.profile.attitude" autocomplete="false" />
                            </div>

                            <div class="form-group col-md-2">
                                <label for="lifestyle">Lifestyle</label>
                                <input type="text" id="lifestyle" class="form-control form-control-sm" name="lifestyle" v-model="farmer_details.profile.lifestyle" autocomplete="false" />
                            </div>

                            <div class="form-group col-md-2">
                                <label for="professional_info">Professional Info</label>
                                <input type="text" id="professional_info" class="form-control form-control-sm" name="professional_info" v-model="farmer_details.profile.professional_info" autocomplete="false" />
                            </div>

                            <div class="form-group col-md-2">
                                <label for="hobbies">Hobbies</label>
                                <input type="text" id="hobbies" class="form-control form-control-sm" name="hobbies" v-model="farmer_details.profile.hobbies" autocomplete="false" />
                            </div>

                            <div class="form-group col-md-2">
                                <label for="favourite_activities">Favourite Activities</label>
                                <input type="text" id="favourite_activities" class="form-control form-control-sm" name="favourite_activities" v-model="farmer_details.profile.favourite_activities" autocomplete="false" />
                            </div>

                            <div class="form-group col-md-2">
                                <label for="interests">Interests</label>
                                <input type="text" id="interests" class="form-control form-control-sm" name="interests" v-model="farmer_details.profile.interests" autocomplete="false" />
                            </div>

                            <div class="form-group col-md-2">
                                <label for="mobile_phone_used">Mobile Phone Used</label>
                                <input type="text" id="mobile_phone_used" class="form-control form-control-sm" name="mobile_phone_used" v-model="farmer_details.profile.mobile_phone_used" autocomplete="false" />
                            </div>

                            <div class="form-group col-md-2">
                                <label for="social_media_platform">Social Media Platform</label>
                                <input type="text" id="social_media_platform" class="form-control form-control-sm" name="social_media_platform" v-model="farmer_details.profile.social_media_platform" autocomplete="false" />
                            </div>

                            <div class="form-group col-md-2">
                                <label for="tech_proficiency">Tech Proficiency</label>
                                <select class="form-control form-control-sm" id="tech_proficiency" name="tech_proficiency" v-model="farmer_details.profile.tech_proficiency">
                                    <option value="">--Select--</option>
                                    <option value="high">High</option>
                                    <option value="low">Low</option>
                                    <option value="nill">Nill</option>
                                </select>
                            </div>

                            <div class="form-group col-md-2">
                                <label for="preferred_communication">Preferred Communication</label>
                                <input type="text" id="preferred_communication" class="form-control form-control-sm" name="preferred_communication" v-model="farmer_details.profile.preferred_communication" autocomplete="false" />

                                <!-- You can use checkboxes here -->
                            </div>

                            <div class="form-group col-md-2">
                                <label for="email_id">Email ID</label>
                                <input type="email" id="email_id" class="form-control form-control-sm" name="email_id" v-model="farmer_details.profile.email_id" autocomplete="false" />
                            </div>

                            <div class="form-group col-md-2">
                                <label for="ratings">Ratings</label>
                                <input type="number" id="ratings" class="form-control form-control-sm" name="ratings" v-model="farmer_details.profile.ratings" autocomplete="false" />
                            </div>

                            <div class="form-group col-md-12">
                                <label for="suggestion_for_improvement">Suggestions for Improvement</label>
                                <textarea type="text" class="form-control form-control-sm" id="suggestion_for_improvement" name="suggestion_for_improvement" rows="3" style="resize: none" v-model="farmer_details.profile.suggestion_for_improvement"></textarea>
                            </div>

                    </div>
                    <!-- end farmer profile -->
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
            // farmer_details: {
            //     farm_addresses: [{
            //         field_area: '',
            //         pin_code: '',
            //         city: '',
            //         district: '',
            //         state: '',
            //         address: '',
            //         acerage: ""
            //     }],
            //     profile: [{
            //         gender: '',
            //         income: '',
            //         education_level: '',
            //         date_of_birth: '',
            //         wedding_anniversary: '',
            //         attitude: '',
            //         lifestyle: "",
            //         professional_info: "",
            //         influence: "",
            //         hobbies: "",
            //         favourite_activities: "",
            //         intrests: "",
            //         mobile_phone_used: "",
            //         social_media_platform: "",
            //         tech_proficiency: "",
            //         prferred_communication: "",
            //         email_id: "",
            //         ratings: "",
            //         suggestion_for_improvement: "",
            //         preferred_communication: "",

            //     }]

            // },
            farmer_details: {
                farm_addresses: [{
                        field_area: "Farm 1",
                        pin_code: "123456",
                        city: null,
                        district: "dist 1",
                        state: "punjab",
                        address: "add 1",
                        sub_district: "sub dis 1",
                        village: "vil 1",
                        acerage: "123"
                    },
                    {
                        field_area: "Farm 2",
                        pin_code: "9876541",
                        city: null,
                        district: "dist 2",
                        state: "haryana",
                        address: "add 2",
                        sub_district: "sub dist 2",
                        village: "vil 2",
                        acerage: "432"
                    }
                ],
                profile: [{
                    gender: 'Male',
                    income: '120',
                    education_level: '8th',
                    date_of_birth: '07-09-1998',
                    wedding_anniversary: '23-08-2003',
                    attitude: 'Good',
                    lifestyle: "Engaing",
                    professional_info: "farmer",
                    influence: "high",
                    hobbies: "cricket",
                    favourite_activities: "music",
                    intrests: "badminton",
                    mobile_phone_used: "smart",
                    social_media_platform: "fb,insta",
                    tech_proficiency: "high",
                    prferred_communication: "whatsapp,email",
                    email_id: "naveen@test.com",
                    ratings: "5",
                    suggestion_for_improvement: "nothing",
                }], // You may want to add fields for the profile object
                farmer_name: "Naveen",
                farmer_mobile_no: "9876543210",
                farmer_pincode: "140603",
                farmer_district: "Udalguri",
                farmer_state: "Assam",
                selected_villages: {
                    // Fields for selected_villages
                },
                farmer_address: "add main",
                farmer_sub_district: "Dhekiajuli (Pt)",
                farmer_village: "Chengelimaragaon"
            },
            areaDetails: {},
            timer: "",
            length: 0,
            selectedSubdistrict: '',
            location_datas: <?php echo json_encode($location_datas); ?>,
            location_data: "",
            selectedState: "",
            districts: "",
            subdistricts: "",
            village_name: "",

        },
        created: function() {
            // alert(this.got_details)
            // alert('hello');
            // console.log(this.location_datas)
        },
        methods: {
            get_village() {
                // alert(this.village_name)
                // return 1;
                if (this.inputText.length === 3) {

                    axios.get("fetch-villages", {
                            params: {
                                village_name: this.village_name,
                            },
                        })
                        .then(response => {
                            return 1;
                            this.districts = response.data.district_details;
                            this.subdistricts = this.districts;
                            this.selectedVillage = ''; // Clear the selected village
                            document.getElementById("district").value = response.data.district_details[0].district_name;
                            document.getElementById("state").value = response.data.district_details[0].state_name;

                            this.farmer_details.farmer_district = response.data.district_details[0].district_name;
                            this.farmer_details.farmer_state = response.data.district_details[0].state_name;


                            // console.log(this.farmer_details)
                        })
                        .catch(error => {
                            console.error('Error fetching districts:', error);
                        });
                } else {
                    alert("Length is more")
                }
            },
            fetchDistricts() {
                this.selectedState = event.target.options[event.target.selectedIndex].getAttribute('data-state');
                this.farmer_details.farmer_district = "";
                this.farmer_details.farmer_state = "";
                // console.log('Data State:', dataState);
                // alert(this.selectedState)
                // return;

                axios.get("fetch-towns", {
                        params: {
                            subdistrict: this.selectedSubdistrict,
                            state: this.selectedState, // Make sure to set selectedState in your data if needed
                        },
                    })
                    .then(response => {
                        this.districts = response.data.district_details;
                        this.subdistricts = this.districts;
                        this.selectedVillage = ''; // Clear the selected village
                        document.getElementById("district").value = response.data.district_details[0].district_name;
                        document.getElementById("state").value = response.data.district_details[0].state_name;

                        this.farmer_details.farmer_district = response.data.district_details[0].district_name;
                        this.farmer_details.farmer_state = response.data.district_details[0].state_name;


                        // console.log(this.farmer_details)
                    })
                    .catch(error => {
                        console.error('Error fetching districts:', error);
                    });

            },
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

                            // this.farmer_details.farmer_district = this.areaDetails.data.district;
                            // this.farmer_details.farmer_state = this.areaDetails.data.state;
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

                // this.farmer_details.farmer_district = "";
                // this.farmer_details.farmer_state = "";
            },
            add_farmer_details: function() {
                // console.log(this.farmer_details.profile.ratings)
                // return 1;
                // this.farmer_details.farmer_sub_district = this.farmer_details.selected_villages.subdistrict_name;
                // this.farmer_details.farmer_village = this.farmer_details.selected_villages.vil_town_name;

                axios.post('/submit_farmer_details', {
                        'farmer_details': this.farmer_details
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