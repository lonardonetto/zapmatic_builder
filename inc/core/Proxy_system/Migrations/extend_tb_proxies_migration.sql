-- Migration: Extensão da tabela sp_proxies com novos campos
-- Arquivo: extend_tb_proxies_migration.sql
-- Data: 07/01/2025
-- Nota: Este comando pode falhar se as colunas já existirem. 
-- Se isso acontecer, remova as linhas ADD COLUMN das colunas que já existem e execute novamente.

ALTER TABLE `sp_proxies` 
ADD COLUMN `timeout` int(11) DEFAULT 120 COMMENT 'Timeout em segundos para requisições',
ADD COLUMN `retry_count` int(11) DEFAULT 2 COMMENT 'Número de tentativas em caso de falha',
ADD COLUMN `max_connections` int(11) DEFAULT 50 COMMENT 'Máximo de conexões simultâneas permitidas',
ADD COLUMN `health_score` float DEFAULT 100.0 COMMENT 'Score de saúde do proxy (0-100)',
ADD COLUMN `last_health_check` int(11) DEFAULT 0 COMMENT 'Timestamp da última verificação de saúde',
ADD COLUMN `total_requests` int(11) DEFAULT 0 COMMENT 'Total de requisições processadas',
ADD COLUMN `failed_requests` int(11) DEFAULT 0 COMMENT 'Total de requisições que falharam';

-- Adicionar índices para os novos campos
-- Nota: Estes comandos podem falhar se os índices já existirem, o que é seguro ignorar.
CREATE INDEX `idx_health_score` ON `sp_proxies` (`health_score`);
CREATE INDEX `idx_last_health_check` ON `sp_proxies` (`last_health_check`);
CREATE INDEX `idx_total_requests` ON `sp_proxies` (`total_requests`);

-- Atualizar valores padrão para proxies existentes
UPDATE `sp_proxies` 
SET 
    `timeout` = 120,
    `retry_count` = 2,
    `max_connections` = 50,
    `health_score` = 100.0,
    `last_health_check` = 0,
    `total_requests` = 0,
    `failed_requests` = 0
WHERE 
    `timeout` IS NULL 
    OR `retry_count` IS NULL 
    OR `max_connections` IS NULL 
    OR `health_score` IS NULL; 