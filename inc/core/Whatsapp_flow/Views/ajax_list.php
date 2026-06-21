<?php if (!empty($result)) { ?>

    <?php foreach ($result as $key => $value): ?>
        <?php
        $status_local = (string) $value->status_local;
        $status_meta = (string) $value->status_meta;
        $categories = [];
        if (!empty($value->categories_json)) {
            $decoded_categories = json_decode($value->categories_json, true);
            if (is_array($decoded_categories)) {
                $categories = array_values(array_filter($decoded_categories, function ($category) {
                    return trim((string) $category) !== "";
                }));
            }
        }
        $status_class = "light-secondary text-gray-700";

        switch ($status_local) {
            case 'ready':
                $status_class = "light-success text-success";
                break;

            case 'archived':
                $status_class = "light-danger text-danger";
                break;
        }
        ?>
        <div class="item col-md-6 col-sm-12 mb-4">
            <div class="card b-r-10 h-100">
                <div class="card-body position-relative p-r-50">
                    <i class="fad fa-project-diagram fs-90 position-absolute opacity-25 r-30" style="color: <?php _ec($config['color'])?>;"></i>

                    <div class="mb-3">
                        <h3 class="text-dark"><?php _e($value->name)?></h3>
                        <div class="fs-12 text-gray-700 text-over"><?php _e($value->slug ? $value->slug : "-")?></div>
                    </div>

                    <div class="mb-3 d-flex flex-wrap gap-2">
                        <span class="badge badge-<?php _ec($status_class)?>"><?php _e(ucfirst($status_local))?></span>

                        <?php if ($status_meta != ""): ?>
                            <span class="badge badge-light-primary text-primary"><?php _e("Meta: " . $status_meta)?></span>
                        <?php else: ?>
                            <span class="badge badge-light-warning text-warning"><?php _e("Meta: not synced")?></span>
                        <?php endif ?>

                        <?php if (!empty($value->endpoint_status)): ?>
                            <span class="badge badge-light-info text-info"><?php _e("Endpoint: " . $value->endpoint_status)?></span>
                        <?php endif ?>
                    </div>

                    <div class="mb-4 fs-12 text-gray-700">
                        <div><strong><?php _e("Account")?>:</strong> <?php _e($value->account_name ? $value->account_name : "-")?></div>
                        <div><strong><?php _e("Channel")?>:</strong> <?php _e($value->channel ? $value->channel : "cloud_api")?></div>
                        <div><strong><?php _e("Flow ID")?>:</strong> <?php _e($value->meta_flow_id ? $value->meta_flow_id : "-")?></div>
                        <div><strong><?php _e("Categories")?>:</strong> <?php _e(!empty($categories) ? implode(", ", $categories) : "-")?></div>
                        <div><strong><?php _e("JSON version")?>:</strong> <?php _e($value->json_version ? $value->json_version : "-")?></div>
                        <div><strong><?php _e("Data API version")?>:</strong> <?php _e($value->data_api_version ? $value->data_api_version : "-")?></div>
                    </div>

                    <div class="d-flex">
                        <a href="<?php _e(get_module_url("index/update/" . $value->ids))?>" class="btn btn-sm btn-dark w-35 h-35 text-center d-flex align-items-center me-2 position-relative" title="<?php _e('Edit')?>"><i class="position-absolute l-11 fs-14 fal fa-edit"></i></a>
                        <a href="<?php _e(get_module_url("endpoint_sync/" . $value->ids))?>" class="btn btn-sm btn-dark w-35 h-35 text-center d-flex align-items-center me-2 position-relative actionItem" data-confirm="<?php _e('Prepare the endpoint and upload the public key to Meta now?')?>" data-call-success="Core.ajax_pages();" title="<?php _e('Prepare endpoint')?>"><i class="position-absolute l-11 fs-14 fal fa-key"></i></a>
                        <a href="<?php _e(get_module_url("meta_push_draft/" . $value->ids))?>" class="btn btn-sm btn-dark w-35 h-35 text-center d-flex align-items-center me-2 position-relative actionItem" data-confirm="<?php _e('Sync this Flow draft with Meta now?')?>" data-call-success="Core.ajax_pages();" title="<?php _e('Sync draft with Meta')?>"><i class="position-absolute l-11 fs-14 fal fa-cloud-upload"></i></a>

                        <?php if (strtoupper($status_meta) !== "PUBLISHED"): ?>
                            <a href="<?php _e(get_module_url("meta_publish/" . $value->ids))?>" class="btn btn-sm btn-dark w-35 h-35 text-center d-flex align-items-center me-2 position-relative actionItem" data-confirm="<?php _e('Publish this Flow on Meta? This cannot be undone.')?>" data-call-success="Core.ajax_pages();" title="<?php _e('Publish on Meta')?>"><i class="position-absolute l-11 fs-14 fal fa-paper-plane"></i></a>
                        <?php endif ?>

                        <?php if (!empty($value->meta_flow_id)): ?>
                            <a href="<?php _e(get_module_url("meta_sync/" . $value->ids))?>" class="btn btn-sm btn-dark w-35 h-35 text-center d-flex align-items-center me-2 position-relative actionItem" data-call-success="Core.ajax_pages();" title="<?php _e('Refresh Meta status')?>"><i class="position-absolute l-11 fs-14 fal fa-sync"></i></a>
                        <?php endif ?>

                        <a href="<?php _e(get_module_url("meta_pull_account/" . $value->ids))?>" class="btn btn-sm btn-dark w-35 h-35 text-center d-flex align-items-center me-2 position-relative actionItem" data-confirm="<?php _e('Pull all Flows from this Cloud account and sync them locally?')?>" data-call-success="Core.ajax_pages();" title="<?php _e('Pull all Flows from Meta')?>"><i class="position-absolute l-11 fs-14 fal fa-download"></i></a>

                        <a href="<?php _e(get_module_url("delete/" . $value->ids))?>" data-id="<?php _ec($value->ids)?>" class="btn btn-sm btn-dark w-35 h-35 text-center d-flex align-items-center me-2 position-relative actionItem" data-confirm="<?php _e('Are you sure to delete this items?')?>" data-call-success="Core.ajax_pages();" title="<?php _e('Delete')?>"><i class="position-absolute l-11 fs-14 fal fa-trash-alt"></i></a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach ?>

<?php } else { ?>
    <div class="mw-400 container d-flex align-items-center align-self-center h-100 py-5">
        <div>
            <div class="text-center px-4">
                <img class="mw-100 mh-300px" alt="" src="<?php _e(get_theme_url()) ?>Assets/img/empty2.png">
            </div>
        </div>
    </div>
<?php } ?>
