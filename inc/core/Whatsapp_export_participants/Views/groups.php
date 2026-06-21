<style>
    .img-thumbnail {
        object-fit: cover; /* Garante que a imagem preencha o contêiner sem distorção */
    }
    .initials {
        display: flex;
        align-items: center;
        width: 60px;
        height: 60px;
        justify-content: center;
        background-color: #6f42c1; /* Cor de fundo roxa */
        color: white;
        font-size: 6px;
        font-weight: bold;
        border-radius: 50%;
        text-transform: uppercase;
        object-fit: cover;
        line-height: 60px; /* Para centralizar verticalmente o texto */
    }
    .card-statistics {
        margin-bottom: 20px;
    }
    .statistics-row {
        display: flex;
        justify-content: space-between;
    }
</style>


<div class="row statistics-row">
    <div class="col-12 col-md-4 mb-3">
        <div class="card mb-3 h-100">
            <div class="row g-0 b-r-10" style="border: 1px solid #f3f3f3; height: 100%;">
                <div class="col-md-3 d-flex align-items-center justify-content-center">
                    <i class="fas fa-users fa-2x"></i>
                </div>
                <div class="col-md-9">
                    <div class="card-body text-center">
                        <h6 class="card-title">Groups <span class="badge badge-secondary"><?php _e($result->statistics->totalGroups); ?></span></h6>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped bg-info" role="progressbar" style="width: <?php echo ($result->statistics->totalGroups / $result->statistics->totalGroups) * 100; ?>%;" aria-valuenow="<?php echo ($result->statistics->totalGroups / $result->statistics->totalGroups) * 100; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo round(($result->statistics->totalGroups / $result->statistics->totalGroups) * 100, 2); ?>%
                            </div>
                        </div>
                    </div>
                </div>
                <p class="card-text text-center" style="background-color: blueviolet; border-radius: 0px 0px 10px 10px; padding-top: 5px;"><small style="color: #fff">Total Groups</small></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 mb-3">
        <div class="card mb-3 h-100">
            <div class="row g-0 b-r-10" style="border: 1px solid #f3f3f3; height: 100%;">
                <div class="col-md-3 d-flex align-items-center justify-content-center">
                    <i class="fas fa-network-wired fa-2x"></i>
                </div>
                <div class="col-md-9">
                    <div class="card-body text-center">
                        <h6 class="card-title">Communities <span class="badge badge-secondary"><?php _e($result->statistics->totalCommunities); ?></span></h6>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped bg-info" role="progressbar" style="width: <?php echo ($result->statistics->totalCommunities / $result->statistics->totalGroups) * 100; ?>%;" aria-valuenow="<?php echo ($result->statistics->totalCommunities / $result->statistics->totalGroups) * 100; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo round(($result->statistics->totalCommunities / $result->statistics->totalGroups) * 100, 2); ?>%
                            </div>
                        </div>
                    </div>
                </div>
                <p class="card-text text-center" style="background-color: blueviolet; border-radius: 0px 0px 10px 10px; padding-top: 5px;"><small style="color: #fff">Total Communities</small></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-4 mb-3">
        <div class="card mb-3 h-100">
            <div class="row g-0 b-r-10" style="border: 1px solid #f3f3f3; height: 100%;">
                <div class="col-md-3 d-flex align-items-center justify-content-center">
                    <i class="fas fa-bullhorn fa-2x"></i>
                </div>
                <div class="col-md-9">
                    <div class="card-body text-center">
                        <h6 class="card-title">Announcements <span class="badge badge-secondary"><?php _e($result->statistics->totalAnnouncements); ?></span></h6>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped bg-info" role="progressbar" style="width: <?php echo ($result->statistics->totalAnnouncements / $result->statistics->totalGroups) * 100; ?>%;" aria-valuenow="<?php echo ($result->statistics->totalAnnouncements / $result->statistics->totalGroups) * 100; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo round(($result->statistics->totalAnnouncements / $result->statistics->totalGroups) * 100, 2); ?>%
                            </div>
                        </div>
                    </div>
                </div>
                <p class="card-text text-center" style="background-color: blueviolet; border-radius: 0px 0px 10px 10px; padding-top: 5px;"><small style="color: #fff">Total Announcements</small></p>
            </div>
        </div>
    </div>
