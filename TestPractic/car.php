<?php
/*
 * Plugin name: CustomPostType
 * Description: Test Practice WordPress
 */

function cpt_custom_car_post_type(): void
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
function cpt_add_custom_fields_to_car_posts(): void
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
function cpt_car_custom_fields_meta_box(): void
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
function cpt_car_custom_fields_meta_box_callback($post): void
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
function cpt_car_save_custom_fields($post_id): void
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

function cpt_carlist_shortcode($atts): string
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
        $output .= '<form id="car-filter-form" method="get" action="' . esc_url(home_url('/')) . '">';
        $output .= '<label for="manufacturer">Manufacturer:</label>';
        $output .= '<select name="manufacturer" id="manufacturer">';
        $output .= '<option value="">All</option>';
        $output .= '<option value="Opel" ' . selected($args['manufacturer'], 'Opel', false) . '>Opel</option>';
        $output .= '<option value="Renault" ' . selected($args['manufacturer'], 'Renault', false) . '>Renault</option>';
        $output .= '<option value="Dacia" ' . selected($args['manufacturer'], 'Dacia', false) . '>Dacia</option>';
        $output .= '</select>';

        $output .= '<label for="fuel">Fuel:</label>';
        $output .= '<select name="fuel" id="fuel">';
        $output .= '<option value="">All</option>';
        $output .= '<option value="Gasoline" ' . selected($args['fuel'], 'Gasoline', false) . '>Gasoline</option>';
        $output .= '<option value="Diesel" ' . selected($args['fuel'], 'Diesel', false) . '>Diesel</option>';
        $output .= '<option value="Electric" ' . selected($args['fuel'], 'Electric', false) . '>Electric</option>';
        $output .= '<option value="GPL" ' . selected($args['fuel'], 'GPL', false) . '>GPL</option>';
        $output .= '</select>';

        $output .= '<label for="color">Color:</label>';
        $output .= '<select name="color" id="color">';
        $output .= '<option value="">All</option>';
        $output .= '<option value="Red" ' . selected($args['color'], 'Red', false) . '>Red</option>';
        $output .= '<option value="Blue" ' . selected($args['color'], 'Blue', false) . '>Blue</option>';
        $output .= '<option value="Green" ' . selected($args['color'], 'Green', false) . '>Green</option>';
        $output .= '<option value="Yellow" ' . selected($args['color'], 'Yellow', false) . '>Yellow</option>';
        $output .= '<option value="Black" ' . selected($args['color'], 'Black', false) . '>Black</option>';
        $output .= '<option value="White" ' . selected($args['color'], 'White', false) . '>White</option>';
        $output .= '</select>';

        $output .= '<input type="submit" value="Filter">';
        $output .= '</form>';
    }

    if ($car_query->have_posts()) {
        $output .= '<table class="car-list-table">';
        $output .= '<tr>';
        $output .= '<th>Car</th>';
        $output .= '<th>Manufacturer</th>';
        $output .= '<th>Fuel</th>';
        $output .= '<th>Color</th>';
        $output .= '</tr>';

        while ($car_query->have_posts()) {
            $car_query->the_post();

            $car_name = esc_html(get_the_title());
            $manufacturer = esc_html(get_post_meta(get_the_ID(), 'manufacturer', true));
            $fuel = esc_html(get_post_meta(get_the_ID(), 'fuel', true));
            $color = esc_html(get_post_meta(get_the_ID(), 'color', true));

            $output .= '<div class="car-container">';
            $output .= '<div class="detail-cell car-name-cell">' . $car_name . '</div>';
            $output .= '<div class="car-details">';
            $output .= '<div class="detail-cell">' . $manufacturer . '</div>';
            $output .= '<div class="detail-cell">' . $fuel . '</div>';
            $output .= '<div class="detail-cell">' . $color . '</div>';
            $output .= '</div>';
            $output .= '</div>';
        }


        wp_reset_postdata();
    } else {
        $output .= 'No car posts found.';
    }
    return $output;
}
add_shortcode('carlist', 'cpt_carlist_shortcode');
function cpt_enqueue_custom_styles()
{
    $css_url = plugin_dir_url(__FILE__) . '/css/custom-style.css';
    wp_enqueue_style( 'custom-styles',$css_url , array(), '1.0' );
}
add_action('wp_enqueue_scripts', 'cpt_enqueue_custom_styles');
