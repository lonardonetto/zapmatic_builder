<button id="btn-teste-proxy" data-id="<?= $perfil['id'] ?>" class="btn btn-primary">Testar Proxy</button>
<div id="resultado-teste-proxy" style="margin-top:10px;"></div>
<script>
document.getElementById('btn-teste-proxy').onclick = function() {
  var id = this.getAttribute('data-id');
  var resultado = document.getElementById('resultado-teste-proxy');
  resultado.innerHTML = 'Testando...';
  fetch('/proxy_system/testar_localizacao/' + id)
    .then(resp => resp.json())
    .then(data => {
      if (data.status === 'success') {
        resultado.innerHTML = `
          <b>IP:</b> ${data.proxy_ip}<br>
          <b>País:</b> ${data.proxy_country}<br>
          <b>Cidade:</b> ${data.proxy_city}<br>
          <b>Org:</b> ${data.proxy_org}<br>
          <b>Tipo de Proxy:</b> ${data.proxy_type}<br>
          <b>Mensagem:</b> ${data.mensagem}
        `;
      } else {
        resultado.innerHTML = '<span style="color:red;">' + data.message + '</span>';
      }
    })
    .catch(() => resultado.innerHTML = '<span style="color:red;">Erro ao testar proxy.</span>');
};
</script> 