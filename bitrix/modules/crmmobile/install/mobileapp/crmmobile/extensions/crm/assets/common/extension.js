/**
 * @module crm/assets/common
 */
jn.define('crm/assets/common', (require, exports, module) => {

	const edit = (color = '#c2c6cf') => `<svg width="24" height="25" viewBox="0 0 24 25" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M16.4358 5.53516L18.9863 8.11258L9.00867 18.0634L6.4581 15.486L16.4358 5.53516ZM5.02549 19.173C5.00137 19.2643 5.02721 19.3607 5.09267 19.4279C5.15986 19.495 5.25634 19.5209 5.34764 19.495L8.19884 18.7269L5.79384 16.3227L5.02549 19.173Z" fill="${color}"/></svg>`;

	const dragButton = `<svg width="6" height="14" viewBox="0 0 6 14" fill="none" xmlns="http://www.w3.org/2000/svg"><g opacity="0.54"><path d="M2 0H0V2H2V0Z" fill="#999999"/><path d="M6 0H4V2H6V0Z" fill="#999999"/><path d="M0 4H2V6H0V4Z" fill="#999999"/><path d="M6 4H4V6H6V4Z" fill="#999999"/><path d="M0 8H2V10H0V8Z" fill="#999999"/><path d="M6 8H4V10H6V8Z" fill="#999999"/><path d="M0 12H2V14H0V12Z" fill="#999999"/><path d="M6 12H4V14H6V12Z" fill="#999999"/></g></svg>`;

	module.exports = { edit, dragButton};
});