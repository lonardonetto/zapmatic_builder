const fs = require('fs');
let code = fs.readFileSync('/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js', 'utf8');

const t1 = `const baileysPayload = {
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
st r1 = `const nativeFlowButtons = normalizedButtons.map((entry) => {
                        let btnId = entry.button?.buttonId || "btn_" + Math.random().toString(36).substr(2, 9);
                        let displayTxt = entry.button?.buttonText?.displayText || "Option";
                        return {
                            name: "quick_reply",
                            buttonParamsJson: JSON.stringify({ display_text: displayTxt, id: btnId })
                        };
                    });

st baileysPayload = {
ceMessage: {
textInfo: { deviceListMetadata: {}, deviceListMetadataVersion: 2 },
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
cludes(t1)) {
    code = code.replace(t1, r1);
    fs.writeFileSync('/www/wwwroot/app_zapmatic_app/app_zapmatic_api/waziper/waziper.js', code);
    console.log("PATCH APPLIED SUCCESSFULLY");
} else {
    console.log("TARGET STRING NOT FOUND. FALLBACK TO LINE REPLACEMENT");
    // Backup and attempt to use sed or edit API
}
