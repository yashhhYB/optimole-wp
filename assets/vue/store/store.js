/* global optimoleDashboardApp */
/*jshint esversion: 6 */
import Vue from 'vue';
import Vuex from 'vuex';
import VueResource from 'vue-resource';
import mutations from './mutations';
import actions from './actions';

Vue.use( Vuex );
Vue.use( VueResource );

const store = new Vuex.Store( {
	state: {
		loading: false,
		connected: optimoleDashboardApp.connection_status ? true : false,
		apiKey: optimoleDashboardApp.api_key ? optimoleDashboardApp.api_key : '',
		apiKeyValidity: true,
		userData: optimoleDashboardApp.user_data ? optimoleDashboardApp.user_data : null
	},
	mutations,
	actions
} );

export default store;

