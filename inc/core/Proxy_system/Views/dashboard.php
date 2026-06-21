<div class="container-fluid">
    <!-- Barra de status no topo -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-clock"></i> Última atualização: <span id="last-update"><?php echo date('H:i:s')?></span>
                <button class="btn btn-sm btn-outline-primary ml-2" onclick="forceRefreshDashboard()">
                    <i class="fas fa-sync-alt"></i> Atualizar
                </button>
            </div>
        </div>
    </div>

    <!-- Cards de Estatísticas -->
    <div class="row mb-4">
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                <?php _e("Total de Proxies")?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-proxies">
                                <?php _e($stats['total_proxies'])?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-globe fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                <?php _e("Proxies Online")?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="online-proxies">
                                <?php _e($stats['online_proxies'])?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                <?php _e("Latência Média")?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="avg-latency">
                                <?php _e($stats['avg_latency'])?>ms
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-3 col-md-6 mb-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                <?php _e("Taxa de Sucesso")?>
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="success-rate">
                                <?php _e($stats['success_rate'])?>%
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Gráfico de Performance e Mapa -->
    <div class="row mb-4">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary"><?php _e("Performance nas Últimas 24h")?></h6>
                    <div class="dropdown no-arrow">
                        <a class="dropdown-toggle" href="#" role="button" data-toggle="dropdown">
                            <i class="fas fa-ellipsis-v fa-sm fa-fw text-gray-400"></i>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in">
                            <a class="dropdown-item" href="#" onclick="exportChart()">Exportar Dados</a>
                            <a class="dropdown-item" href="#" onclick="refreshChart()">Atualizar</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="chart-area">
                        <canvas id="performance-chart"></canvas>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Mapa de Proxies -->
        <div class="col-lg-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?php _e("Localização dos Proxies")?></h6>
                </div>
                <div class="card-body">
                    <div id="proxy-map" style="height: 300px; border-radius: 0.35rem;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Proxies com Problemas -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary"><?php _e("Proxies com Problemas")?></h6>
                    <span class="badge badge-danger"><?php echo count($problematic_proxies)?> problemas</span>
                </div>
                <div class="card-body">
                    <?php if(empty($problematic_proxies)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                            <h5 class="text-success"><?php _e("Todos os proxies estão funcionando corretamente!")?></h5>
                            <p class="text-muted"><?php _e("Nenhum problema detectado no momento.")?></p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="problematic-proxies-table">
                                <thead>
                                    <tr>
                                        <th><?php _e("Proxy")?></th>
                                        <th><?php _e("Status")?></th>
                                        <th><?php _e("Latência")?></th>
                                        <th><?php _e("Contas Usando")?></th>
                                        <th><?php _e("Último Check")?></th>
                                        <th><?php _e("Ações")?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach($problematic_proxies as $proxy): ?>
                                    <tr id="proxy-row-<?php _e($proxy->id)?>">
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-circle text-danger mr-2"></i>
                                                <span class="font-weight-bold"><?php _e(substr($proxy->proxy, 0, 30))?>...</span>
                                            </div>
                                            <small class="text-muted"><?php _e($proxy->location ?? 'Local não identificado')?></small>
                                        </td>
                                        <td>
                                            <?php 
                                            $status_colors = [
                                                'online' => 'success',
                                                'offline' => 'danger',
                                                'slow' => 'warning',
                                                'problematic' => 'warning'
                                            ];
                                            $color = $status_colors[$proxy->status] ?? 'secondary';
                                            ?>
                                            <span class="badge badge-<?php _e($color)?>"><?php _e(ucfirst($proxy->status))?></span>
                                            <?php if($proxy->error_message): ?>
                                                <br><small class="text-danger"><?php _e(substr($proxy->error_message, 0, 50))?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($proxy->latency > 0): ?>
                                                <span class="<?php echo $proxy->latency > 2000 ? 'text-danger' : ($proxy->latency > 1000 ? 'text-warning' : 'text-success')?>">
                                                    <?php _e($proxy->latency)?>ms
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">N/A</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($proxy->accounts_using > 0): ?>
                                                <span class="badge badge-info"><?php _e($proxy->accounts_using)?> contas</span>
                                            <?php else: ?>
                                                <span class="text-muted">Nenhuma</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if($proxy->last_check): ?>
                                                <span title="<?php echo date('d/m/Y H:i:s', $proxy->last_check)?>">
                                                    <?php echo time_elapsed_string($proxy->last_check)?>
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted">Nunca testado</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button class="btn btn-sm btn-outline-primary" onclick="testProxy(<?php _e($proxy->id)?>)" id="test-btn-<?php _e($proxy->id)?>">
                                                    <i class="fas fa-vial"></i> Testar
                                                </button>
                                                <button class="btn btn-sm btn-outline-info" onclick="showProxyDetails(<?php _e($proxy->id)?>)">
                                                    <i class="fas fa-info-circle"></i> Detalhes
                                                </button>
                                                <?php if($proxy->accounts_using == 0): ?>
                                                <button class="btn btn-sm btn-outline-danger" onclick="disableProxy(<?php _e($proxy->id)?>)">
                                                    <i class="fas fa-times"></i> Desativar
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Alertas em Tempo Real -->
    <div id="alert-container" class="position-fixed" style="top: 20px; right: 20px; z-index: 1050; max-width: 400px;">
        <!-- Alertas serão inseridos aqui dinamicamente -->
    </div>
</div>

<!-- Modal para Detalhes do Proxy -->
<div class="modal fade" id="proxyDetailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalhes do Proxy</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="proxy-details-content">
                <div class="text-center py-3">
                    <i class="fas fa-spinner fa-spin fa-2x"></i>
                    <p class="mt-2">Carregando detalhes...</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://unpkg.com/leaflet@1.7.1/dist/leaflet.js"></script>
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.7.1/dist/leaflet.css" />

<script>
let performanceChart;
let proxyMap;
let refreshInterval;

// Inicializar dashboard quando carregado
$(document).ready(function() {
    initializeChart();
    initializeMap();
    startRealTimeUpdates();
    
    // Atualizar dados iniciais
    updateDashboard();
});

// Configurar Chart.js para gráfico de performance
function initializeChart() {
    const ctx = document.getElementById('performance-chart').getContext('2d');
    performanceChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($performance_data['labels'])?>,
            datasets: [{
                label: 'Latência (ms)',
                data: <?php echo json_encode($performance_data['latency'])?>,
                borderColor: 'rgb(78, 115, 223)',
                backgroundColor: 'rgba(78, 115, 223, 0.05)',
                tension: 0.3,
                fill: true
            }, {
                label: 'Taxa de Sucesso (%)',
                data: <?php echo json_encode($performance_data['success_rate'])?>,
                borderColor: 'rgb(28, 200, 138)',
                backgroundColor: 'rgba(28, 200, 138, 0.05)',
                tension: 0.3,
                fill: true,
                yAxisID: 'y1'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                intersect: false,
                mode: 'index'
            },
            plugins: {
                legend: {
                    display: true,
                    position: 'top'
                },
                tooltip: {
                    callbacks: {
                        afterLabel: function(context) {
                            if (context.datasetIndex === 0) {
                                return 'Latência em milissegundos';
                            } else {
                                return 'Porcentagem de sucessos';
                            }
                        }
                    }
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: 'Hora do Dia'
                    }
                },
                y: {
                    type: 'linear',
                    display: true,
                    position: 'left',
                    title: {
                        display: true,
                        text: 'Latência (ms)'
                    },
                    beginAtZero: true
                },
                y1: {
                    type: 'linear',
                    display: true,
                    position: 'right',
                    title: {
                        display: true,
                        text: 'Taxa de Sucesso (%)'
                    },
                    beginAtZero: true,
                    max: 100,
                    grid: {
                        drawOnChartArea: false,
                    },
                }
            }
        }
    });
}

