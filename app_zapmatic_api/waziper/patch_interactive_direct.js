const fs = require('fs');
let code = fs.readFileSync('/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js', 'utf8');

const oldFunc = `const baileysPayload = {
Text,
teractiveButtons: normalizedButtons.map((entry) => entry.button)
{
load.footer = footerText;
{
load.title = headerTitle;
load.subtitle) {
load.subtitle = String(payload.subtitle || "").trim();
st attachHeaderMedia = (field, messageField) => {
st media = payload[field] || payload.interactive?.header?.[messageField];
return false;
load[field] = typeof media === "string" ? { url: media } : media;
load.caption = bodyText;
load.hasMediaAttachment = true;
sPayload.text;
 true;
st newFunc = `const nativeFlowButtons = normalizedButtons.map((entry) => ({
ame: "quick_reply",
ParamsJson: JSON.stringify({
entry.button.buttonText?.displayText || "Option",
try.button.buttonId || "btn" + Math.random().toString(36).substr(2, 9)
st baileysPayload = {
ceMessage: {
textInfo: {
: 2
teractiveMessage: {
title: headerTitle || "", subtitle: String(payload.subtitle || "").trim(), hasMediaAttachment: false },
{ text: bodyText },
text: footerText || "" },
ativeFlowMessage: { buttons: nativeFlowButtons }
st attachHeaderMedia = (field, messageField) => {
st media = payload[field] || payload.interactive?.header?.[messageField];
return false;
st mediaObj = typeof media === "string" ? { url: media } : media;
=== 'image') {
load.viewOnceMessage.message.interactiveMessage.header.hasMediaAttachment = true;
load.viewOnceMessage.message.interactiveMessage.header.imageMessage = mediaObj;
if (field === 'video') {
load.viewOnceMessage.message.interactiveMessage.header.hasMediaAttachment = true;
load.viewOnceMessage.message.interactiveMessage.header.videoMessage = mediaObj;
if (field === 'document') {
load.viewOnceMessage.message.interactiveMessage.header.hasMediaAttachment = true;
load.viewOnceMessage.message.interactiveMessage.header.documentMessage = mediaObj;
 true;
code.replace(oldFunc, newFunc);
fs.writeFileSync('/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js', code);
console.log("PATCH DONE V3");
