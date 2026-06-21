<?php if (!empty($result)) { ?>

	<?php foreach ($result as $key => $value) : ?>

		<tr>

			<td class="ps-4">
				<div class="form-check">
					<input class="form-check-input checkbox-item" type="checkbox" name="ids[]" value="<?php _e($value->ids) ?>">
				</div>
			</td>
			<td>
				<?php _e($key + 1) ?>
			</td>
			<td>
				<div class="d-flex align-items-center justify-content-between">
					<div class="d-flex align-items-center">
						<i class="fas fa-phone-alt text-muted me-2"></i>
						<span class="fw-medium"><?php _e($value->phone) ?></span>
					</div>
					<a href="https://wa.me/<?php _e(preg_replace('/[^0-9]/', '', $value->phone)) ?>" 
					   target="_blank"
					   class="btn btn-sm btn-success-soft rounded-circle" 
					   data-bs-toggle="tooltip" 
					   title="<?php _e('Iniciar conversa') ?>">
						<i class="fab fa-whatsapp"></i>
					</a>
				</div>
			</td>
			<td>
				<?php if ($value->is_valid == 1): ?>
					<span class="badge bg-success-soft">
						<i class="fas fa-check-circle me-1"></i>
						<?php _e("Válido") ?>
					</span>
				<?php elseif ($value->is_valid == 2): ?>
					<span class="badge bg-danger-soft">
						<i class="fas fa-times-circle me-1"></i>
						<?php _e("Inválido") ?>
					</span>
				<?php else: ?>
					<span class="badge bg-info-soft">
						<i class="fas fa-sync me-1"></i>
						<?php _e("Validando") ?>
					</span>
				<?php endif; ?>
			</td>
			<td>
				<?php if (!empty($value->params)): ?>
					<div class="d-flex flex-wrap gap-2">
						<?php 
						$params = json_decode($value->params);
						if ($params && is_object($params)):
							foreach ($params as $param_key => $param_value):
						?>
							<span class="badge bg-light text-dark">
								<?php _e($param_key) ?>: <?php _e($param_value) ?>
							</span>
						<?php 
							endforeach;
						endif;
						?>
					</div>
				<?php else: ?>
					<span class="text-muted">-</span>
				<?php endif; ?>
			</td>
		</tr>

	<?php endforeach ?>

<?php } else { ?>
	<tr>
		<td colspan="5">
			<div class="d-flex align-items-center justify-content-center py-5">
				<div class="text-center">
					<img src="<?php _e(get_theme_url()) ?>Assets/img/empty.png" 
						 class="mw-100 mb-3" style="max-height: 150px;" 
						 alt="No numbers">
					<p class="text-muted mb-0"><?php _e("Nenhum número encontrado") ?></p>
				</div>
			</div>
		</td>
	</tr>
<?php } ?>

<style>
.bg-success-soft { background-color: rgba(25, 135, 84, 0.1) !important; color: #198754 !important; }
.bg-danger-soft { background-color: rgba(220, 53, 69, 0.1) !important; color: #dc3545 !important; }
.bg-info-soft { background-color: rgba(13, 202, 240, 0.1) !important; color: #0dcaf0 !important; }
.badge { font-weight: 500; padding: 0.5em 0.75em; }
.btn-success-soft {
	background-color: rgba(37, 211, 102, 0.1) !important;
	color: #25d366 !important;
	border: none;
	width: 32px;
	height: 32px;
	padding: 0;
	display: inline-flex;
	align-items: center;
	justify-content: center;
	transition: all 0.2s ease;
}
.btn-success-soft:hover {
	background-color: #25d366 !important;
	color: #fff !important;
}
.fab.fa-whatsapp {
	font-size: 1.1rem;
}
</style>

<script type="text/javascript">
$(function(){
	// Initialize tooltips
	$('[data-bs-toggle="tooltip"]').tooltip();
});
</script>