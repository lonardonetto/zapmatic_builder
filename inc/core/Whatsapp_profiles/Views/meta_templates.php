<?php
/**
 * Gestão de Templates Oficiais (Meta) por conta Cloud API.
 *
 * Regras (Meta):
 * - O template precisa ser criado/submetido no WABA e aprovado para envio fora da janela.
 * - Templates são por WABA/idioma. No SaaS, cada cliente usa suas próprias credenciais.
 */
?>

<div class="container py-4">
  <div class="d-flex justify-content-between align-items-center mb-4">
    <div>
      <h3 class="mb-1"><?php _e("Templates Oficiais (Meta)")?></h3>
      <div class="text-muted">
        <?php _e("Conta:")?> <strong><?php _ec($account->name ?? $account->pid ?? '')?></strong>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a class="btn btn-light" href="<?php _ec( base_url('whatsapp_profiles') )?>">
        <i class="fas fa-arrow-left me-2"></i><?php _e("Voltar")?>
      </a>
      <button type="button" class="btn btn-info" onclick="sincronizarStatusMeta('<?php _ec($account->ids ?? '')?>')">
        <i class="fas fa-sync-alt me-2"></i><?php _e("Sincronizar status")?>
      </button>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header">
      <div class="card-title"><?php _e("Criar rascunho e submeter para aprovação")?></div>
    </div>
    <div class="card-body">
      <form class="actionForm" action="<?php _ec( base_url('whatsapp_profiles/meta_draft_save/' . ($account->ids ?? '')) )?>" method="POST" enctype="multipart/form-data">
        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold"><?php _e("Nome do template (Meta)")?></label>
            <input type="text" class="form-control form-control-solid" name="template_name" placeholder="ex: desbanimento" required>
            <small class="text-muted"><?php _e("Use apenas letras minúsculas, números e _ (regra comum da Meta).")?></small>
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label fw-bold"><?php _e("Categoria")?></label>
            <select class="form-select form-select-solid" name="category" required>
              <option value="MARKETING"><?php _e("MARKETING")?></option>
              <option value="UTILITY"><?php _e("UTILITY")?></option>
            </select>
          </div>
          <div class="col-md-3 mb-3">
            <label class="form-label fw-bold"><?php _e("Idiomas")?></label>
            <input type="text" class="form-control form-control-solid" name="languages" placeholder="pt_BR,en_US" required>
            <small class="text-muted"><?php _e("Separar por vírgula. Ex: pt_BR,en_US")?></small>
          </div>
        </div>

        <div class="row">
          <div class="col-md-3 mb-3">
            <label class="form-label fw-bold"><?php _e("Header")?></label>
            <select class="form-select form-select-solid" name="header_format">
              <option value="NONE"><?php _e("Sem header")?></option>
              <option value="TEXT"><?php _e("Texto")?></option>
              <option value="IMAGE"><?php _e("Imagem")?></option>
              <option value="VIDEO"><?php _e("Vídeo")?></option>
              <option value="DOCUMENT"><?php _e("Documento")?></option>
            </select>
          </div>
          <div class="col-md-9 mb-3">
            <label class="form-label fw-bold"><?php _e("Header texto (se aplicável)")?></label>
            <input type="text" class="form-control form-control-solid" name="header_text" placeholder="Título curto (opcional para header TEXT)">
          </div>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold"><?php _e("Header mídia (se aplicável)")?></label>
          <input type="file" class="form-control form-control-solid" name="header_media">
          <small class="text-muted"><?php _e("Obrigatório se o header for IMAGE/VIDEO/DOCUMENT. O sistema fará upload para a Meta e usará media_id.")?></small>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold"><?php _e("Body")?></label>
          <textarea class="form-control form-control-solid" name="body_text" rows="4" placeholder="Texto do template. Use {{1}}, {{2}} para variáveis." required></textarea>
        </div>

        <div class="mb-3">
          <label class="form-label fw-bold"><?php _e("Exemplo de variáveis (obrigatório se houver {{n}})")?></label>
          <input type="text" class="form-control form-control-solid" name="body_example" placeholder="ex: João|12345|Rio de Janeiro">
          <small class="text-muted"><?php _e("Informe os valores na ordem das variáveis, separados por |. Ex: se tiver {{1}} e {{2}}, use 'valor1|valor2'.")?></small>
        </div>

        <div class="row">
          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold"><?php _e("Footer (opcional)")?></label>
            <input type="text" class="form-control form-control-solid" name="footer_text" placeholder="Rodapé">
          </div>
          <div class="col-md-6 mb-3">
            <label class="form-label fw-bold"><?php _e("Botões (opcional, até 3)")?></label>
            <input type="text" class="form-control form-control-solid mb-2" name="btn1_text" placeholder="Botão 1 (quick reply) texto">
            <input type="text" class="form-control form-control-solid mb-2" name="btn2_text" placeholder="Botão 2 (quick reply) texto">
            <input type="text" class="form-control form-control-solid" name="btn3_url" placeholder="Botão 3 URL (formato: TEXTO|https://exemplo.com)">
            <small class="text-muted"><?php _e("Para URL: 'Texto|https://...'. Quick reply usa apenas o texto.")?></small>
          </div>
        </div>

        <div class="d-flex justify-content-end">
          <button type="submit" class="btn btn-primary">
            <i class="fas fa-save me-2"></i><?php _e("Salvar rascunho")?>
          </button>
        </div>
      </form>
    </div>
  </div>

  <div class="card mb-4">
    <div class="card-header">
      <div class="card-title"><?php _e("Rascunhos")?></div>
    </div>
    <div class="card-body">
      <?php if (empty($drafts)): ?>
        <div class="text-muted"><?php _e("Nenhum rascunho ainda.")?></div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th><?php _e("Nome")?></th>
                <th><?php _e("Categoria")?></th>
                <th><?php _e("Idiomas")?></th>
                <th class="text-end"><?php _e("Ações")?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($drafts as $d): ?>
                <?php $dd = json_decode($d->data, true) ?: []; ?>
                <tr>
                  <td><strong><?php _ec($d->name)?></strong></td>
                  <td><?php _ec($dd['category'] ?? '-')?></td>
                  <td><?php _ec(is_array($dd['languages'] ?? null) ? implode(',', $dd['languages']) : '-')?></td>
                  <td class="text-end">
                    <button type="button" class="btn btn-success btn-sm" onclick="submeterDraft('<?php _ec( base_url('whatsapp_profiles/meta_draft_submit/' . ($account->ids ?? '') . '/' . $d->id) )?>')">
                      <i class="fas fa-paper-plane me-1"></i><?php _e("Submeter")?>
                    </button>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="card-header">
      <div class="card-title"><?php _e("Status na Meta (por idioma)")?></div>
    </div>
    <div class="card-body">
      <?php if (empty($statuses)): ?>
        <div class="text-muted"><?php _e("Sem status ainda. Clique em Sincronizar status.")?></div>
      <?php else: ?>
        <div class="table-responsive">
          <table class="table table-striped align-middle">
            <thead>
              <tr>
                <th><?php _e("Nome")?></th>
                <th><?php _e("Idioma")?></th>
                <th><?php _e("Status")?></th>
                <th><?php _e("Categoria (Meta)")?></th>
                <th><?php _e("Meta ID")?></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($statuses as $s): ?>
                <?php $sd = json_decode($s->data, true) ?: []; ?>
                <tr>
                  <td><strong><?php _ec($s->name)?></strong></td>
                  <td><?php _ec($sd['language'] ?? '-')?></td>
                  <td><?php _ec($sd['status'] ?? '-')?></td>
                  <td>
                    <?php
                      $cat = $sd['category'] ?? '-';
                      $prev = $sd['previous_category'] ?? null;
                      _ec($cat);
                      if ($prev && $prev !== $cat) {
                        echo ' <small class="text-muted">(prev: ' . htmlspecialchars((string)$prev, ENT_QUOTES, 'UTF-8') . ')</small>';
                      }
                    ?>
                  </td>
                  <td><small><?php _ec($sd['meta_id'] ?? '-')?></small></td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