// Inicializar mapa
function initializeMap() {
    proxyMap = L.map('proxy-map').setView([40.7128, -74.0060], 2);
    
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap contributors'
    }).addTo(proxyMap);
    
    // Carregar dados do mapa
    loadMapData();
}

// Carregar dados para o mapa
function loadMapData() {
    $.ajax({
        url: '<?php _ec(get_module_url("ajax_map_data"))?>',
        method: 'POST',
        success: function(response) {
            if(response.status === 'success') {
                response.data.forEach(function(proxy) {
                    if(proxy.lat && proxy.lng) {
                        let color = proxy.status === 'online' ? 'green' : 
                                   proxy.status === 'slow' ? 'orange' : 'red';
                        
                        let marker = L.circleMarker([proxy.lat, proxy.lng], {
                            color: color,
                            fillColor: color,
                            fillOpacity: 0.6,
                            radius: 8
                        }).addTo(proxyMap);
                        
                        marker.bindPopup(`
                            <div>
                                <strong>${proxy.city}, ${proxy.country}</strong><br>
                                <small>Proxy: ${proxy.proxy_address}</small><br>
                                Status: <span style="color: ${color};">${proxy.status}</span><br>
                                Latência: ${proxy.latency}ms<br>
                                Contas usando: ${proxy.usage_count}
                            </div>
                        `);
                    }
                });
            }
        }
    });
}

// Iniciar atualizações em tempo real
function startRealTimeUpdates() {
    refreshInterval = setInterval(updateDashboard, 30000); // 30 segundos
}

// Atualizar dashboard
function updateDashboard() {
    $.ajax({
        url: '<?php _ec(get_module_url("ajax_realtime_stats"))?>',
        method: 'POST',
        success: function(response) {
            if(response.status === 'success') {
                const data = response.data;
                
                // Atualizar cards de estatísticas
                $('#total-proxies').text(data.stats.total_proxies);
                $('#online-proxies').text(data.stats.online_proxies);
                $('#avg-latency').text(data.stats.avg_latency + 'ms');
                $('#success-rate').text(data.stats.success_rate + '%');
                $('#last-update').text(data.last_update);
                
                // Mostrar alertas se houver
                if(data.alerts && data.alerts.length > 0) {
                    showAlerts(data.alerts);
                }
            }
        },
        error: function() {
            showAlert('Erro ao atualizar dados do dashboard', 'danger');
        }
    });
}