</div>

<div class="row statistics-row">
    <div class="col-12 col-md-6 mb-3">
        <div class="card mb-3 h-100">
            <div class="row g-0 b-r-10" style="border: 1px solid #f3f3f3; height: 100%;">
                <div class="col-md-3 d-flex align-items-center justify-content-center">
                    <i class="fas fa-camera fa-2x"></i>
                </div>
                <div class="col-md-9">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total with Photos <span class="badge badge-secondary"><?php _e($result->statistics->totalWithPhotos); ?></span></h6>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped bg-info" role="progressbar" style="width: <?php echo ($result->statistics->totalWithPhotos / $result->statistics->totalGroups) * 100; ?>%;" aria-valuenow="<?php echo ($result->statistics->totalWithPhotos / $result->statistics->totalGroups) * 100; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo round(($result->statistics->totalWithPhotos / $result->statistics->totalGroups) * 100, 2); ?>%
                            </div>
                        </div>
                    </div>
                </div>
                <p class="card-text text-center" style="background-color: blueviolet; border-radius: 0px 0px 10px 10px; padding-top: 5px;"><small style="color: #fff">Total with Photos</small></p>
            </div>
        </div>
    </div>
    <div class="col-12 col-md-6 mb-3">
        <div class="card mb-3 h-100">
            <div class="row g-0 b-r-10" style="border: 1px solid #f3f3f3; height: 100%;">
                <div class="col-md-3 d-flex align-items-center justify-content-center">
                    <i class="fas fa-link fa-2x"></i>
                </div>
                <div class="col-md-9">
                    <div class="card-body text-center">
                        <h6 class="card-title">Total with Invite Links <span class="badge badge-secondary"><?php _e($result->statistics->totalWithInviteLinks); ?></span></h6>
                        <div class="progress">
                            <div class="progress-bar progress-bar-striped bg-info" role="progressbar" style="width: <?php echo ($result->statistics->totalWithInviteLinks / $result->statistics->totalGroups) * 100; ?>%;" aria-valuenow="<?php echo ($result->statistics->totalWithInviteLinks / $result->statistics->totalGroups) * 100; ?>" aria-valuemin="0" aria-valuemax="100">
                                <?php echo round(($result->statistics->totalWithInviteLinks / $result->statistics->totalGroups) * 100, 2); ?>%
                            </div>
                        </div>
                    </div>
                </div>
                <p class="card-text text-center" style="background-color: blueviolet; border-radius: 0px 0px 10px 10px; padding-top: 5px;"><small style="color: #fff">Total with Invite Links</small></p>
            </div>
        </div>
    </div>
</div>




