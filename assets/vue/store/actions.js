/* jshint esversion: 6 */
/* global optimoleDashboardApp */
import Vue from 'vue';
import VueResource from 'vue-resource';

Vue.use( VueResource );

const connectOptimole = function ( { commit, state }, data ) {
	commit( 'toggleConnecting', true );
	Vue.http( {
		url: optimoleDashboardApp.root + '/connect',
		method: 'POST',
		headers: { 'X-WP-Nonce': optimoleDashboardApp.nonce },
		params: { 'req': data.req },
		body: {
			'api_key': data.apiKey,
		},
		responseType: 'json'
	} ).then( function ( response ) {
		commit( 'toggleConnecting', false );
		if ( response.body.code === 'success' ) {
			commit( 'toggleKeyValidity', true );
			commit( 'toggleConnectedToOptml', true );
			commit( 'updateUserData', response.body.data );
			console.log( '%c OptiMole API connection successful.', 'color: #59B278' );
			pingHomepage();
		} else {
			commit( 'toggleKeyValidity', false );
			console.log( '%c Invalid API Key.', 'color: #E7602A' );
		}
	} );
};

const pingHomepage = function() {
	Vue.http( {
		url: optimoleDashboardApp.home_url,
		method: 'GET',
		headers: { 'X-WP-Nonce': optimoleDashboardApp.nonce },
		params: { 'req': 'Ping Homepage' },
		responseType: 'json'
	} );
};

const disconnectOptimole = function ( { commit, state }, data ) {
	commit( 'toggleLoading', true, 'loading' );
	Vue.http( {
		url: optimoleDashboardApp.root + '/disconnect',
		method: 'GET',
		headers: { 'X-WP-Nonce': optimoleDashboardApp.nonce },
		params: { 'req': data.req },
		responseType: 'json'
	} ).then( function ( response ) {
		commit( 'updateUserData', null );
		commit( 'toggleLoading', false );
		if ( response.ok ) {
			commit( 'toggleConnectedToOptml', false );
			console.log( '%c Disconnected from OptiMole API.', 'color: #59B278' );
		} else {
			console.error( response );
		}
	} );
};

const toggleSetting = function ( { commit, state }, data ) {
	commit( 'toggleLoading', true, 'loading' );
	Vue.http( {
		url: optimoleDashboardApp.root + '/update_option',
		method: 'POST',
		headers: { 'X-WP-Nonce': optimoleDashboardApp.nonce },
		params: {
			'req': data.req,
			'option_key': data.option_key,
			'type' : data.type ? data.type : ''
		},
		responseType: 'json'
	} ).then( function ( response ) {
		commit( 'toggleLoading', false );
		if ( response.ok ) {
			console.log( '%c '+ data.option_key +' Toggled.', 'color: #59B278' );
			if( data.option_key === 'image_replacer' ) {
				pingHomepage();
			}
		} else {
			console.error( response );
		}
	} );
};

const retrieveOptimizedImages = function( { commit, state }, component ) {
	Vue.http( {
		url: optimoleDashboardApp.root + '/poll_optimized_images',
		method: 'GET',
		headers: { 'X-WP-Nonce': optimoleDashboardApp.nonce },
		params: { 'req': 'Get Optimized Images' },
		responseType: 'json',
		timeout: 10000
	} ).then( function ( response ) {
		if ( response.ok ) {
			commit('updateOptimizedImages', response );
			component.loading = false;
			console.log( '%c Images Fetched.', 'color: #59B278' );
		} else {
			console.log( '%c No images available.', 'color: #E7602A' );
		}
	} );
};

export default {
	connectOptimole,
	disconnectOptimole,
	toggleSetting,
	retrieveOptimizedImages,
};