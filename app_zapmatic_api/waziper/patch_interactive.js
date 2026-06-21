const fs = require('fs');

let code = fs.readFileSync('/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js', 'utf8');

const target1 = `const baileysPayload = {
Text,
teractiveButtons: normalizedButtons.map((entry) => entry.button)
st replace1 = `const baileysPayload = {
ceMessage: {
textInfo: {
: 2
teractiveMessage: {


t: false 
{ text: bodyText },
text: footerText },
ativeFlowMessage: {
s: normalizedButtons.map((entry) => entry.button)
code.replace(target1, replace1);

const target2 = `if (footerText) {
load.footer = footerText;
{
load.title = headerTitle;
load.subtitle) {
load.subtitle = String(payload.subtitle || "").trim();
st replace2 = `// Handling media attachments
load.subtitle) {
load.viewOnceMessage.message.interactiveMessage.header.subtitle = String(payload.subtitle || "").trim();
code.replace(target2, replace2);

const targetMedia = `const attachHeaderMedia = (field, messageField) => {
st media = payload[field] || payload.interactive?.header?.[messageField];
return false;
load[field] = typeof media === "string" ? { url: media } : media;
load.caption = bodyText;
load.hasMediaAttachment = true;
sPayload.text;
 true;
st replaceMedia = `const attachHeaderMedia = (field, messageField) => {
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
code.replace(targetMedia, replaceMedia);

fs.writeFileSync('/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js', code);
console.log("PATCH DONE");
