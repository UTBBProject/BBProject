<template>
    <div style="padding: 0px; background-color: white;">
        <div class="utalk_table_search" style="padding: 15px; margin-bottom: 0px;">
            <div class="search_toggle">
                <b-button v-if="!buttonBack.show" id="toggle-open-search" class="search_btn" :class="toggle_search == 1 ? 'active' : ''" v-b-toggle.search_filter_holder @click="toggle_search = toggle_search == 1 ? 0 : 1"><fa icon="angle-down" /></b-button>
            </div>

            <b-collapse id="search_filter_holder" class="mt-2">
                <b-card>
                    <b-form>
                        <b-row>
                            <b-col sm="4">
                                <b-form-group>
                                    <label>Teacher ID</label>
                                    <b-input id="teacher-id-input" type="number" min="0" v-model="search.tid"/>
                                </b-form-group>
                            </b-col>
                            <b-col sm="4">
                                <b-form-group>
                                    <label>Employee ID</label>
                                    <b-input id="employee-id-input" type="number" min="0" v-model="search.empid"/>
                                </b-form-group>
                            </b-col>
                            <b-col sm="4">
                                <b-form-group>
                                    <label>Teacher Name</label>
                                    <b-input id="teacher-name-input" type="text" v-model="search.tname"/>
                                </b-form-group>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col sm="4">
                                <b-form-group>
                                    <label>Cut-off Date</label>
                                    <div class="date_range_container">
                                        <date-picker type="date" v-model="search.date_from" :format="format" :lang="lang"></date-picker>
                                        <span> - </span>
                                        <date-picker type="date" v-model="search.date_to" :format="format" :lang="lang"></date-picker>
                                    </div>
                                </b-form-group>
                            </b-col>
                            <b-col id="its" sm="2">
                                <b-form-group>
                                    <input type="checkbox" id="checkbox" v-model="search.checked">
                                    <label for="checkbox"> Include to search</label>
                                </b-form-group>
                            </b-col>
                        </b-row>
                        <b-row>
                            <b-col sm="12">
                                <div style="text-align: right;">
                                    <b-button size="sm" class="submit_search" :disabled="search.onLoad" @click="buttonSearch">
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
        </div>
		<tablelayout
			:header-data="table.collumn_lists"
            headerStyle
			:data-list="table.show_lists"
			:page-count="table.pages"
            :pagination-show="true"
            :currentPage="table.currentPage"
            :getPageNum="getPageNum"
			:action-pass="false"
            :disableColl="[1]"
			:onLoad="table.loading"
            style="font-size: 13px;"
		/>
    </div>
</template>
<script src="./js/payroll.js"></script>
<style lang="scss">
    
</style>