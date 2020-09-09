<template>
    <div id="ranking_container">
        <div class="white_container">
            <div class="utalk_table_search">
                <div class="search_toggle">
                    <b-button class="search_btn" :class="toggle_search == 1 ? 'active' : ''" v-b-toggle.search_filter_holder @click="toggle_search = toggle_search == 1 ? 0 : 1"><fa icon="angle-down" /></b-button>
                </div>
                <b-collapse id="search_filter_holder" class="mt-2">
                    <b-card>
                        <b-form>
                            <b-row>
                                <b-col sm="3">
                                    <b-form-group>
                                        <label>Class ID</label>
                                        <b-input type="number" v-model="search.class_id"/>
                                    </b-form-group>
                                </b-col>
                                <b-col sm="6">
                                    <b-form-group>
                                        <label>Date Time From - Date Time To</label>
                                        <div class="date_range_container">
                                            <datetime class="date_time_picker" v-model="search.from"></datetime>
                                            <span class="range_icon">-</span>
                                            <datetime class="date_time_picker" v-model="search.to"></datetime>
                                        </div>
                                    </b-form-group>
                                </b-col>
                                <b-col sm="3">
                                    <b-form-group>
                                        <label>Status</label>
                                        <b-form-select v-model="search.status" :options="stats_option"/>
                                    </b-form-group>
                                </b-col>
                            </b-row>
                            <b-row>
                                <b-col sm="3">
                                    <b-form-group>
                                        <label>Amount</label>
                                        <b-input type="number" v-model="search.amount"></b-input>
                                    </b-form-group>
                                </b-col>
                                <b-col sm="3">
                                    <b-form-group>
                                        <label>Addition to Total Class</label>
                                        <b-input type="number" v-model="search.attoclass"></b-input>
                                    </b-form-group>
                                </b-col>
                                <b-col sm="3">
                                    <b-form-group>
                                        <label>Current Lesson</label>
                                        <b-input type="number" v-model="search.curr_lesson_count"></b-input>
                                    </b-form-group>
                                </b-col>
                                <b-col sm="3">
                                    <b-form-group>
                                        <label>Current Level</label>
                                        <b-form-select v-model="search.curr_level" :options="level"/>
                                    </b-form-group>
                                </b-col>
                            </b-row>
                        </b-form>
                    </b-card>
                </b-collapse>
            </div>

            <h5 class="text-center">Earnings/Rank up/down Log</h5>
            <table class="utalk_table_layout">
                <thead>
                    <tr>
                        <th style="width:10%;">Class ID</th>
                        <th style="width:10%;">Date Time</th>
                        <th style="width:10%;">Status</th>
                        <th style="width:10%;">Amount</th>
                        <th style="width:10%;">Addition to Total Class</th>
                        <th style="width:10%;">Current Lesson Count</th>
                        <th style="width:10%;">Current Level</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>1473822</td>
                        <td>Nov 1.13:00</td>
                        <td><a href="#" @click="$bvModal.show('modalStatus')">Valid</a></td>
                        <td><a href="#" @click="$bvModal.show('modalAmount')">75</a></td>
                        <td>1</td>
                        <td>799</td>
                        <td>Junior C</td>
                    </tr>
                    <tr>
                        <td>1473823</td>
                        <td>Nov 2.13:00</td>
                        <td><a href="#" @click="$bvModal.show('modalStatus')">Transferred</a></td>
                        <td><a href="#" @click="$bvModal.show('modalAmount')">-77.25</a></td>
                        <td>1</td>
                        <td>800</td>
                        <td>Junior B</td>
                    </tr>
                    <tr>
                        <td>1473824</td>
                        <td>Nov 3.13:00</td>
                        <td><a href="#" @click="$bvModal.show('modalStatus')">Invalid</a></td>
                        <td><a href="#" @click="$bvModal.show('modalAmount')">77.75</a></td>
                        <td><a href="#" @click="$bvModal.show('modalAttc')">-200</a></td>
                        <td>600</td>
                        <td>Junior B</td>
                    </tr>
                </tbody>
            </table>

            <!-- <b-modal id="modalDesc">
                <template v-if="dataType === 'amount'">
                    <label>Class Amount:</label><br/>
                    <label>Sub-class Amount:</label><br/>
                    <label>Week-end class amount:</label>
                </template>

                <template v-else-if="dataType === 'attc'">
                    <label>Complaints:</label><br/>
                    <label>IR ID:</label><br/>
                    <label>Description:</label><br/>
                    <label>Create Date:</label>
                </template>

                <template v-else>
                    <label>Teacher:</label><br/>
                    <label>Check In:</label><br/>
                    <label>Category:</label><br/>
                    <label>Comment:</label><br/>
                    <label>Duration:</label>
                </template>

                <template v-slot:modal-footer>
                    <div class="w-100">
                        <b-button variant="primary" size="sm" class="float-right" @click="$bvModal.hide('modalDesc')">
                            Close
                        </b-button>
                    </div>
                </template>
            </b-modal> -->
            <b-modal id="modalStatus">
                <template>
                    <b-row>
                        <b-col sm="6"><strong class="ml-md-4">Teacher: </strong></b-col><b-col sm="6">Wendy</b-col>
                        <b-col sm="6"><strong class="ml-md-4">Check In:</strong></b-col><b-col sm="6">Done</b-col>
                        <b-col sm="6"><strong class="ml-md-4">Category:</strong></b-col><b-col sm="6">Done</b-col>
                        <b-col sm="6"><strong class="ml-md-4">Comment:</strong></b-col><b-col sm="6">Done</b-col>
                        <b-col sm="6"><strong class="ml-md-4">Duration:</strong></b-col><b-col sm="6">25:00</b-col>
                    </b-row>
                </template>

                <template v-slot:modal-footer>
                    <div class="w-100">
                        <b-button variant="primary" size="sm" class="float-right" @click="$bvModal.hide('modalStatus')">
                            Close
                        </b-button>
                    </div>
                </template>
            </b-modal>

            <b-modal id="modalAmount">
                <template>
                    <b-row>
                        <b-col sm="6"><strong class="ml-md-4">Class Amount:</strong></b-col><b-col sm="6">600</b-col>
                        <b-col sm="6"><strong class="ml-md-4">Sub-class Amount:</strong></b-col><b-col sm="6">260</b-col>
                        <b-col sm="6"><strong class="ml-md-4">Weeke-end class amount:</strong></b-col><b-col sm="6">40</b-col>
                        <b-col sm="6"><strong class="ml-md-4">Total:</strong></b-col><b-col sm="6">900</b-col>
                    </b-row>
                </template>

                <template v-slot:modal-footer>
                    <div class="w-100">
                        <b-button variant="primary" size="sm" class="float-right" @click="$bvModal.hide('modalAmount')">
                            Close
                        </b-button>
                    </div>
                </template>
            </b-modal>

            <b-modal id="modalAttc">
                <template>
                    <b-row>
                        <b-col sm="6"><strong class="ml-md-4">Complaints:</strong></b-col><b-col sm="6">Late Comer</b-col>
                        <b-col sm="6"><strong class="ml-md-4">IR ID:</strong></b-col><b-col sm="6">2314</b-col>
                        <b-col sm="6"><strong class="ml-md-4">Description:</strong></b-col><b-col sm="6">Asdfhkd dkjgfkjsdf kfkjs</b-col>
                        <b-col sm="6"><strong class="ml-md-4">Date Created:</strong></b-col><b-col sm="6">12-12-2019</b-col>
                    </b-row>
                </template>

                <template v-slot:modal-footer>
                    <div class="w-100">
                        <b-button variant="primary" size="sm" class="float-right" @click="$bvModal.hide('modalAttc')">
                            Close
                        </b-button>
                    </div>
                </template>
            </b-modal>
        </div>
    </div>
