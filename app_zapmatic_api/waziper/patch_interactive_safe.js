const fs = require('fs');
let code = fs.readFileSync('/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js', 'utf8');

const t1 = 'const baileysPayload = {\n\t\t\t\t\t\t\ttext: bodyText,\n\t\t\t\t\t\t\tinteractiveButtons: normalizedButtons.map((entry) => entry.button)\n\t\t\t\t\t\t};';
const r1 = `const baileysPayload = {
ceMessage: {
textInfo: {
: 2
teractiveMessage: {


t: false 
{ text: bodyText },
text: footerText },
ativeFlowMessage: {
s: normalizedButtons.map((entry) => entry.button)
code.replace(t1, r1);

const t2 = 'if (footerText) {\n\t\t\t\t\t\t\tbaileysPayload.footer = footerText;\n\t\t\t\t\t\t}\n\n\t\t\t\t\t\tif (headerTitle) {\n\t\t\t\t\t\t\tbaileysPayload.title = headerTitle;\n\t\t\t\t\t\t}\n\n\t\t\t\t\t\tif (payload.subtitle) {\n\t\t\t\t\t\t\tbaileysPayload.subtitle = String(payload.subtitle || "").trim();\n\t\t\t\t\t\t}';
const r2 = `// Headers, footers and bodies are already set in interactiveMessage
load.subtitle) {
load.viewOnceMessage.message.interactiveMessage.header.subtitle = String(payload.subtitle || "").trim();
code.replace(t2, r2);

const t3 = 'const attachHeaderMedia = (field, messageField) => {\n\t\t\t\t\t\t\tconst media = payload[field] || payload.interactive?.header?.[messageField];\n\t\t\t\t\t\t\tif (!media) return false;\n\t\t\t\t\t\t\tbaileysPayload[field] = typeof media === "string" ? { url: media } : media;\n\t\t\t\t\t\t\tbaileysPayload.caption = bodyText;\n\t\t\t\t\t\t\tbaileysPayload.hasMediaAttachment = true;\n\t\t\t\t\t\t\tdelete baileysPayload.text;\n\t\t\t\t\t\t\treturn true;\n\t\t\t\t\t\t};';
const r3 = `const attachHeaderMedia = (field, messageField) => {
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
code.replace(t3, r3);

fs.writeFileSync('/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js', code);
console.log("PATCH DONE SAFELY");
