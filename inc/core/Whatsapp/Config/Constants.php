<?php
defined('TB_WHATSAPP_AUTORESPONDER') || define('TB_WHATSAPP_AUTORESPONDER', 'sp_whatsapp_autoresponder');
defined('TB_WHATSAPP_CHATBOT') || define('TB_WHATSAPP_CHATBOT', 'sp_whatsapp_chatbot');
defined('TB_WHATSAPP_AI') || define('TB_WHATSAPP_AI', 'sp_whatsapp_ai');
defined('TB_WHATSAPP_SESSIONS') || define('TB_WHATSAPP_SESSIONS', 'sp_whatsapp_sessions');
defined('TB_WHATSAPP_STATS') || define('TB_WHATSAPP_STATS', 'sp_whatsapp_stats');
defined('TB_WHATSAPP_MESSAGE_STATUS') || define('TB_WHATSAPP_MESSAGE_STATUS', 'sp_whatsapp_message_status');
defined('TB_WHATSAPP_CLOUD_DISPATCHES') || define('TB_WHATSAPP_CLOUD_DISPATCHES', 'sp_whatsapp_cloud_dispatches');
defined('TB_WHATSAPP_TEAM_HOLIDAYS') || define('TB_WHATSAPP_TEAM_HOLIDAYS', 'sp_whatsapp_team_holidays');
defined('TB_WHATSAPP_TEMPLATE') || define('TB_WHATSAPP_TEMPLATE', 'sp_whatsapp_template');
defined('TB_WHATSAPP_WEBHOOK') || define('TB_WHATSAPP_WEBHOOK', 'sp_whatsapp_webhook');
defined('TB_WHATSAPP_SCHEDULES') || define('TB_WHATSAPP_SCHEDULES', 'sp_whatsapp_schedules');
defined('TB_WHATSAPP_CONTACTS') || define('TB_WHATSAPP_CONTACTS', 'sp_whatsapp_contacts');
defined('TB_WHATSAPP_PHONE_NUMBERS') || define('TB_WHATSAPP_PHONE_NUMBERS', 'sp_whatsapp_phone_numbers');
defined('TB_WHATSAPP_SUBSCRIBERS') || define('TB_WHATSAPP_SUBSCRIBERS', 'sp_whatsapp_subscriber');
defined('TB_WHATSAPP_HISTORY') || define('TB_WHATSAPP_HISTORY', 'sp_whatsapp_history');
defined('TB_WHATSAPP_CALLRESPONDER') || define('TB_WHATSAPP_CALLRESPONDER', 'sp_whatsapp_callresponder');
defined('TB_WHATSAPP_LIVECHAT') || define('TB_WHATSAPP_LIVECHAT', 'sp_whatsapp_livechat');
defined('TB_WHATSAPP_MESSAGES') || define('TB_WHATSAPP_MESSAGES', 'sp_whatsapp_messages');
defined('TB_WHATSAPP_GROUPARTICIPANTSUPDATE') || define('TB_WHATSAPP_GROUPARTICIPANTSUPDATE', 'sp_whatsapp_grouparticipantsupdate');
defined('TB_WHATSAPP_FLOWS') || define('TB_WHATSAPP_FLOWS', 'sp_whatsapp_flows');
defined('TB_WHATSAPP_FLOW_ASSETS') || define('TB_WHATSAPP_FLOW_ASSETS', 'sp_whatsapp_flow_assets');
defined('TB_WHATSAPP_FLOW_EVENTS') || define('TB_WHATSAPP_FLOW_EVENTS', 'sp_whatsapp_flow_events');
defined('TB_WHATSAPP_FLOW_ENDPOINTS') || define('TB_WHATSAPP_FLOW_ENDPOINTS', 'sp_whatsapp_flow_endpoints');

/**
 * Tipos de templates no `sp_whatsapp_template`.
 *
 * Observação:
 * - A tabela é compartilhada entre Baileys (templates internos) e Cloud API (templates oficiais).
 * - Para evitar duplicação de tabelas no SaaS, reservamos types específicos para o fluxo oficial Meta.
 */
defined('WA_TEMPLATE_TYPE_META_APPROVED') || define('WA_TEMPLATE_TYPE_META_APPROVED', 6); // Templates oficiais aprovados (por WABA/idioma)
defined('WA_TEMPLATE_TYPE_META_STATUS') || define('WA_TEMPLATE_TYPE_META_STATUS', 66);   // Espelho de status (PENDING/REJECTED/APPROVED) por WABA/idioma
defined('WA_TEMPLATE_TYPE_META_DRAFT') || define('WA_TEMPLATE_TYPE_META_DRAFT', 67);     // Rascunho/blueprint criado no Zapmatic (antes de submeter)

/**
 * Status locais de WhatsApp Flow.
 *
 * Observação:
 * - O canal primário da operação é Cloud API.
 * - O Baileys entrará depois como camada de compatibilidade.
 */
defined('WA_FLOW_STATUS_LOCAL_DRAFT') || define('WA_FLOW_STATUS_LOCAL_DRAFT', 'draft');
defined('WA_FLOW_STATUS_LOCAL_READY') || define('WA_FLOW_STATUS_LOCAL_READY', 'ready');
defined('WA_FLOW_STATUS_LOCAL_ARCHIVED') || define('WA_FLOW_STATUS_LOCAL_ARCHIVED', 'archived');