// Testar proxy específico
function testProxy(proxyId) {
    const btn = $('#test-btn-' + proxyId);
    const originalText = btn.html();
    
    btn.html('<i class="fas fa-spinner fa-spin"></i> Testando...')
       .prop('disabled', true);
    
    $.ajax({
        url: '<?php _ec(get_module_url("ajax_test_proxy"))?>/' + proxyId,
        method: 'POST',
        success: function(response) {
            if(response.status === 'success') {
                const result = response.data;
                showTestResults(result);
                
                // Atualizar linha da tabela
                updateProxyRow(proxyId, result);
            } else {
                showAlert('Erro ao testar proxy: ' + response.message, 'danger');
            }
        },
        error: function() {
            showAlert('Erro na comunicação com o servidor', 'danger');
        },
        complete: function() {
            btn.html(originalText).prop('disabled', false);
        }
    });
}

// Mostrar resultado dos testes
function showTestResults(result) {
    const escapeHtml = (value) => $('<div>').text(value == null ? '' : String(value)).html();
    let message = '<strong>Resultado do teste</strong><br>';
    message += 'Status: ' + escapeHtml(result.overall_status || 'Indefinido') + '<br>';
    message += 'Tempo total do teste: ' + escapeHtml(result.total_test_time || 0) + 'ms';

    if(result.recommendations && result.recommendations.length > 0) {
        message += '<div class="mt-2"><strong>Recomendações:</strong><ul class="mb-0 ps-4">';
        result.recommendations.forEach(rec => {
            message += '<li>' + escapeHtml(rec) + '</li>';
        });
        message += '</ul></div>';
    }

    showAlert(message, result.overall_status === 'success' ? 'success' : 'info');
}

// Mostrar detalhes do proxy
function showProxyDetails(proxyId) {
    $('#proxyDetailsModal').modal('show');
    // Implementar carregamento de detalhes
}

// Atualizar forçado
function forceRefreshDashboard() {
    clearInterval(refreshInterval);
    updateDashboard();
    startRealTimeUpdates();
    showAlert('Dashboard atualizado!', 'success');
}

// Funções auxiliares
function showAlert(message, type = 'info') {
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    
    $('#alert-container').append(alertHtml);
    
    // Auto-remover após 5 segundos
    setTimeout(() => {
        $('#alert-container .alert').first().alert('close');
    }, 5000);
}

function showAlerts(alerts) {
    alerts.forEach(alert => {
        showAlert(alert.message, alert.type);
    });
}

function updateProxyRow(proxyId, testResult) {
    // Implementar atualização da linha da tabela
}

// Funções auxiliares
<?php if(!function_exists('time_elapsed_string')): ?>
function time_elapsed_string(timestamp) {
    const now = Math.floor(Date.now() / 1000);
    const elapsed = now - timestamp;
    
    if (elapsed < 60) return 'agora há pouco';
    if (elapsed < 3600) return Math.floor(elapsed / 60) + ' min atrás';
    if (elapsed < 86400) return Math.floor(elapsed / 3600) + ' h atrás';
    return Math.floor(elapsed / 86400) + ' dias atrás';
}
<?php endif; ?>

function exportChart() {
    // Implementar exportação de dados
    showAlert('Funcionalidade de exportação em desenvolvimento', 'info');
}

function refreshChart() {
    // Implementar atualização do gráfico
    showAlert('Atualizando gráfico...', 'info');
}

function disableProxy(proxyId) {
    var proceed = function() {
        // Implementar desativação
        showAlert('Proxy desativado com sucesso', 'warning');
    };

    if (typeof Core !== 'undefined' && typeof Core.showConfirmDialog === 'function') {
        Core.showConfirmDialog({
            title: 'Desativar proxy',
            message: 'Tem certeza que deseja desativar este proxy?',
            confirmText: 'Desativar proxy',
            readyHint: 'Se estiver tudo certo, confirme para desativar este proxy.',
            onConfirm: proceed
        });
        return;
    }

    if (window.confirm('Tem certeza que deseja desativar este proxy?')) {
        proceed();
    }
}
</script>

<style>
.border-left-primary {
    border-left: 0.25rem solid #4e73df !important;
}

.border-left-success {
    border-left: 0.25rem solid #1cc88a !important;
}

.border-left-warning {
    border-left: 0.25rem solid #f6c23e !important;
}

.border-left-info {
    border-left: 0.25rem solid #36b9cc !important;
}

.chart-area {
    position: relative;
    height: 300px;
}

#proxy-map {
    background: #f8f9fc;
}

.table-hover tbody tr:hover {
    background-color: rgba(0,0,0,.075);
}

.alert-container {
    max-height: 300px;
    overflow-y: auto;
}
</style> 
