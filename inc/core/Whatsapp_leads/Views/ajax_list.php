<?php if (!empty($result)) { ?>
    <?php foreach ($result as $item): ?>
        <tr>
            <td class="ps-4">
                <div class="form-check">
                    <input class="form-check-input checkbox-item" type="checkbox" value="<?php _ec($item->id) ?>">
                </div>
            </td>
            <td>
                <div class="fw-semibold"><?php _ec($item->name ?: __('Sem nome')) ?></div>
                <small class="text-muted">JID: <?php _ec($item->chatid) ?></small>
            </td>
            <td>
                <span class="badge bg-light text-dark">
                    <?php _ec($item->phone_number) ?>
                </span>
            </td>
            <td>
                <div class="fw-semibold"><?php _ec($item->account_name ?: __('Conta removida')) ?></div>
                <?php if(!empty($item->account_phone)): ?>
                    <small class="text-muted"><?php _ec($item->account_phone) ?></small>
                <?php endif; ?>
            </td>
            <td><?php _ec($item->created_at) ?></td>
            <td><?php _ec($item->last_message_at) ?></td>
            <td class="text-center">
                <span class="badge bg-info-soft text-info"><?php _ec((int)$item->unread_messages) ?></span>
            </td>
        </tr>
    <?php endforeach; ?>
<?php } else { ?>
    <tr>
        <td colspan="7" class="text-center py-5 text-muted">
            <?php _e('Nenhum lead encontrado com os filtros informados.') ?>
        </td>
    </tr>
<?php } ?>
