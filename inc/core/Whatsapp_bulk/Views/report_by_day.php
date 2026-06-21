<html>

<head>
	<meta charset="UTF-8">
</head>

<body>

	<table style="width: 1366px; border: 1px solid #000000; font-family: Tahoma; margin: auto;">
		<tr>
			<th colspan="7" style="height: 100px; color: #000; padding: 5px; border: 1px solid #000000; text-transform: uppercase; font-size: 40px; background: #bada99;"><?php _e("Relatório de campanhas") ?></th>
		</tr>

		<?php

		$daterange = post("daterange");
		if ($daterange != "") {
			$daterange = explode(",", $daterange);
		} else {
			$daterange = [];
		}

		if (count($daterange) != 2) {
			return false;
		}
		?>

		<?php if (!empty($daterange) && count($daterange) == 2) : ?>
			<tr>
				<th style="background: #bada99; height: 30px; text-transform: uppercase; color: #000; padding: 5px; border: 1px solid #000000; text-align: left;"><?php _e("Data inicial") ?></th>
				<th colspan="2" style="color: #000; padding: 5px; border: 1px solid #000000; text-align: left;"><?php _ec(date_show($daterange[0])) ?></th>
				<th style="background: #bada99; height: 30px; text-transform: uppercase; color: #000; padding: 5px; border: 1px solid #000000; text-align: left;"><?php _e("Data final") ?></th>
				<th colspan="3" style="color: #000; padding: 5px; border: 1px solid #000000; text-align: left;"><?php _ec(date_show($daterange[1])) ?></th>
			</tr>
		<?php endif ?>
		<tr>
			<th colspan="7" style="height: 40px;"></th>
		</tr>
		<tr>
			<th colspan="1" style="height: 25px; color: #000; padding: 5px; border: 1px solid #000000;"><?php _e("Nome da campanha") ?></th>
			<th colspan="1" style="height: 25px; color: #000; padding: 5px; border: 1px solid #000000;"><?php _e("Tipo de campanha") ?></th>
			<th colspan="1" style="height: 25px; color: #000; padding: 5px; border: 1px solid #000000;"><?php _e("Nome do contato") ?></th>
			<th colspan="1" style="height: 25px; color: #000; padding: 5px; border: 1px solid #000000;"><?php _e("Delay mínimo") ?></th>
			<th colspan="1" style="height: 25px; color: #000; padding: 5px; border: 1px solid #000000;"><?php _e("Delay máximo") ?></th>
			<th colspan="1" style="height: 25px; color: #000; padding: 5px; border: 1px solid #000000;"><?php _e("Número de telefone") ?></th>
			<th colspan="1" style="height: 25px; color: #000; padding: 5px; border: 1px solid #000000;"><?php _e("Status") ?></th>
		</tr>
		<?php
		if (!empty($result)) {

			foreach ($result as $key => $row) {
				$data = whatsapp_bulk_get_report_items($row);

				if (!empty($data)) {
		?>
					<?php foreach ($data as $key => $value) : ?>
						<?php if (is_object($value)) : ?>
                            <?php $is_call_campaign = (int)($row->type ?? 1) === 7; ?>
							<?php
								$dispatch_state = (string)($value->dispatch_state ?? '');
								$status_ok = $dispatch_state !== '' ? $dispatch_state === 'sent' : !empty($value->status);
								$status_text = $value->message ?? ($status_ok ? ($is_call_campaign ? 'Ligação iniciada' : 'Sucesso') : ($is_call_campaign ? 'Ligação falhou' : 'Falha'));
							?>
							<tr>
								<td colspan="1" style="height: 25px; padding: 5px; border: 1px solid #000000; text-align: left;"><?php _e($row->name) ?></td>
								<td colspan="1" style="height: 25px; padding: 5px; border: 1px solid #000000; text-align: left;"><?php _e($is_call_campaign ? 'Campanha de ligação' : 'Campanha de mensagem') ?></td>
								<td colspan="1" style="height: 25px; padding: 5px; border: 1px solid #000000; text-align: left;"><?php _e($row->contact_name) ?></td>
								<td colspan="1" style="height: 25px; padding: 5px; border: 1px solid #000000; text-align: left;"><?php _e($row->min_delay) ?></td>
								<td colspan="1" style="height: 25px; padding: 5px; border: 1px solid #000000; text-align: left;"><?php _e($row->max_delay) ?></td>
								<td colspan="1" style="height: 25px; padding: 5px; border: 1px solid #000000; mso-number-format:'\@'; text-align: left;"><?php echo htmlspecialchars((string)$value->phone_number, ENT_QUOTES, 'UTF-8'); ?></td>
								<td colspan="1" style="height: 25px; padding: 5px; border: 1px solid #000000; text-align: center; color: <?php _e($status_ok ? "#009f19" : ($dispatch_state !== '' && $dispatch_state !== 'failed' ? "#0d6efd" : "#f00")) ?>;"><?php _e($status_text) ?></td>
							</tr>
						<?php endif ?>
					<?php endforeach ?>
				<?php } ?>
			<?php } ?>
		<?php } else { ?>
			<tr>
				<td colspan="7" style="color: #000; padding: 5px; border: 1px solid #000000; text-align: center;"><?php _e("Nenhum dado encontrado") ?></td>
			</tr>
		<?php } ?>
	</table>

</body>

</html>
