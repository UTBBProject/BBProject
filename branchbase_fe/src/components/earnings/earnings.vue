<template>
    <div style="padding: 0; background-color: white;">
        <!-- Search Here -->
        <div class="utalk_table_search" style="padding: 15px; margin-bottom: 0px;">
            <div class="search_toggle">
               
                <b-button id="toggle-open-search" class="search_btn" :class="toggle_search == 1 ? 'active' : ''" v-b-toggle.search_filter_holder @click="toggle_search = toggle_search == 1 ? 0 : 1">
                    <fa icon="angle-down" /></b-button>
            </div>

            <b-collapse id="search_filter_holder" class="mt-2">
                <b-card>
                    <b-form>
                        <b-row>
                            <b-col sm="3">
                                <b-form-group>
                                    <label>Class ID</label>
                                    <b-input id="class-id-input" type="number" min="0" v-model="search.class_id" v-on:keypress="numberOnly"/>
                                </b-form-group>
                            </b-col>
                            <b-col sm="3">
                                <b-form-group>
                                    <label>Date From</label>
                                    <div class="">
                                        <date-picker type="datetime" v-model="search.date_from" :editable="false" format="YYYY-MM-DD HH:mm:ss" :lang="'en'" value-type="format"></date-picker>
                                    </div>
                                </b-form-group>
                            </b-col>
                            <b-col sm="3">
                                <b-form-group>
                                    <label>Date To</label>
                                    <div class="">
                                        <date-picker type="datetime" v-model="search.date_to" :editable="false" format="YYYY-MM-DD HH:mm:ss" :lang="'en'" value-type="format"></date-picker>
                                    </div>
                                </b-form-group>
                            </b-col>
                            <b-col sm="3">
                                <b-form-group>
                                    <label>Cancel Status</label>
                                    <b-form-select id="cancel-status-field" v-model="search.cancelstatus" :options="cs_option"/>
                                </b-form-group>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col sm="3">
                                <b-form-group>
                                    <label>Status</label>
                                    <b-form-select id="status-field" v-model="search.status" :options="stats_option"/>
                                </b-form-group>
                            </b-col>
                            <b-col sm="3">
                                <b-form-group>
                                    <label>Comment</label>
                                    <b-form-select id="comment-field" type="text" v-model="search.comment" :options="comment_option"/>
                                </b-form-group>
                            </b-col>
                            <b-col sm="3">
                                <b-form-group>
                                    <label>Checked In</label>
                                    <b-form-select id="checkedin-field" v-model="search.checkedin" :options="checked_option"/>
                                </b-form-group>
                            </b-col>
                            <b-col sm="3">
                                <b-form-group>
                                    <label>Category</label>
                                    <b-form-select id="category-field" v-model="search.category" :options="category_option"/>
                                </b-form-group>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col sm="12">
                                <div style="text-align: right;">
                                    <b-button size="sm" class="submit_search" style="width:70px" @click="searchButton" :disabled="search.onLoad">
                                        <b-spinner v-if="search.onLoad" small  variant="primary" label="Spinning"></b-spinner>
                                        <span v-else>Search</span>
                                    </b-button>
                                    &nbsp;
                                    <b-button size="sm" class="reset_search" @click="resetSearch">Reset</b-button>
                                </div>
                            </b-col>
                        </b-row>
                    </b-form>
                </b-card>
            </b-collapse>
        </div>
        <!-- Shows the Computation -->
        <div class="detail-box" :class="badge_count.length > 0 ? 'hhh-50': 'mtm-20'">
            <div :style=" badge_count.length > 0 ? 'margin-top:-5px;' : 'height: 30px;'">
                <span class="warning-note"
                :style=" badge_count.length > 0 ? 'margin-top:-5px;' : ''"
                >Note: January 8, 2020 onwards will have generated logs</span>
            </div>
            <span v-for="(count,key,index) of badge_count" :key="index" class="detail-item" :class="' detail-item-'+count.class">{{ count.title }}<span class="detail-badge">{{ count.count }}</span> </span>
        </div>

        <!-- table here -->
		<tablelayout
			:header-data="table.collumn_lists"
			:page-count="table.pages"
			:getPageNum="getPageNum"
			:actionClick="buttonClicked"
			:onLoad="table.loading"
            :currentPage="table.currentPage"
            trStyle
            style="font-size: 13px;"
		>
            <template v-slot:rows>
                <td v-if="!table.show_lists.length" colspan="100" style="text-align:center;">
					<NoData />
				</td>
                <tr 
                    v-else
                    v-for="list in table.show_lists" 
                    :key="list.id"
                    @click="buttonClicked('dispute','', list)"
                    :class="list.dispute_status != null && list.dispute_description != null ? 'has-dispute' : ''"
                >
                    <td>
                        {{ list.id }}
                    </td>
                    <td>
                        {{ list.class_date }}
                    </td>
                    <td >
                        <button 
                            style="width: 73px;"
                            class="act-btn"
                            :class="list.class_status == 'Valid' ? 'act-primary act-small':( 
                                list.class_status == 'Transferred' ? 'act-info act-small': (
                                    list.class_status=='Invalid' ? 'act-danger act-small': 'act-warning act-small'
                                )
                            )"
                            v-on:click.stop=""
                            @click="buttonClicked('status', list.class_id)"
                        >
                            {{ list.class_status }}
                        </button><br>
                        <small
                            v-if="list.dispute_status_num != 0"
                            :class="list.dispute_status_num > 0 ? (list.dispute_status_num == 1 ? 'label-success':'label-danger' ) : ''"
                        >*Dispute</small>
                    </td>
                    <td>
                        <button
                            class="act-btn act-grey"
                            style="width: 60px;"
                            v-on:click.stop=""
                            @click="buttonClicked('amount',list.class_id,{
                                class_rate: list.amount!=0 || list.amount!=''?list.class_rate:0,
                                week_pay: list.weekend_pay,
                                sub_class: list.ct_amount,
                                amount: list.amount,
                                video_duration: list.video_duration,
                                video_deduction: list.video_deduction,
                                note: list.note
                            })"
                        >
                            {{ list.video_deduction != 0 && list.video_deduction != "" ? list.video_deduction: list.amount }}
                        </button>
                    </td>
                    <td>
                        {{parseInt(list.class_count)}}
                    </td>
                    <td>
                        {{ list.running_class_count}}
                    </td>
                    <td>
                        {{ list.current_level }}
                    </td>
                </tr>
            </template>
		</tablelayout>




        <!-- modal clicking the amount-->
        <b-modal size="sm" ref="amount-modal" hide-footer title="Amount" centered hide-header>
            <div v-if="modal.amount > 0" class="d-block">
                <span style="">Class Rate: </span><span>{{ modal.class_rate }}</span><br>
                <span style="">Week-end Bonus: </span><span>{{ modal.week_pay }}</span><br>
                <span style="">Sub-class: </span><span>{{ modal.sub_class }}</span><br>
            </div>
            <div v-else-if="modal.amount < 1" class="d-block">
                
                <div v-if="(modal.amount == 0 && modal.class_rate == 0)">
                    <span style="margin-right: 10px;">Invalid Class</span><br>
                </div>
                <div v-else-if="(modal.amount < 0 && modal.video_deduction == 0)  || modal.class_rate > 0">
                    <span>Transfered Class</span><br>
                    <span>Transfer Reason: {{ modal.note }}</span><br>
                    <div v-on:mouseover="check_scheme(modal.amount,modal.class_rate,'transferred')" v-b-tooltip.hover :title="scheme">
                    <span  style="margin-right: 10px;">Class Rate({{ modal.class_rate }}) * {{ (modal.amount/(modal.class_rate))*100 }}%:</span>  <span style="font-weight: bold;border-bottom: 1px solid grey; ">{{ modal.amount }}</span>
                    </div>
                </div>
                <div v-else-if="modal.video_deduction < 0">
                    <span v-on:mouseover="check_scheme(modal.video_deduction,modal.class_rate,'video')" v-b-tooltip.hover :title="scheme" style="margin-right: 10px;">Video Deduction: {{ modal.video_deduction }}</span><br>
                </div>
            </div>
            <b-button variant="primary" size="sm" class="mt-3" style="float:right" block @click="closeModal('amount-modal')">Close</b-button>
        </b-modal>




        <!-- This is when click the row-->
        <b-modal size="sm" ref="dispute-modal" hide-footer title="Amount" centered hide-header>
            <div class="d-block" v-if="!isObjectEmpty(table.disputeDetails)">
                <span class="dispute-label">Date: </span><span>{{ table.disputeDetails.discription.date }}</span><br>
                <span class="dispute-label">Category: </span><span>{{ JSON.parse(table.disputeDetails.discription.tag1)[0] }}</span><br>
                <span class="dispute-label">Sub-Category: </span><span>{{ JSON.parse(table.disputeDetails.discription.tag2)[0] }}</span><br>
                <span class="dispute-label">Dispute Detail: </span><span>{{ table.disputeDetails.discription.dispute_detail }}</span><br>
                <span class="dispute-label" >Dispute Status: </span><span>{{ table.disputeDetails.status }}</span><br>
                <span class="dispute-label" >Dispute Result: </span><span>{{ table.disputeDetails.dispute_result }}</span>
            </div>
            <b-button variant="primary" size="sm" class="mt-3" style="float:right" block @click="closeModal('dispute-modal')">Close</b-button>
        </b-modal>

    </div>
</template>
<script src="./js/earnings.js"></script>
<style src="./css/earnings.css"></style>