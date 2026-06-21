<li class="nav-item me-0">
     <label for="type_meta_template" class="nav-link bg-active-primary text-gray-700 px-4 py-3 b-r-6 text-active-white <?php _ec( (get_data($result, "type") == 6)?"active":"" ) ?>" data-bs-toggle="pill" data-bs-target="#wa_meta_template" type="button" role="tab"><?php _e("Templates Meta (Oficial)")?></label>
     <input class="d-none" type="radio" name="type" id="type_meta_template" <?php _ec( (get_data($result, "type") == 6)?"checked='true'":"" ) ?> value="6">
</li>
