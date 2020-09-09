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
            :disableColl="table.disableColl"
            :currentPage="table.currentPage"
            trStyle
            style="font-size: 13px;"
		>
            <template v-slot:rows>
                <tr class="bb-table-row" v-for="list in table.show_lists" :key="list.id" :class="checkIfHasDispute(list.id) ? 'has-dispute' : ''">
                    <td v-for="(item,key, index) of setBody(list)" @click="buttonClicked(list.id,'dispute','',!Array.isArray(item))" :key="index" :style="typeof item.style != 'undefined' ? item.style : ''">
                        <div v-if="Array.isArray(item)">
                            <span v-for="action in item" :key="action.key">
                                <button v-if="typeof action.type == 'undefined' && action.type != 'label'" :style="action.btnStyle" class="act-btn" :class="action.btnClass" v-html="action.btnName" @click="buttonClicked(list.id,action.btnKey,(typeof action.btnCallData !== 'undefined')?(action.btnCallData != ''? action.btnCallData: ''):'')">
                                </button>
                                <span v-else-if="typeof action.type != 'undefined' && action.type == 'label'">
                                    <br v-if="action.nextLine == true">
                                    <span  :class="action.class" :style="action.style" v-html="action.text">
                                    </span>
                                </span>
                            </span>
                        </div>
                        <div v-else v-html="typeof item == 'object' ? item.text : item"></div>
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
                <span class="dispute-label">Dispute Detail: </span><span>{{ table.disputeDetails.discription.desc }}</span><br>
            </div>
            <b-button variant="primary" size="sm" class="mt-3" style="float:right" block @click="closeModal('dispute-modal')">Close</b-button>
        </b-modal>
    </div>
</template>
<script src="./js/earnings.js"></script>
<style src="./css/earnings.css"></style>