<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conectar WhatsApp</title>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #fff; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 480px; width: 100%; overflow: hidden; }
        .header { background: linear-gradient(135deg, #25D366 0%, #128C7E 100%); padding: 30px; text-align: center; color: #fff; }
        .header svg { width: 48px; height: 48px; margin-bottom: 10px; }
        .header h1 { font-size: 22px; font-weight: 600; margin-bottom: 5px; }
        .header p { font-size: 14px; opacity: 0.9; }
        .body { padding: 30px; }
        .section { text-align: center; }
        .section h2 { font-size: 18px; color: #333; margin-bottom: 15px; }
        .qr-container { background: #f8f9fa; border-radius: 16px; padding: 20px; margin-bottom: 20px; display: inline-block; }
        .qr-container img { display: block; margin: 0 auto; }
        .steps { text-align: left; margin: 20px 0; }
        .steps li { padding: 8px 0; color: #555; font-size: 14px; list-style: none; display: flex; align-items: center; }
        .steps li::before { content: attr(data-step); background: #25D366; color: #fff; border-radius: 50%; width: 24px; height: 24px; display: inline-flex; align-items: center; justify-content: center; font-size: 12px; font-weight: 600; margin-right: 12px; flex-shrink: 0; }
        .divider { display: flex; align-items: center; margin: 25px 0; color: #aaa; font-size: 13px; }
        .divider::before, .divider::after { content: ''; flex: 1; height: 1px; background: #e0e0e0; }
        .divider span { padding: 0 15px; }
        .btn { display: inline-block; padding: 12px 24px; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; border: none; transition: all 0.2s; text-decoration: none; }
        .btn-outline { background: #fff; border: 2px solid #25D366; color: #25D366; }
        .btn-outline:hover { background: #25D366; color: #fff; }
        .btn-whatsapp { background: #25D366; color: #fff; }
        .btn-whatsapp:hover { background: #1da851; }
        .btn-block { width: 100%; text-align: center; }
        .pin-section { display: none; }
        .pin-section.active { display: block; }
        .qr-section.active { display: block; }
        .input-group { margin: 15px 0; }
        .input-group input { width: 100%; padding: 14px 16px; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 16px; text-align: center; transition: border-color 0.2s; }
        .input-group input:focus { outline: none; border-color: #25D366; }
        .input-group small { display: block; margin-top: 5px; color: #999; font-size: 12px; }
        .code-display { background: #f0f7ff; border: 2px dashed #4A90D9; border-radius: 12px; padding: 20px; margin: 15px 0; display: none; }
        .code-display .code { font-size: 32px; font-weight: 700; letter-spacing: 6px; color: #333; font-family: 'Courier New', monospace; }
        .code-display .instructions { font-size: 13px; color: #666; margin-top: 10px; }
        .spinner { display: none; margin: 20px auto; width: 40px; height: 40px; border: 4px solid #e0e0e0; border-top-color: #25D366; border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        .footer { padding: 15px 30px; background: #f8f9fa; text-align: center; font-size: 12px; color: #999; }
        .timer { display: inline-flex; align-items: center; gap: 5px; }
        .error-msg { background: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; padding: 10px 15px; margin: 10px 0; font-size: 13px; color: #856404; display: none; }
        .success-overlay { position: fixed; inset: 0; background: rgba(0,0,0,0.5); display: none; align-items: center; justify-content: center; z-index: 100; }
        .success-overlay.active { display: flex; }
        .success-card { background: #fff; border-radius: 20px; padding: 40px; text-align: center; max-width: 400px; width: 90%; }
        .success-card .check { font-size: 64px; color: #25D366; margin-bottom: 15px; }
        .success-card h2 { font-size: 22px; color: #333; margin-bottom: 10px; }
        .success-card p { color: #666; font-size: 14px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="header">
            <svg viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 01-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 01-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 012.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0012.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 005.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 00-3.48-8.413z"/></svg>
            <h1>Conectar WhatsApp</h1>
            <p><?php echo htmlspecialchars($host ?? ''); ?></p>
        </div>
        <div class="body">
            <div id="qr-section" class="section qr-section active">
                <h2>Escaneie o QR Code</h2>
                <div class="qr-container">
                    <div id="qr-code"></div>
                </div>
                <ol class="steps">
                    <li data-step="1">Abra o <strong>WhatsApp</strong> no celular</li>
                    <li data-step="2">Toque em <strong>⋮</strong> → <strong>Dispositivos conectados</strong></li>
                    <li data-step="3">Toque em <strong>"Vincular dispositivo"</strong></li>
                    <li data-step="4">Aponte a câmera para o QR Code</li>
                </ol>
                <div class="divider"><span>ou</span></div>
                <button class="btn btn-outline btn-block" onclick="showPin()">Conectar via código PIN</button>
            </div>
            <div id="pin-section" class="section pin-section">
                <h2>Conectar via código</h2>
                <p style="color:#666;font-size:14px;margin-bottom:15px">Digite seu número com DDD</p>
                <div class="input-group">
                    <input type="tel" id="phone-input" placeholder="5511999999999" autocomplete="tel">
                    <small>Inclua o código do país (55) + DDD + número</small>
                </div>
                <button class="btn btn-whatsapp btn-block" id="btn-generate" onclick="generatePin()">Gerar código</button>
                <div id="pin-loading" class="spinner"></div>
                <div id="pin-error" class="error-msg"></div>
                <div id="pin-code" class="code-display">
                    <div style="font-size:12px;color:#999;margin-bottom:5px">Código gerado:</div>
                    <div class="code" id="pin-value">XXXX-XXXX</div>
                    <div class="instructions">
                        <strong>Como usar:</strong><br>
                        1. Abra o WhatsApp no celular<br>
                        2. Toque em <strong>⋮</strong> → <strong>Dispositivos conectados</strong><br>
                        3. Toque em <strong>"Vincular com código"</strong><br>
                        4. Digite o código acima
                    </div>
                </div>
                <div class="divider"><span>ou</span></div>
                <button class="btn btn-outline btn-block" onclick="showQR()">Voltar ao QR Code</button>
            </div>
            <div id="poll-spinner" class="spinner"></div>
        </div>
        <div class="footer">
            <div class="timer">⏱ Link expira em <strong id="timer-display"><?php echo $expires_minutes ?? 0; ?>:<?php echo str_pad($expires_seconds ?? 0, 2, '0', STR_PAD_LEFT); ?></strong></div>
        </div>
    </div>
    <div id="success-overlay" class="success-overlay">
        <div class="success-card">
            <div class="check">✅</div>
            <h2>Conectado com sucesso!</h2>
            <p id="success-name"></p>
            <p id="success-phone" style="margin-top:5px;font-size:13px;color:#999"></p>
            <p style="margin-top:20px;font-size:13px;color:#999">Esta página pode ser fechada.</p>
        </div>
    </div>
    <script>
    var TOKEN = '<?php echo $token ?? ''; ?>';
    var pollTimer = null, countdownTimer = null;
    var remaining = <?php echo $remaining ?? 1800; ?>;
    var isMobile = /iPhone|iPad|Android/i.test(navigator.userAgent);
    var qrInstance = null;

    function showPin() {
        document.getElementById('qr-section').style.display = 'none';
        document.getElementById('pin-section').style.display = 'block';
        document.getElementById('pin-section').classList.add('active');
    }
    function showQR() {
        document.getElementById('pin-section').style.display = 'none';
        document.getElementById('pin-section').classList.remove('active');
        document.getElementById('qr-section').style.display = 'block';
    }
    if (isMobile) showPin();

    function loadQR() {
        fetch('/connect/' + TOKEN + '/qr')
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.qrcode) {
                var container = document.getElementById('qr-code');
                container.innerHTML = '';
                try {
                    new QRCode(container, {
                        text: data.qrcode,
                        width: 250,
                        height: 250,
                        colorDark: '#000000',
                        colorLight: '#ffffff',
                        correctLevel: QRCode.CorrectLevel.M
                    });
                } catch(e) {}
            }
        })
        .catch(function() {});
    }

    function generatePin() {
        var phone = document.getElementById('phone-input').value.trim();
        if (!phone) { alert('Digite o número'); return; }
        var btn = document.getElementById('btn-generate');
        btn.disabled = true;
        btn.textContent = 'Gerando...';
        document.getElementById('pin-loading').style.display = 'block';
        document.getElementById('pin-error').style.display = 'none';
        document.getElementById('pin-code').style.display = 'none';
        fetch('/connect/' + TOKEN + '/paircode', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'phone=' + encodeURIComponent(phone)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            document.getElementById('pin-loading').style.display = 'none';
            btn.disabled = false;
            btn.textContent = 'Gerar código';
            if (data.status === 'success') {
                document.getElementById('pin-value').textContent = data.code;
                document.getElementById('pin-code').style.display = 'block';
            } else {
                document.getElementById('pin-error').textContent = data.message || 'Erro ao gerar código';
                document.getElementById('pin-error').style.display = 'block';
            }
        })
        .catch(function() {
            document.getElementById('pin-loading').style.display = 'none';
            btn.disabled = false;
            btn.textContent = 'Gerar código';
            document.getElementById('pin-error').textContent = 'Erro de conexão';
            document.getElementById('pin-error').style.display = 'block';
        });
    }

    function startPolling() {
        pollTimer = setInterval(function() {
            fetch('/connect/' + TOKEN + '/status')
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (data.state === 'connected') {
                    clearInterval(pollTimer);
                    clearInterval(countdownTimer);
                    document.getElementById('success-name').textContent = data.name || '';
                    document.getElementById('success-phone').textContent = data.phone || '';
                    document.getElementById('success-overlay').classList.add('active');
                } else if (data.state === 'expired' || data.state === 'not_found') {
                    clearInterval(pollTimer);
                    clearInterval(countdownTimer);
                    window.location.reload();
                } else if (data.refresh) {
                    // QR expired, auto-refreshed by backend, reload QR on page
                    loadQR();
                }
            })
            .catch(function() {});
        }, 3000);
    }

    function startCountdown() {
        countdownTimer = setInterval(function() {
            remaining--;
            if (remaining <= 0) { clearInterval(countdownTimer); clearInterval(pollTimer); window.location.reload(); return; }
            var m = Math.floor(remaining / 60), s = remaining % 60;
            document.getElementById('timer-display').textContent = m + ':' + (s < 10 ? '0' : '') + s;
        }, 1000);
    }

    setInterval(function() {
        if (!isMobile) loadQR();
    }, 15000);

    loadQR();
    startPolling();
    startCountdown();
    </script>
</body>
</html>
