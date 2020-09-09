<template>
    <div style="padding: 0px; background-color: white;">
        <div class="utalk_table_search" style="padding: 15px; margin-bottom: 0px;">
            <div class="search_toggle">
                <router-link class="btn btn-primary" v-if="buttonBack.show" :to="buttonBack.to" style="float:left;">Back</router-link>
                <b-button v-if="!buttonBack.show" id="toggle-open-search" class="search_btn" :class="toggle_search == 1 ? 'active' : ''" v-b-toggle.search_filter_holder @click="toggle_search = toggle_search == 1 ? 0 : 1"><fa icon="angle-down" /></b-button>
                <b-button v-if="buttonBack.show" @click="refreshRoute" class="search_btn" :class="toggle_search == 1 ? 'active' : ''" :disabled="table.loading"><fa icon="times" /></b-button>
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
                            <b-col sm="4">
                                <b-form-group>
                                    <label>Date Range</label>
                                    <div class="date_range_container">
                                        <date-picker type="datetime" v-model="search.date_from" :format="format" :lang="lang"></date-picker>
                                        <span> - </span>
                                        <date-picker type="datetime" v-model="search.date_to" :format="format" :lang="lang"></date-picker>
                                    </div>
                                </b-form-group>
                            </b-col>
                            <b-col id="its" sm="2">
                                <b-form-group>
                                    <input type="checkbox" id="checkbox" @click="checkboxChecked" v-model="search.checked">
                                    <label for="checkbox"> Include to search</label>
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
                                    <b-button size="sm" class="submit_search" @click="search_table" :disabled="search.onLoad">
                                        <b-spinner v-if="search.onLoad" small  variant="primary" label="Spinning"></b-spinner>
                                        <span v-else>Search</span>
                                    </b-button>
                                    &nbsp;
                                    <b-button size="sm" @click="reset_search" class="reset_search">Reset</b-button>
                                </div>
                            </b-col>
                        </b-row>
                    </b-form>
                </b-card>
            </b-collapse>
            <b-modal size="sm" ref="transferred-modal" hide-footer centered hide-header>
                <b-row>
                    <b-col md="6"><span style="">Class ID: </span></b-col> <b-col md="6"><span>{{modal.class_id}}</span></b-col>
                    <b-col md="6"><span style="">Date Time: </span></b-col> <b-col md="6"><span>{{modal.class_datetime}}</span></b-col>
                    <b-col md="6"><span style="">New Teacher ID: </span></b-col> <b-col md="6"><span>{{modal.new_teacher_id}}</span></b-col>
                    <b-col md="6"><span style="">Nickname: </span></b-col> <b-col md="6"><span>{{modal.nickname}}</span></b-col>
                </b-row>
                <b-button variant="primary" size="sm" class="mt-3" style="float:right" block @click="closeModal">Close</b-button>
            </b-modal>
        </div>
		<tablelayout
			:header-data="table.collumn_lists"
            headerStyle
			:data-list="table.show_lists"
			:page-count="table.pages"
            :pagination-show="true"
			:getPageNum="getPageNum"
            :currentPage="table.currentPage"
			:action-pass="false"
            :actionClick="buttonClicked"
			:onLoad="table.loading"
            trStyle
            style="font-size: 13px;"
		/>
    </div>
</template>
<script src="./js/classes.js"></script>
<style lang="scss">
    .utalk_table_search{
        #search_filter_holder{
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
            }
            .submit_search:hover{
                background: #0d708f;
                color: #fff;
                border-color: #0d708f;
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
            }
            .reset_search:hover{
                background: #8f0d0d;
                color: #fff;
                border-color: #8f0d0d;
            }

            .date_range_container{
                width:100%;
                display: inline-flex;
                span {
                    padding-left:10px;
                    padding-right: 10px;
                }
            }

            #its{
                padding-top: 38px;
            }

            label {
                font-weight: bold;
            }
        }
    }
    .curriculum{
        label{
            font-weight: bold;
        }
    }
</style>