<div class="card b-r-6">
    <div class="card-body p-0">
        <table class="table align-middle mb-0">
            <tbody>
                <?php if ($status == "success") : ?>
                    <?php if ($result->status == "success" && !empty($result->data)) : ?>
                        <?php foreach ($result->data as $key => $value) : ?>
                            
                            <?php
                            $filtered_arr = array_filter(
                                $value->participants,
                                function ($obj) use ($account) {
                                    return isset($obj->admin) && str_replace('@s.whatsapp.net', '', $obj->id) == $account->username;
                                }
                            );
                            ?>

                            <tr>
                                <td class="p-25 border-bottom">
                                    <div class="d-flex align-items-center">
                                        <!-- Contêiner para a foto ou as iniciais -->
                                        <div class="me-3">
                                            <?php
                                            $profilePicUrl = isset($value->profilePicUrl) ? $value->profilePicUrl : '';
                                            
                                            // Verifica se a URL da foto é válida
                                            $isValidUrl = filter_var($profilePicUrl, FILTER_VALIDATE_URL);
                                            
                                            if ($isValidUrl && !empty($profilePicUrl)) : ?>
                                                <!-- Mostrar a foto do grupo -->
                                                <img src="<?php echo htmlspecialchars($profilePicUrl); ?>" alt="<?php _e($value->name) ?>" class="img-thumbnail rounded-circle" style="width: 60px; height: 60px;">
                                            <?php else : ?>
                                                <!-- Mostrar as iniciais do grupo -->
                                                <div class="initials">
                                                    <?php _e('NO PICTURE') ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div>
                                            <div class="fs-14 mb-1 fw-6 text-gray-800"><?php _e($value->name) ?></div>
                                            <div class="fs-12 mb-1 text-gray-600"><?php _e("Group ID: ") ?> <?php _e($value->id) ?></div>
                                            <div class="fs-10 mb-1 text-white bg-dark d-inline p-l-8 p-r-8 p-t-3 p-b-3 b-r-10"><?php _e(sprintf(__("%s participants"), $value->size)) ?></div>
                                            <div class="fs-10 mb-1 text-white bg-dark d-inline p-l-8 p-r-8 p-t-3 p-b-3 b-r-10">
                                                <?php _e("Created:")?> <?php echo date('d/m/Y', $value->creation); ?>
                                            </div>
                                            
                                            <?php
                                            // Inicialize as variáveis para o texto e a cor de fundo
                                            $text = '';
                                            $bgColor = '';
                                            
                                            // Verifica se o grupo é uma comunidade e/ou se é apenas para anúncios
                                            if ($value->isCommunity) {
                                                if ($value->announce) {
                                                    $text = 'COMUNIDADE - ANÚNCIOS';
                                                    $bgColor = 'bg-warning'; // Cor de fundo para COMUNIDADE - ANÚNCIOS
                                                } else {
                                                    $text = 'COMUNIDADE';
                                                    $bgColor = 'bg-info'; // Cor de fundo para COMUNIDADE
                                                }
                                            } else {
                                                if ($value->announce) {
                                                    $text = 'COMUNIDADE | ANÚNCIOS';
                                                    $bgColor = 'bg-info'; // Cor de fundo para ANÚNCIOS
                                                } else {
                                                    $text = 'GRUPO NORMAL';
                                                    $bgColor = 'bg-secondary'; // Cor de fundo para GRUPO NORMAL
                                                }
                                            }
                                            ?>
                                            
                                            <div class="fs-10 mb-1 ms-2 text-white d-inline p-l-8 p-r-8 p-t-3 p-b-3 b-r-10 <?php echo $bgColor; ?>">
                                                <?php echo $text; ?>
                                            </div>

                                            <?php if (count($filtered_arr) > 0) : ?>
                                                <div class="fs-10 mb-1 text-white bg-success d-inline p-l-8 p-r-8 p-t-3 p-b-3 b-r-10 ms-2"><?php _e('Admin') ?></div>
                                            <?php endif ?>
                                            
                                            <!-- Link de convite -->
                                            <?php if (isset($value->inviteCode)) : ?>
                                                <div class="fs-10 mb-1 mt-2">
                                                    <a href="<?php _e($value->inviteCode) ?>" target="_blank" class="btn btn-info btn-sm btn-copy-id"data-clipboard-text="<?php _e($value->inviteCode) ?>"><?php _e("Join Group") ?></a>
                                                    
                                                    <button class="btn btn-light btn-sm btn-copy-invite me-2" data-clipboard-text="<?php _e($value->inviteCode) ?>"><?php _e("Copy Invite Code") ?></button>
                                                </div>
                                            <?php endif ?>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-25 border-bottom text-end">
                                    <button class="btn btn-dark btn-sm btn-copy-id" data-clipboard-text="<?php _e($value->id) ?>"><?php _e("Copy Id") ?></button>
                                    <a href="<?php _e(get_module_url("export_group/{$account->ids}/{$value->id}")) ?>" class="btn btn-dark btn-sm"><?php _e("Download") ?></a>
                                </td>
                            </tr>

                        <?php endforeach ?>
                    <?php else : ?>
                        <tr>
                            <td class="p-20">
                                <?php _ec($this->include('Core\Whatsapp\Views\empty'), false); ?>
                            </td>
                        </tr>
                    <?php endif ?>
                <?php else : ?>
                    <tr>
                        <td class="p-20">
                            <?php _ec($this->include('Core\Whatsapp\Views\empty'), false); ?>
                        </td>
                    </tr>
                <?php endif ?>
            </tbody>
        </table>
    </div>
</div>
