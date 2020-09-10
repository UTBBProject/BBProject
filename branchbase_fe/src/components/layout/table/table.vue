
<template>
	<!-- DONT EDIT -->
	<div class="hr-table">
		<div class="outer-table" style="overflow-y: scroll;" :style="tableStyle">
			<table class="table">
				<thead>
					<tr :style="headerStyle" class="bb-table-header">
						<th v-for="(item) of setTableHead" :style=" typeof item.style != 'undefined' ? item.style : 'width:'+(typeof item == 'object' ? item.width : '')" :key="typeof item == 'object' ? item.name : item" v-html="typeof item == 'object' ? item.name : item"></th>
						<th v-if="actionPass" :style="'width:'+actionCollumnWidth+'px;'">Action</th>
					</tr>
				</thead>
				<tbody v-if="!onLoad" style="">
					<slot name="rows" v-if="dataList == null"></slot>
					<template>
						<tr class="bb-table-row" v-for="list in dataList" :key="list.id" :style="trStyle">
							<!-- v-if="((index+1)!=disableColl)" -->
							<td v-for="(item,key, index) of setBody(list)" :key="index" :style="typeof item.style != 'undefined' ? item.style : ''">
								<div v-if="Array.isArray(item)">
									<span v-for="action in item" :key="action.key">
										<button v-if="typeof action.type == 'undefined' && action.type != 'label'" :style="action.btnStyle" class="act-btn" :class="action.btnClass" v-html="action.btnName" @click="actionClick(list.id,action.btnKey,(typeof action.btnCallData !== 'undefined')?(action.btnCallData != ''? action.btnCallData: ''):'')">
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
							<td v-if="actionPass"  class="action-collumn">
								<button v-for="action in actionArray" :key="action.key" class="act-btn" :class="action.btnClass" v-html="action.btnName" @click="actionClick(list.id,action.btnKey,(typeof action.btnCallData !== 'undefined')?(action.btnCallData != ''? action.btnCallData: ''):'')"></button>
							</td>
						</tr>
					</template>
				</tbody>
				<tbody v-else-if="onLoad">
					<td colspan="100" style="text-align:center;">
						<center>
							<loader />
						</center>
					</td>
				</tbody>
				<tbody v-else-if="!dataList.length ">
					<td colspan="100" style="text-align:center;">
						<Nodata />
					</td>
				</tbody>
			</table>
		</div>
		<pagination
			v-if="paginationShow && pageCount>1"
			:page-count="pageCount"
			:click-handler="paginationClickHandler"
			:prev-text="'<'"
			:next-text="'>'"
			:value="currentPage"
			style="padding:15px 0px 10px 20px;"
            >
		</pagination>
	</div>
</template>
<script src="./js/table.js"></script>