<?php
class clsWpMenu
{

    function __construct()
    {
        add_filter('wp_setup_nav_menu_item', [$this, 'custom_menu_item_visibility_field']);
        add_action('wp_nav_menu_item_custom_fields', [$this, 'add_visibility_fields_to_menu'], 10, 4);
        add_action('wp_update_nav_menu_item', [$this, 'save_custom_menu_fields'], 10, 3);

        add_filter('wp_get_nav_menu_items', [$this, 'filter_menu_items_by_visibility'], 10, 3);
    }

    function custom_menu_item_visibility_field($menu_item) 
    {
        $menu_item->visibility = get_post_meta($menu_item->ID, '_menu_item_visibility', true);
        $menu_item->user_roles = get_post_meta($menu_item->ID, '_menu_item_user_roles', true);

        return $menu_item;
    }

    function add_visibility_fields_to_menu($item_id, $item, $depth, $args) 
    {
        $visibility = isset($item->visibility) ? $item->visibility : '';
        $user_roles = isset($item->user_roles) ? (array) $item->user_roles : [];

        ?>
        <p class="field-visibility description description-wide">
            <label for="edit-menu-item-visibility-<?php echo $item_id; ?>">
                Show this menu item to:
                <select id="edit-menu-item-visibility-<?php echo $item_id; ?>" class="widefat" name="menu-item-visibility[<?php echo $item_id; ?>]">
                    <option value="" <?php selected($visibility, ''); ?>>Everyone</option>
                    <option value="logged_in" <?php selected($visibility, 'logged_in'); ?>>Logged In Users</option>
                    <option value="logged_out" <?php selected($visibility, 'logged_out'); ?>>Logged Out Users</option>
                </select>
            </label>
        </p>
        <p class="field-user-roles description description-wide">
            <label for="edit-menu-item-user-roles-<?php echo $item_id; ?>">
                Show only for these roles (optional):
                <br>
                <?php
                global $wp_roles;
                foreach ($wp_roles->roles as $role_key => $role) {
                    ?>
                    <label>
                        <input type="checkbox" name="menu-item-user-roles[<?php echo $item_id; ?>][]" value="<?php echo $role_key; ?>" <?php checked(in_array($role_key, $user_roles)); ?>>
                        <?php echo esc_html($role['name']); ?>
                    </label><br>
                    <?php
                }
                ?>
            </label>
        </p>
        <?php
    }

    function save_custom_menu_fields($menu_id, $menu_item_db_id, $args) 
    {
        if (isset($_POST['menu-item-visibility'][$menu_item_db_id])) {
            update_post_meta($menu_item_db_id, '_menu_item_visibility', sanitize_text_field($_POST['menu-item-visibility'][$menu_item_db_id]));
        } else {
            delete_post_meta($menu_item_db_id, '_menu_item_visibility');
        }

        if (isset($_POST['menu-item-user-roles'][$menu_item_db_id])) {
            $roles = array_map('sanitize_text_field', $_POST['menu-item-user-roles'][$menu_item_db_id]);
            update_post_meta($menu_item_db_id, '_menu_item_user_roles', $roles);
        } else {
            delete_post_meta($menu_item_db_id, '_menu_item_user_roles');
        }
    }

    function filter_menu_items_by_visibility($items, $menu, $args) 
    {
        if (is_admin()) return $items;
        
        $filtered = [];

        foreach ($items as $item) {
            $show = true;
            $visibility = get_post_meta($item->ID, '_menu_item_visibility', true);
            $user_roles = get_post_meta($item->ID, '_menu_item_user_roles', true);

            if ($visibility === 'logged_in' && !is_user_logged_in()) {
                $show = false;
            } elseif ($visibility === 'logged_out' && is_user_logged_in()) {
                $show = false;
            }

            if (!empty($user_roles) && is_user_logged_in()) {
                $current_user = wp_get_current_user();
                if (!array_intersect($user_roles, $current_user->roles)) {
                    $show = false;
                }
            }

            if ($show) {
                $filtered[] = $item;
            }
        }

        return $filtered;
    }

}

new clsWpMenu();