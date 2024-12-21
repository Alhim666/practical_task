<?php

/*
Plugin Name: Car Checker
Description: A plugin that calculates free and booked days for a car and returns car brand and model info.
Version: 1.0
Author: Yanushevych Dmytro
*/

function car_checker_register_route() {
    register_rest_route('car_checker/v1', '/free-days', array(
        'methods' => 'POST',
        'callback' => 'car_checker_calculate_free_days',
        'permission_callback' => '__return_true',
    ));
    register_rest_route('car_checker/v1', '/free-days-all', array(
        'methods' => 'POST',
        'callback' => 'car_checker_calculate_free_days_all',
        'permission_callback' => '__return_true',
    ));
}


function get_all_cars(): mixed {
    global $wpdb;

    $cars = $wpdb->get_results(
        "
        SELECT car_id 
        FROM rc_cars
        "
    );

    return $cars;
}

function get_car_info($car_id) {
    global $wpdb;

    $car_info = $wpdb->get_row(
        $wpdb->prepare(
            "
            SELECT rc_cars_brands.icon AS brand_icon, rc_cars_brands.slug AS brand_slug, 
                   rc_cars_models.slug AS model_slug, rc_cars_models.type AS model_type
            FROM rc_cars
            JOIN rc_cars_models ON rc_cars.car_model_id = rc_cars_models.car_model_id
            JOIN rc_cars_brands ON rc_cars_models.car_brand_id = rc_cars_brands.car_brand_id
            WHERE rc_cars.car_id = %d
            ",
            $car_id
        )
    );

    return $car_info;
}

function get_all_days_in_period($start_date, $end_date) {
    $period_start = strtotime($start_date);
    $period_end = strtotime($end_date);
    $all_days = [];

    for ($current_date = $period_start; $current_date <= $period_end; $current_date += 86400) {
        $all_days[] = date('Y-m-d', $current_date);
    }

    return $all_days;
}

function get_free_days($all_days, $bookings) {
    foreach ($bookings as $booking) {
        $booking_start = strtotime($booking->start_date);
        $booking_end = strtotime($booking->end_date);

        $booked_days = [];
        for ($current_booking_day = $booking_start; $current_booking_day <= $booking_end; $current_booking_day += 86400) {
            $booked_days[] = date('Y-m-d', $current_booking_day);
        }

        $all_days = array_diff($all_days, $booked_days);
    }

    return $all_days;
}

function car_checker_calculate_free_days(WP_REST_Request $request) {
    global $wpdb;

    $car_id = $request->get_param('car_id');
    $start_date = $request->get_param('start_date');
    $end_date = $request->get_param('end_date');

    if (!$car_id) {
        return new WP_REST_Response(
            array(
                'status' => 'error',
                'message' => 'Missing required parameter: car_id.',
            ),
            400
        );
    }

    if (!$start_date || !$end_date) {
        $results = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT * 
                FROM rc_bookings 
                WHERE car_id = %d
                  AND status = 1
                  AND company_id = 1
                  AND is_deleted = 0
                ORDER BY start_date ASC
                ",
                $car_id
            )
        );

        if (empty($results)) {
            return new WP_REST_Response(
                array(
                    'status' => 'error',
                    'message' => 'No bookings found for the given car.',
                ),
                404
            );
        }

        $start_date = $results[0]->start_date;
        $end_date = $results[count($results) - 1]->end_date;
    }
    
    $results = $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT * 
            FROM rc_bookings 
            WHERE car_id = %d
              AND status = 1
              AND company_id = 1
              AND start_date >= %s AND end_date <= %s
              AND is_deleted = 0
            ORDER BY start_date ASC
            ",
            $car_id,
            $start_date,
            $end_date
        )
    );

    if (empty($results)) {
        return new WP_REST_Response(
            array(
                'status' => 'error',
                'message' => 'No bookings found for the given parameters.',
            ),
            404
        );
    }

    $all_days = get_all_days_in_period($start_date, $end_date);
    $free_days = get_free_days($all_days, $results);

    $total_free_days = count($free_days);
    $total_days = count($all_days) + count($results);

    $car_info = get_car_info($car_id);

    return new WP_REST_Response(
        array(
            'status' => 'success',
            'data' => array(
                'car_info' => $car_info,
                'free_days' => $total_free_days,
                'all_days' => $total_days,
                'start_date' => $start_date,
                'end_date' => $end_date,
            ),
        ),
        200
    );
}

function calculate_free_days_for_all_cars_paginated($start_date, $end_date, $page, $per_page) {
    global $wpdb;

    if (!$start_date || !$end_date || !$page || !$per_page) {
        return array(
            'status' => 'error',
            'message' => 'Missing required parameters: start_date, end_date, page, per_page.',
        );
    }

    $offset = ($page - 1) * $per_page;

    $cars = $wpdb->get_results(
        $wpdb->prepare(
            "
            SELECT car_id 
            FROM rc_cars
            LIMIT %d OFFSET %d
            ",
            $per_page,
            $offset
        )
    );

    $total_cars = $wpdb->get_var("SELECT COUNT(*) FROM rc_cars");

    if (empty($cars)) {
        return array(
            'status' => 'error',
            'message' => 'No cars found for the given page and per_page parameters.',
        );
    }

    $results = [];

    foreach ($cars as $car) {
        $bookings = $wpdb->get_results(
            $wpdb->prepare(
                "
                SELECT * 
                FROM rc_bookings 
                WHERE car_id = %d
                  AND status = 1
                  AND company_id = 1
                  AND start_date >= %s AND end_date <= %s
                  AND is_deleted = 0
                ORDER BY start_date ASC
                ",
                $car->car_id,
                $start_date,
                $end_date
            )
        );

        $all_days = get_all_days_in_period($start_date, $end_date);
        $free_days = get_free_days($all_days, $bookings);

        $car_info = get_car_info($car->car_id);

        $results[] = array(
            'car_id' => $car->car_id,
            'car_info' => $car_info,
            'free_days' => count($free_days),
            'total_days' => count($all_days),
        );
    }

    return array(
        'status' => 'success',
        'data' => $results,
        'pagination' => array(
            'current_page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total_cars / $per_page),
            'total_items' => $total_cars,
        ),
    );
}


function car_checker_calculate_free_days_all(WP_REST_Request $request) {
    $start_date = $request->get_param('start_date');
    $end_date = $request->get_param('end_date');
    $page = (int) $request->get_param('page');
    $per_page = (int) $request->get_param('per_page');

    if ($page < 1) $page = 1;
    if ($per_page < 1) $per_page = 10;

    $result = calculate_free_days_for_all_cars_paginated($start_date, $end_date, $page, $per_page);

    return new WP_REST_Response($result, 200);
}

add_action('rest_api_init', 'car_checker_register_route');
