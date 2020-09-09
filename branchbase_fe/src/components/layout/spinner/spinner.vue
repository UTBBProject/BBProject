<template>
	<center v-if="!isWord">
		<div class="lds-roller" style="padding-top: 130px; padding-bottom: 130px;">
			<div></div><div></div><div></div><div></div><div></div><div></div><div></div><div></div>
		</div>
	</center>
	<center v-else>
		<div class="text-loading" style="padding-top: 130px; padding-bottom: 130px; font-size: 16px;">
			Loading<span id="myspan">...</span>
		</div>
	</center>
</template>

<script>

export default {
	props:{
		isWord:{ type: Boolean, default: true }
	},
	data() {
		return {
			
		}
	},
}
</script>

<style scoped>
/* text loading */
/* .text-loading:after {
  content: ' .';
  animation: dots 1s steps(5, end) infinite;
}
@keyframes dots {
  0%, 20% {
    color: rgba(0,0,0,0);
    text-shadow:
      .25em 0 0 rgba(0,0,0,0),
      .5em 0 0 rgba(0,0,0,0);}
  40% {
    color: white;
    text-shadow:
      .25em 0 0 rgba(0,0,0,0),
      .5em 0 0 rgba(0,0,0,0);}
  60% {
    text-shadow:
      .25em 0 0 white,
      .5em 0 0 rgba(0,0,0,0);}
  80%, 100% {
    text-shadow:
      .25em 0 0 white,
      .5em 0 0 white;}} */




/* this is for the loading spinner */
.lds-roller {
	width: 80px;
	height: 80px;
  }
  .lds-roller div {
	animation: lds-roller 1.2s cubic-bezier(0.5, 0, 0.5, 1) infinite;
	transform-origin: 40px 40px;
  }
  .lds-roller div:after {
	content: " ";
	display: block;
	position: absolute;
	width: 7px;
	height: 7px;
	border-radius: 50%;
	background: #696969;
	background: var(--loading_color);
	margin: -4px 0 0 -4px;
  }
  .lds-roller div:nth-child(1) {
	animation-delay: -0.036s;
  }
  .lds-roller div:nth-child(1):after {
	top: 63px;
	left: 63px;
  }
  .lds-roller div:nth-child(2) {
	animation-delay: -0.072s;
  }
  .lds-roller div:nth-child(2):after {
	top: 68px;
	left: 56px;
  }
  .lds-roller div:nth-child(3) {
	animation-delay: -0.108s;
  }
  .lds-roller div:nth-child(3):after {
	top: 71px;
	left: 48px;
  }
  .lds-roller div:nth-child(4) {
	animation-delay: -0.144s;
  }
  .lds-roller div:nth-child(4):after {
	top: 72px;
	left: 40px;
  }
  .lds-roller div:nth-child(5) {
	animation-delay: -0.18s;
  }
  .lds-roller div:nth-child(5):after {
	top: 71px;
	left: 32px;
  }
  .lds-roller div:nth-child(6) {
	animation-delay: -0.216s;
  }
  .lds-roller div:nth-child(6):after {
	top: 68px;
	left: 24px;
  }
  .lds-roller div:nth-child(7) {
	animation-delay: -0.252s;
  }
  .lds-roller div:nth-child(7):after {
	top: 63px;
	left: 17px;
  }
  .lds-roller div:nth-child(8) {
	animation-delay: -0.288s;
  }
  .lds-roller div:nth-child(8):after {
	top: 56px;
	left: 12px;
  }
  @keyframes lds-roller {
	0% {
		transform: rotate(0deg);
	}
	100% {
		transform: rotate(360deg);
	}
  }
</style>