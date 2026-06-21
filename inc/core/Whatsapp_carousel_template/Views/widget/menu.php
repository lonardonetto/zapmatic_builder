<li class="nav-item me-0">
     <label for="type_carousel" class="nav-link bg-active-primary text-gray-700 px-4 py-3 b-r-6 text-active-white <?php _ec( get_data($result, 'type') == 5 ? 'active' : '' ) ?>" data-bs-toggle="pill" data-bs-target="#wa_carousel" type="button" role="tab"><?php _e('Carousel')?></label>
     <input class="d-none" type="radio" name="type" id="type_carousel" <?php _ec( get_data($result, 'type') == 5 ? "checked='true'" : '' ) ?> value="5">
</li>
