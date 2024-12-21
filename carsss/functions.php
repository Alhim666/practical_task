<?php

add_action('wp_enqueue_scripts', 'include_style');
function include_style()
{
    wp_enqueue_style('style', get_stylesheet_uri());
    wp_enqueue_style('base', get_template_directory_uri() . '/assets/css/base.css');
    wp_enqueue_style('404', get_template_directory_uri() . '/assets/css/404.css');
    wp_enqueue_style('nav', get_template_directory_uri() . '/assets/css/nav.css');
    wp_enqueue_style('form', get_template_directory_uri() . '/assets/css/form.css');
    wp_enqueue_style('table', get_template_directory_uri() . '/assets/css/table.css');
}

add_action('after_setup_theme', 'myMenu');
function myMenu()
{
    register_nav_menu('top_nav', 'top navigation bar');
}


add_action('wp_footer', 'include_script');
function include_script()
{
}


function include_all_cars_availability_script()
{
    wp_enqueue_script('car_api_request', get_template_directory_uri() . '/assets/js/table_car_api_request.js', null, null, false);
}
add_shortcode('include_all_cars_availability_script', 'include_all_cars_availability_script');

function draw_all_cars_availability_form()
{
    echo '<div class="container">
        <div class="wrap">
            <h1>Car Availability</h1>
            <form id="car-availability-form">
                <label for="start_date">Start Date:</label>
                <input type="date" id="start_date" name="start_date" required>

                <label for="end_date">End Date:</label>
                <input type="date" id="end_date" name="end_date" required>

                <button class="submit" type="button" id="get_data">Get Data</button>
            </form>

            <div id="car-results"></div>
        </div>
    </div>';
}
add_shortcode('draw_all_cars_availability_form', 'draw_all_cars_availability_form');


// спосіб виведення через пхп а не js
function draw_single_car_info() {
    $car_id = isset($_GET['id']) ? sanitize_text_field($_GET['id']) : '';

    if (!empty($car_id)) {
        $start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : '';
        $end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : '';

        $data = array(
            'car_id' => $car_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
        );

        $base_url = get_site_url();
        $api_url = $base_url . '/wp-json/car_checker/v1/free-days';

        $response = wp_remote_post($api_url, array(
            'method'    => 'POST',
            'body'      => json_encode($data),
            'headers'   => array(
                'Content-Type' => 'application/json',
            ),
        ));

        if (is_wp_error($response)) {
            return 'There was an error retrieving the car information.';
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['status']) && $data['status'] == 'success') {
            $car_info = $data['data']['car_info'];

            $output = '<div class="car-info">';
            $output .= '<h2>' . $car_info['brand_slug'] . ' (' . $car_info['model_slug'] . ')</h2>';
            $output .= '<p>Type: ' . $car_info['model_type'] . '</p>';
            $output .= '<p>Start date: ' . $data['data']['start_date'] . ' || End date: '. $data['data']['end_date'] .'</p>';
            $output .= '<p>All days: ' . $data['data']['all_days'] . '</p>';
            $output .= '<p>Free days: ' . $data['data']['free_days'] . '</p>';
            $output .= '</div>';
        } else {
            $output = 'Car information not found.';
        }
    } else {
        $output = 'Car ID not provided.';
    }

    $form = '
    <form method="get" action="">
        <label for="start_date">Start Date:</label>
        <input type="date" id="start_date" name="start_date" value="' . (isset($_GET['start_date']) ? esc_attr($_GET['start_date']) : '') . '">
        <label for="end_date">End Date:</label>
        <input type="date" id="end_date" name="end_date" value="' . (isset($_GET['end_date']) ? esc_attr($_GET['end_date']) : '') . '">
        <label for="end_date">ID:</label>
        <input name="id" value="' . esc_attr($car_id) . '">
        <input class="submit" type="submit" value="Check Availability">
    </form>';

    return $form . $output;
}

add_shortcode('draw_single_car_info', 'draw_single_car_info');



