-- Migration: Tabela para histórico de saúde dos proxies
-- Arquivo: proxy_health_migration.sql
-- Data: 07/01/2025

-- Passo 1: Criar a tabela com o tipo de coluna correto
CREATE TABLE IF NOT EXISTS `sp_proxy_health` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `proxy_id` int(11) NOT NULL,
    `status` enum('online','offline','slow','problematic') DEFAULT 'offline' COMMENT 'Status atual do proxy',
    `latency` float DEFAULT 0 COMMENT 'Latência em milissegundos',
    `error_message` text COMMENT 'Mensagem de erro se houver',
    `last_check` int(11) DEFAULT 0 COMMENT 'Timestamp do último teste',
    `created` int(11) DEFAULT 0 COMMENT 'Timestamp de criação do registro',
    PRIMARY KEY (`id`),
    KEY `proxy_id` (`proxy_id`),
    KEY `created` (`created`),
    KEY `status` (`status`),
    KEY `last_check` (`last_check`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Histórico de monitoramento de saúde dos proxies';

-- Passo 2: Adicionar a chave estrangeira (agora deve funcionar)
ALTER TABLE `sp_proxy_health` 
ADD CONSTRAINT `fk_sp_proxy_health_proxy` 
FOREIGN KEY (`proxy_id`) REFERENCES `sp_proxies`(`id`) ON DELETE CASCADE;

-- Índice composto para consultas de performance
CREATE INDEX `