function notifyMetaTemplate(message, type) {
  type = type || "info";

  if (typeof Core !== "undefined" && typeof Core.notify === "function") {
    Core.notify(message, type);
    return;
  }

  if (typeof showNotification === "function") {
    showNotification(message, type);
    return;
  }

  if (typeof Swal !== "undefined") {
    Swal.fire({
      icon: type === "error" ? "error" : "success",
      title: message,
      timer: 2400,
      showConfirmButton: false
    });
    return;
  }

  console[type === "error" ? "error" : "log"](message);
}

async function sincronizarStatusMeta(accountIds) {
  try {
    if (!accountIds) {
      notifyMetaTemplate("Conta inválida.", "error");
      return;
    }

    if (typeof Swal !== "undefined") {
      Swal.fire({ title: "Sincronizando...", didOpen: () => Swal.showLoading() });
    }

    const url = "<?php _ec( base_url('whatsapp_profiles/sync_templates') )?>/" + accountIds;
    const resp = await fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } });
    const data = await resp.json();

    if (typeof Swal !== "undefined") Swal.close();

    const msg = data && data.message ? data.message : "Sincronização concluída.";
    if (typeof showNotification === "function") {
      showNotification(msg, data.status || "success");
    } else {
      notifyMetaTemplate(msg, data.status || "success");
    }

    // Mantém você na tela e atualiza lista/status
    window.location.reload();
  } catch (e) {
    if (typeof Swal !== "undefined") Swal.close();
    notifyMetaTemplate("Erro ao sincronizar: " + (e && e.message ? e.message : "desconhecido"), "error");
  }
}

async function submeterDraft(url) {
  try {
    if (typeof Swal !== "undefined") {
      Swal.fire({ title: "Submetendo...", didOpen: () => Swal.showLoading() });
    }

    const resp = await fetch(url, { headers: { "X-Requested-With": "XMLHttpRequest" } });
    const data = await resp.json();

    if (typeof Swal !== "undefined") Swal.close();

    if (data.status === "success" || data.status === "warning") {
      notifyMetaTemplate(data.message || "Template submetido.", data.status);
      if (data.redirect) window.location.href = data.redirect;
      return;
    }
    notifyMetaTemplate(data.message || "Erro ao submeter.", "error");
  } catch (e) {
    if (typeof Swal !== "undefined") Swal.close();
    notifyMetaTemplate("Erro ao submeter: " + (e && e.message ? e.message : "desconhecido"), "error");
  }
}
</script>
