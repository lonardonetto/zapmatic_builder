<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conectado com sucesso</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .card { background: #fff; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); max-width: 420px; width: 100%; padding: 50px 30px; text-align: center; }
        .check { font-size: 72px; margin-bottom: 20px; }
        h1 { font-size: 24px; color: #333; margin-bottom: 10px; }
        p { color: #666; font-size: 15px; margin: 5px 0; }
        .phone { font-size: 18px; color: #25D366; font-weight: 600; margin-top: 10px; }
        .avatar { width: 80px; height: 80px; border-radius: 50%; margin: 15px auto; object-fit: cover; border: 3px solid #25D366; }
        .footer { margin-top: 30px; font-size: 13px; color: #999; }
    </style>
</head>
<body>
    <div class="card">
        <div class="check">✅</div>
        <h1>Conectado com sucesso!</h1>
        <?php if (!empty($avatar)): ?>
            <img src="<?php echo htmlspecialchars($avatar); ?>" alt="Avatar" class="avatar">
        <?php endif; ?>
        <?php if (!empty($name)): ?>
            <p><strong><?php echo htmlspecialchars($name); ?></strong></p>
        <?php endif; ?>
        <?php if (!empty($phone)): ?>
            <p class="phone"><?php echo htmlspecialchars($phone); ?></p>
        <?php endif; ?>
        <div class="footer">
            <p>WhatsApp conectado ao sistema.</p>
            <p>Esta página pode ser fechada.</p>
        </div>
    </div>
</body>
</html>
