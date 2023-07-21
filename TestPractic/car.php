<?php
/*
 * Plugin name: CustomPostType
 * Description: Test Practice WordPress
 */

function custom_car_post_type() {
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
add_action('init', 'custom_car_post_type');

function add_custom_fields_to_car_posts() {
    $car_posts = get_post(array('post_type'=>'car','posts_per_page'=>-1,'fields'=>'ids',));
    $fuel_options = array('Gasoline', 'Diesel', 'Electric', 'GPL');
    $manufacturer_options = array('VW', 'Renault', 'Mercedes');
    $color_options = array('Red', 'Blue', 'Green','Yellow');
    function car_custom_fields_meta_box() {
        add_meta_box(
            'car_custom_fields_meta_box',
            'Car Custom Fields',
            'car_custom_fields_meta_box_callback',
            'car', // Replace 'car' with the actual slug of your custom post type
            'normal',
            'high'
        );
    }
    function car_custom_fields_meta_box_callback($post) {

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
    function car_save_custom_fields($post_id) {
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
function carlist_shortcode($atts) {
    $args = shortcode_atts(
        array('fuel' => '','manufacturer' => '','color' => '','showfilters' => 1,),
        $atts
    );
    $car_query_args = array(
        'post_type' => 'car',
        'posts_per_page' => -1,
    );

    $car_query = new WP_Query($car_query_args);
    $output = '';
    if ($car_query->have_posts()) {
        if ($args['showfilters'] == 1) {
            $output .= '<form method="get" action="' . esc_url(home_url('/')) . '"> 
                <label for="fuel">Fuel:</label>
                <input type="text" name="fuel" id="fuel" value="' . esc_attr($args['fuel']) . '"><br>
                <label for="manufacturer">Manufacturer:</label>
                <input type="text" name="manufacturer" id="manufacturer" value="' . esc_attr($args['manufacturer']) . '"><br>
                <label for="color">Color:</label>
                <input type="text" name="color" id="color" value="' . esc_attr($args['color']) . '"><br>   
                <input type="submit" value="Filter">
            </form>';
        }
        while ($car_query->have_posts()) {
            $car_query->the_post();
            $output .= '<article id="post-' . get_the_ID() . '" >
                <header class="entry-header">
                    <h2 class="entry-title">' . get_the_title() . '</h2>
                </header>
                <div class="entry-content">' . get_the_content() . '                    
                    <p>Fuel: ' . get_post_meta(get_the_ID(), 'fuel', true) . '</p>
                    <p>Manufacturer: ' . get_post_meta(get_the_ID(), 'manufacturer', true) . '</p>
                    <p>Color: ' . get_post_meta(get_the_ID(), 'color', true) . '</p>
                </div>
            </article>';
        }
        wp_reset_postdata();
    } else {
        $output .= 'No car posts found.';
    }
    return $output;
}

add_action('wp_enqueue_scripts', 'enqueue_parent_styles');
function enqueue_parent_styles()
{
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');
}
add_action('init', 'add_custom_fields_to_car_posts');
add_action('add_meta_boxes', 'car_custom_fields_meta_box');
add_action('save_post', 'car_save_custom_fields');
add_shortcode('carlist', 'carlist_shortcode');
