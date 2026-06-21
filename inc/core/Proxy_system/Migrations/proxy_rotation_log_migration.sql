-- Migration: Tabela para log de rotações de proxy
-- Arquivo: proxy_rotation_log_migration.sql
-- Data: 07/01/2025

-- Passo 1: Criar a tabela com os tipos de coluna corretos
CREATE TABLE IF NOT EXISTS `sp_proxy_rotation_log` (
    `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `account_id` int(11) NOT NULL COMMENT 'ID da conta que teve proxy alterado',
    `old_proxy_id` int(11) DEFAULT NULL COMMENT 'ID do proxy anterior',
    `new_proxy_id` int(11) DEFAULT NULL COMMENT 'ID do novo proxy',
    `reason` enum('scheduled','performance','failover','manual') DEFAULT 'scheduled' COMMENT 'Motivo da rotação',
    `performance_metrics` text COMMENT 'Métricas de performance em JSON',
    `created` int(11) DEFAULT 0 COMMENT 'Timestamp da rotação',
    PRIMARY KEY (`id`),
    KEY `account_id` (`account_id`),
    KEY `old_proxy_id` (`old_proxy_id`),
    KEY `new_proxy_id` (`new_proxy_id`),
    KEY `created` (`created`),
    KEY `reason` (`reason`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Log de rotações automáticas e manuais de proxies';

-- Passo 2: Adicionar as chaves estrangeiras (agora deve funcionar)
ALTER TABLE `sp_proxy_rotation_log` 
ADD CONSTRAINT `fk_sp_rotation_account` FOREIGN KEY (`account_id`) REFERENCES `sp_accounts`(`id`) ON DELETE CASCADE,
ADD CONSTRAINT `fk_sp_rotation_old_proxy` FOREIGN KEY (`old_proxy_id`) REFERENCES `sp_proxies`(`id`) ON DELETE SET NULL,
ADD CONSTRAINT `fk_sp_rotation_new_proxy` FOREIGN KEY (`new_proxy_id`) REFERENCES `sp_proxies`(`id`) ON DELETE SET NULL;

-- Índice composto para consultas por conta e período
CREATE INDEX `idx_account_created` ON `sp_proxy_rotation_log` (`account_id`, `created`);

-- Índice para consultas por motivo de rotação
CREATE INDEX `idx_reason_created` ON `sp_proxy_rotation_log` (`reason`, `created`);

-- Índice para relatórios de uso de proxy
CREATE INDEX `idx_proxy_usage` ON `sp_proxy_rotation_log` (`old_proxy_id`, `new_proxy_id`, `created`); 