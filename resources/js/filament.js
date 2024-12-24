import {createApp} from 'vue'
import CustomComponent from './components/CustomComponent.vue'

// 创建 Vue 应用
const app = createApp({})

// 注册组件
app.component('custom-component', CustomComponent)

// 挂载应用
app.mount('#vue-app')
