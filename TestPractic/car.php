<?php
/*
 * Plugin name: CustomPostType
 * Description: Test Practice WordPress
 */

function cpt_custom_car_post_type()
{
    $labels = array(
        'name' => 'Cars',
        'singular_name' => 'Car',
        'menu_name' => 'Cars',
        'add_new' => 'Add New',
        'add_new_item' => 'Add New Car',
        'edit_item' => 'Edit Car',
        'new_item' => 'New Car',
        'view_item' => 'View Car',
        'search_items' => 'Search Cars',
        'not_found' => 'No Cars found',
        'not_found_in_trash' => 'No Cars found in Trash',
    );

    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array('slug' => 'car'),
        'capability_type' => 'post',
        'has_archive' => true,
        'hierarchical' => false,
        'menu_position' => null,
        'supports' => array('title', 'editor', 'thumbnail', 'custom-fields'),
    );

    register_post_type('car', $args);
}
add_action('init', 'cpt_custom_car_post_type');
function cpt_add_custom_fields_to_car_posts()
{
    $car_posts = get_post(array('post_type' => 'car', 'posts_per_page' => -1, 'fields' => 'ids',));
    $fuel_options = array('Gasoline', 'Diesel', 'Electric', 'GPL');
    $manufacturer_options = array('Opel', 'Renault', 'Dacia');
    $color_options = array('Red', 'Blue', 'Green', 'Black', 'White', 'Yellow');

    foreach ($car_posts as $post_id) {
        if (get_post_type($post_id) == 'car') {
            $selected_fuel = $fuel_options[array_rand($fuel_options)];
            add_post_meta($post_id, 'fuel', $selected_fuel, true) or update_post_meta($post_id, 'fuel', $selected_fuel);

            $selected_manufacturer = $manufacturer_options[array_rand($manufacturer_options)];
            add_post_meta($post_id, 'manufacturer', $selected_manufacturer, true) or update_post_meta($post_id, 'manufacturer', $selected_manufacturer);

            $selected_color = $color_options[array_rand($color_options)];
            add_post_meta($post_id, 'color', $selected_color, true) or update_post_meta($post_id, 'color', $selected_color);
        }
    }
}
add_action('init', 'cpt_add_custom_fields_to_car_posts');
function cpt_car_custom_fields_meta_box()
{
    {
        add_meta_box(
            'cpt_car_custom_fields_meta_box',
            'Car Custom Fields',
            'cpt_car_custom_fields_meta_box_callback',
            'home',
            'normal',
            'low'
        );
    }
}
add_action('add_meta_boxes', 'cpt_car_custom_fields_meta_box');
function cpt_car_custom_fields_meta_box_callback($post)
{
    {
        $fuel_value = get_post_meta($post->ID, 'fuel', true);
        $manufacturer_value = get_post_meta($post->ID, 'manufacturer', true);
        $color_value = get_post_meta($post->ID, 'color', true);

        ?>
        <table class="form-table">
            <tr>
                <th><label for="fuel_field">Fuel:</label></th>
                <td><input type="text" id="fuel_field" name="fuel_field" value="<?php echo esc_attr($fuel_value); ?>" /></td>
            </tr>
            <tr>
                <th><label for="manufacturer_field">Manufacturer:</label></th>
                <td><input type="text" id="manufacturer_field" name="manufacturer_field" value="<?php echo esc_attr($manufacturer_value); ?>" /></td>
            </tr>
            <tr>
                <th><label for="color_field">Color:</label></th>
                <td><input type="text" id="color_field" name="color_field" value="<?php echo esc_attr($color_value); ?>" /></td>
            </tr>
        </table>
        <?php
    }
}
 function cpt_car_save_custom_fields($post_id)
 {
    {
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }

        if (isset($_POST['fuel_field'])) {
            $fuel_value = sanitize_text_field($_POST['fuel_field']);
            update_post_meta($post_id, 'fuel', $fuel_value);
        }

        if (isset($_POST['manufacturer_field'])) {
            $manufacturer_value = sanitize_text_field($_POST['manufacturer_field']);
            update_post_meta($post_id, 'manufacturer', $manufacturer_value);
        }

        if (isset($_POST['color_field'])) {
            $color_value = sanitize_text_field($_POST['color_field']);
            update_post_meta($post_id, 'color', $color_value);
        }
    }
}
add_action('save_post', 'cpt_car_save_custom_fields');
function cpt_carlist_shortcode($atts)
{
    $args = shortcode_atts(
        array(
            'fuel' => '',
            'manufacturer' => '',
            'color' => '',
            'showfilters' => 1,
        ),
        $atts
    );
    if (isset($_GET['manufacturer'])) {
        $args['manufacturer'] = sanitize_text_field($_GET['manufacturer']);
    }

    if (isset($_GET['fuel'])) {
        $args['fuel'] = sanitize_text_field($_GET['fuel']);
    }

    if (isset($_GET['color'])) {
        $args['color'] = sanitize_text_field($_GET['color']);
    }

    $car_query_args = array(
        'post_type' => 'car',
        'posts_per_page' => -1,
        'meta_query' => array(),
    );

    if (!empty($args['manufacturer'])) {
        $car_query_args['meta_query'][] = array(
            'key' => 'manufacturer',
            'value' => $args['manufacturer'],
            'compare' => 'LIKE',
        );
    }

    if (!empty($args['fuel'])) {
        $car_query_args['meta_query'][] = array(
            'key' => 'fuel',
            'value' => $args['fuel'],
            'compare' => 'LIKE',
        );
    }
    if (!empty($args['color'])) {
        $car_query_args['meta_query'][] = array(
            'key' => 'color',
            'value' => $args['color'],
            'compare' => 'LIKE',
        );
    }

    $car_query = new WP_Query($car_query_args);
    $output = '';

    if ($args['showfilters'] == 1) {
        $output .= '<form id="car-filter-form" method="get" action="' . esc_url(home_url('/')) . '">
            <label for="manufacturer">Manufacturer:</label>
            <select name="manufacturer" id="manufacturer">
                <option value="">All</option>
                <option value="Opel">Opel</option>
                <option value="Renault">Renault</option>
                <option value="Dacia">Dacia</option>
            </select>
            <label for="fuel">Fuel:</label>
            <select name="fuel" id="fuel">
                <option value="">All</option>
                <option value="Gasoline">Gasoline</option>
                <option value="Diesel">Diesel</option>
                <option value="Electric">Electric</option>
                <option value="GPL">GPL</option>
            </select>
            <label for="color">Color:</label>
            <select name="color" id="color">
                <option value="">All</option>
                <option value="Red">Red</option>
                <option value="Blue">Blue</option>
                <option value="Green">Green</option>
                <option value="Yellow">Yellow</option>
                <option value="Black">Black</option>
                <<option value="White">White</option>
            </select>
            <input type="submit" value="Filter">
        </form>';
    }

    if ($car_query->have_posts()) {

        while ($car_query->have_posts()) {
            $car_query->the_post();
            $output .= '<p>';
            $output .= '<strong>' . esc_html(get_the_title()) . '</strong><br>';
            $output .= 'Manufacturer: ' . esc_html(get_post_meta(get_the_ID(), 'manufacturer', true)) . '<br>';
            $output .= 'Fuel: ' . esc_html(get_post_meta(get_the_ID(), 'fuel', true)) . '<br>';
            $output .= 'Color: ' . esc_html(get_post_meta(get_the_ID(), 'color', true)) . '<br>';
            $output .= '</p>';
        }
        wp_reset_postdata();
    } else {
        $output .= 'No car posts found.';
    }
    return $output;
}
add_shortcode('carlist', 'cpt_carlist_shortcode');


