<li class="nav-item me-0">
     <label for="type_flow" class="nav-link bg-active-primary text-gray-700 px-4 py-3 b-r-6 text-active-white <?php _ec( get_data($result, "type") == 6 ? "active" : "" ) ?>" data-bs-toggle="pill" data-bs-target="#wa_flow" type="button" role="tab"><?php _e("Flow")?></label>
     <input class="d-none" type="radio" name="type" id="type_flow" <?php _ec( get_data($result, "type") == 6 ? "checked='true'" : "" ) ?> value="6">
</li>
