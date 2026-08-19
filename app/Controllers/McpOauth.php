<?php

namespace App\Controllers;

use CodeIgniter\Controller;

class McpOauth extends Controller
{
    public function callback()
    {
        $queryString = $this->request->getUri()->getQuery();
        $targetUrl = 'http://127.0.0.1:3000/mcp/oauth/callback';
        
        if (!empty($queryString)) {
            $targetUrl .= '?' . $queryString;
        }

        // Fazer a requisição para o servidor local do Kilo
        $ch = curl_init($targetUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // Se a requisição local falhar, avisar o usuário
        if ($httpCode !== 200 && $httpCode !== 302 && $httpCode !== 301) {
            return "<html><body style='font-family: sans-serif; text-align: center; margin-top: 50px;'>
                <h2>Erro na autenticação local do Kilo</h2>
                <p>O servidor local do Kilo (porta 3000) não respondeu corretamente. Verifique se ele está rodando.</p>
                <p>Código HTTP: {$httpCode}</p>
            </body></html>";
        }

        return "<html><body style='font-family: sans-serif; text-align: center; margin-top: 50px;'>
            <h2 style='color: green;'>Autenticação concluída!</h2>
            <p>O redirecionamento do Facebook foi recebido e processado com sucesso.</p>
            <p>Você já pode fechar esta aba e voltar para o Kilo.</p>
            <script>setTimeout(function() { window.close(); }, 3000);</script>
        </body></html>";
    }
}
