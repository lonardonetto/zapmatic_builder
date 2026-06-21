<div class="container my-5">
	<form class="actionForm" action="<?php _ec( get_module_url("save/".uri("segment", 4)) )?>" method="POST" data-redirect="<?php _ec( get_module_url() )?>">
		<div class="card m-b-25 mw-800 m-auto">
		    <div class="card-header">
		        <div class="card-title flex-column">
		            <h3 class="fw-bolder"><i class="fad fa-edit"></i> <?php _e("Update")?></h3>
		        </div>
		    </div>
		    <div class="card-body">
        		<div class="row">
        			<div class="col-md-6 mb-3">
	                    <label for="website_description" class="form-label"><?php _e("Status")?></label>
	                    <div>
	                        <div class="form-check form-check-inline">
	                            <input class="form-check-input" type="radio" name="status" id="status_1" value="1" <?php _ec( (empty($result) || get_data($result, "status") == 1)?"checked":"" )?>>
	                            <label class="form-check-label" for="status_1"><?php _e("Enable")?></label>
	                        </div>
	                        <div class="form-check form-check-inline">
	                            <input class="form-check-input" type="radio" name="status" id="status_0" value="0" <?php _ec( get_data($result, "status", "radio", 0) )?>>
	                            <label class="form-check-label" for="status_0"><?php _e("Disable")?></label>
	                        </div>
	                    </div>
	                </div>
			        <div class="col-md-6 mb-3">
			            <label for="website_description" class="form-label"><?php _e("Coupon by")?></label>
	                    <div>
	                        <div class="form-check form-check-inline">
	                            <input class="form-check-input" type="radio" name="by" id="coupon_by_percent" value="1" <?php _ec( (empty($result) || get_data($result, "by") == 1)?"checked":"" )?> >
	                            <label class="form-check-label" for="coupon_by_percent"><?php _e("Percent")?></label>
	                        </div>
	                        <div class="form-check form-check-inline">
	                            <input class="form-check-input" type="radio" name="by" id="coupon_by_price" value="2" <?php _ec( get_data($result, "by", "radio", 2) )?>>
	                            <label class="form-check-label" for="coupon_by_price"><?php _e("Price")?></label>
	                        </div>
	                    </div>
			        </div>
        		</div>
		        <div class="mb-3">
		            <label for="name" class="form-label"><?php _e("Name")?></label>
		            <input type="text" class="form-control form-control-solid" id="name" name="name" value="<?php _ec( get_data($result, "name") )?>">
		        </div>
		        <div class="mb-3">
		            <label for="code" class="form-label"><?php _e("Code")?></label>
		            <input type="text" class="form-control form-control-solid" id="code" name="code" value="<?php _ec( get_data($result, "code") )?>">
		        </div>
		        <div class="mb-3">
		            <label for="price" class="form-label"><?php _e("Price/Percent")?></label>
		            <input type="text" class="form-control form-control-solid" id="price" name="price" value="<?php _ec( get_data($result, "price") )?>">
		        </div>
		        <div class="mb-3">
		            <label for="expiration_date" class="form-label"><?php _e("Expiration date")?></label>
		            <input type="text" class="form-control form-control-solid datetime" id="expiration_date" name="expiration_date" value="<?php _ec( datetime_show( get_data($result, "expiration_date") ) )?>" autocomplete="off">
		        </div>
		        <div class="mb-3">
		            <label for="content" class="form-label"><?php _e("Packages")?></label>
		            <div class="am-choice-body mh-400 overflow-auto border rounded">
						<div class="search-plans">
							<?php if (!empty($plans)): ?>
								
								<?php foreach ($plans as $key => $value): ?>

									<?php 
										$plan_selected = [];
										if(!empty($result)){
											$plan_selected = json_decode($result->plans);
										}
									?>

									<div class="group-plans">
										<label class="am-choice-item d-flex flex-stack" for="am_<?php _ec($value->id)?>" >
											<div class="symbol symbol-35px px-3 py-2">
												<img src="<?php _ec( get_avatar($value->name) )?>" class="align-self-center" alt="">
											</div>
											<div class="d-flex align-items-center flex-row-fluid flex-wrap">
												<div class="flex-grow-1 me-2 text-over-all">
													<div class="text-gray-800 text-hover-primary fs-12 fw-bold text-over"><?php _e($value->name)?></div>
													<span class="text-muted fw-semibold d-block fs-10"><?php _e( ucfirst( $value->description ) )?></span>
												</div>
											</div>
											<div class="form-check me-2">
						                        <input class="form-check-input check-item" id="am_<?php _ec($value->id)?>" name="plans[]" type="checkbox" value="<?php _e($value->id)?>" <?php _ec( in_array($value->id, $plan_selected)?"checked":"" ) ?> >
						                        <label class="form-check-label"></label>
						                    </div>
										</label>
										<div class="separator separator-dashed mt-1"></div>
									</div>
								<?php endforeach ?>

							<?php endif ?>
						</div>
					</div>
		        </div>
		    </div>
		    <div class="card-footer d-flex justify-content-between">
		    	<a href="<?php _ec( get_module_url() )?>" class="btn btn-secondary"><?php _e("Back")?></a>
		    	<button type="submit" class="btn btn-primary"><?php _e("Save")?></button>
		    </div>
		</div>
	</form>
</div>
