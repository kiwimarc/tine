<template>
  <BModal v-model="props.otherConfigs.visible"
          :title="props.opt.title"
          :title-class="'title'"
          :modal-class="'bootstrap-scope'"
          :hide-header-close="!props.opt.closable"
          :hide-footer="!props.opt.buttons"
          :centered="true"
          :no-fade="true"
          :lazy="true"
          :noCloseOnBackdrop="true"
          @close="closeBox" :style="{'z-index': otherConfigs.zIndex}"
  >
    <template #default>
      <div class="container">
        <div class="row">
          <div class="col-3" v-if="props.opt.icon">
            <PersonaContainer :iconName="opt.icon" :skinColor="opt?.skinColor"/>
          </div>
          <div class="col">
            <div class="pb-1 mb-1">
              <span v-html="props.opt.msg"></span>
            </div>
            <div class="pb-1 mb-1 ext-mb-textarea" v-if="textAreaElVisiblity">
              <BFormTextarea v-model="textElValue" :rows="textAreaHeight" ref="textAreaField"/>
            </div>
            <div class="pb-1 mb-1" v-if="textBoxElVisibility">
              <BFormInput v-model="textElValue" ref="inputField"/>
            </div>
            <div v-if="progressBarVisibility">
              <BProgress :max="1" height="2em">
                <BProgressBar
                  :animated="props.opt.wait"
                  :value="props.opt.wait ? 1 : props.opt.progressValue"
                  variant="primary">
                  <span v-html="props.opt.progressText"></span>
                </BProgressBar>
              </BProgress>
            </div>
          </div>
        </div>
      </div>
    </template>
    <template #footer>
      <div>
        <BButton class="mx-1 x-tool-close vue-button" v-for="button in buttonToShow" @click="button.clickHandler"
                 :key="button.name" :class="button.class">{{ button.name }}
        </BButton>
      </div>
    </template>
  </BModal>
</template>

<script setup>
// TODO: change the progressBar according to `props.opt.waitConfig` if available
// NOTE: Ext.MessageBox.wait is currently not used with any waitConfig, so
// the implementation is not given top priority
import {computed, inject, onBeforeMount, ref, watch} from "vue"

import {SymbolKeys} from ".";

const ExtEventBus = inject(SymbolKeys.ExtEventBusInjectKey);
const textBoxElVisibility = ref(false);
const inputField = ref();
const textAreaElVisiblity = ref(false);
const textAreaField = ref();
const progressBarVisibility = ref(false);
const textAreaHeight = ref(0);
const textElValue = ref("");

const init = async function () {
  if (props.opt.prompt) {
    if (props.opt.multiline) {
      textBoxElVisibility.value = false;
      textAreaElVisiblity.value = true;
      textAreaHeight.value = (typeof props.opt.multiline === "number") ? props.opt.multiline / 25 : props.opt.defaultTextAreaHeight;
    } else {
      textBoxElVisibility.value = true;
      textAreaElVisiblity.value = false;
    }
  } else {
    textBoxElVisibility.value = false;
    textAreaElVisiblity.value = false;
  }
  textElValue.value = props.opt.value;
  progressBarVisibility.value = props.opt.progress === true || props.opt.wait === true;

  _.delay(() => {
    if(textAreaElVisiblity.value) textAreaField.value.focus()
    if(textBoxElVisibility.value) inputField.value.focus()
  },20)
}

const props = defineProps({
  opt: Object,
  otherConfigs: Object
})

const closeBox = () => {
  if (props.opt.closable) {
    ExtEventBus.emit("close");
  }
}

const buttonToShow = computed(() => {
  if (props.opt.buttons) {
    return Object.keys(props.opt.buttons).map(buttonName => {
      return {
        clickHandler: () => {
          ExtEventBus.emit("buttonClicked", {
            buttonName,
            textElValue: textElValue.value,
          })
        },
        name: props.otherConfigs.buttonText[buttonName],
        class: `${buttonName}-button`
      }
    });
  } else {
    return []
  }
})

watch(() => props.otherConfigs.visible, newVal => {
  if (newVal) init();
})

onBeforeMount(async () => {
  await init();
})
</script>

<style lang="scss">
.vue-button.x-tool-close {
  background-image: none !important;
}
</style>