</template>

<script>
import datetime from 'vuejs-datetimepicker';
export default {
    name: 'Rankings',
    components:{
        datetime
    },
    data(){
        return {
            search: {
                class_id: null,
                from: null,
                to: null,
                status: null,
                amount: null,
                attclass: null,
                curr_lesson_count: null,
                curr_level: null
            },
            toggle_search: 0,
            stats_option: [
                {value: 0, text : 'Valid'},
                {value: 1, text : 'Transferred'},
                {value: 2, text : 'Invalid'},
                {value: 3, text : 'Complaints'},
            ],
            level: [
                {value: 0, text: 'Senior A'},
                {value: 1, text: 'Senior B'},
                {value: 2, text: 'Senior C'},
                {value: 3, text: 'Junior A'},
                {value: 4, text: 'Junior B'},
                {value: 5, text: 'Junior C'},
            ],
        }
    },
}
</script>

<style lang="scss">
#ranking_container{
    #search_filter_holder{
        .date_range_container{
            display: inline-flex;
            // width: 100%;
        }

        .submit_search{
            background: transparent;
            border: solid 1px;
            /*border-radius: 0;*/
            color:  #0d708f;
            margin-right: .5rem;
            font-size: 12px;
            outline: none;
            cursor: pointer;
            box-shadow: none;

            &:hover{
                background: #0d708f;
                color: #fff;
                border-color: #0d708f;
            }
        }
        .reset_search{
            background: transparent;
            border: solid 1px;
            /*border-radius: 0;*/
            color:  #8f0d0d;
            margin-right: 1rem;
            font-size: 12px;
            outline: none;
            cursor: pointer;
            box-shadow: none;

            &:hover{
                background: #8f0d0d;
                color: #fff;
                border-color: #8f0d0d;
            }
        }
    }
}
</style>