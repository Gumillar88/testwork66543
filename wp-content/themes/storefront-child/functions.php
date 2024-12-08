<?php

/*
** Input data wp_terms, wp_term_taxonomy, wp_term_relationships, wp_posts, dan wp_postmeta
*/ 
function add_european_country_city_data()
{
    global $wpdb;

    // Data to add: European countries, cities, and their coordinates
    $new_locations = [
        ['country' => 'France', 'city' => 'Paris', 'latitude' => '48.8566', 'longitude' => '2.3522'],
        ['country' => 'Germany', 'city' => 'Berlin', 'latitude' => '52.5200', 'longitude' => '13.4050'],
        ['country' => 'Italy', 'city' => 'Rome', 'latitude' => '41.9028', 'longitude' => '12.4964'],
        ['country' => 'Spain', 'city' => 'Madrid', 'latitude' => '40.4168', 'longitude' => '-3.7038'],
        ['country' => 'United Kingdom', 'city' => 'London', 'latitude' => '51.5074', 'longitude' => '-0.1278'],
    ];

    foreach ($new_locations as $location) {
        $country_name = $location['country'];
        $city_name = $location['city'];
        $latitude = $location['latitude'];
        $longitude = $location['longitude'];

        // Step 1: Check if the country already exists in wp_terms
        $country_term = $wpdb->get_row(
            $wpdb->prepare("SELECT * FROM {$wpdb->terms} WHERE name = %s", $country_name)
        );

        if (!$country_term) {
            // Add the country if it does not exist
            $wpdb->insert(
                $wpdb->terms,
                ['name' => $country_name, 'slug' => sanitize_title($country_name), 'term_group' => 0],
                ['%s', '%s', '%d']
            );
            $country_term_id = $wpdb->insert_id;

            $wpdb->insert(
                $wpdb->term_taxonomy,
                ['term_id' => $country_term_id, 'taxonomy' => 'country', 'description' => '', 'parent' => 0, 'count' => 0],
                ['%d', '%s', '%s', '%d', '%d']
            );
            $country_taxonomy_id = $wpdb->insert_id;
        } else {
            // Retrieve the existing term_taxonomy_id
            $country_term_id = $country_term->term_id;

            $country_taxonomy_id = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d AND taxonomy = 'country'",
                    $country_term_id
                )
            );
        }

        // Step 2: Check if the city already exists in wp_posts
        $city_post_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_title = %s AND post_type = 'city' AND post_status = 'publish'",
                $city_name
            )
        );

        if (!$city_post_id) {
            // Add the city if it does not exist
            $wpdb->insert(
                $wpdb->posts,
                [
                    'post_title' => $city_name,
                    'post_type' => 'city',
                    'post_status' => 'publish',
                    'post_date' => current_time('mysql'),
                    'post_date_gmt' => current_time('mysql', 1),
                ],
                ['%s', '%s', '%s', '%s', '%s']
            );
            $city_post_id = $wpdb->insert_id;

            // Link the city to the country in wp_term_relationships
            $wpdb->insert(
                $wpdb->term_relationships,
                ['object_id' => $city_post_id, 'term_taxonomy_id' => $country_taxonomy_id],
                ['%d', '%d']
            );

            // Add latitude and longitude metadata in wp_postmeta
            $wpdb->insert(
                $wpdb->postmeta,
                ['post_id' => $city_post_id, 'meta_key' => 'city_latitude', 'meta_value' => $latitude],
                ['%d', '%s', '%s']
            );

            $wpdb->insert(
                $wpdb->postmeta,
                ['post_id' => $city_post_id, 'meta_key' => 'city_longitude', 'meta_value' => $longitude],
                ['%d', '%s', '%s']
            );
        }
    }
}

// Run this function when the theme is activated
add_action('after_setup_theme', 'add_european_country_city_data');


function storefront_child_enqueue_styles()
{
    wp_enqueue_style('parent-style', get_stylesheet_directory_uri() . '/assets/css/style.css');
}
add_action('wp_enqueue_scripts', 'storefront_child_enqueue_styles');

function enqueue_bootstrap_and_jquery()
{
    // Deregister old jQuery version in WordPress
    wp_deregister_script('jquery');

    // Add jQuery CDN
    wp_enqueue_script('jquery', 'https://code.jquery.com/jquery-3.6.4.min.js', [], null, true);

    // Add Bootstrap CSS and JS CDN
    wp_enqueue_style('bootstrap-css', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/css/bootstrap.min.css', [], null);
    wp_enqueue_script('bootstrap-js', 'https://cdn.jsdelivr.net/npm/bootstrap@5.3.1/dist/js/bootstrap.bundle.min.js', ['jquery'], null, true);
}
add_action('wp_enqueue_scripts', 'enqueue_bootstrap_and_jquery');

// Enqueue AJAX Script
function enqueue_ajax_script()
{
    wp_enqueue_script('ajax-search', get_stylesheet_directory_uri() . '/assets/js/ajax-search.js', ['jquery'], null, true);
    wp_localize_script('ajax-search', 'ajax_object', [
        'ajax_url' => admin_url('admin-ajax.php'),
    ]);
}
add_action('wp_enqueue_scripts', 'enqueue_ajax_script');

// AJAX Handler
// AJAX Handler for fetching cities
function fetch_cities_callback()
{
    // Nonce verification for security
    check_ajax_referer('ajax_city_search', 'nonce');

    global $wpdb;

    // Sanitize the search input
    $search_query = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';

    // Query to fetch city data
    $query = "
        SELECT p.ID, p.post_title, t.name AS country
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->term_relationships} tr ON (p.ID = tr.object_id)
        LEFT JOIN {$wpdb->term_taxonomy} tt ON (tr.term_taxonomy_id = tt.term_taxonomy_id AND tt.taxonomy = 'country')
        LEFT JOIN {$wpdb->terms} t ON (tt.term_id = t.term_id)
        WHERE p.post_type = 'city' AND p.post_status = 'publish'
    ";

    if (!empty($search_query)) {
        // Securely prepare the search query
        $query .= $wpdb->prepare(" AND p.post_title LIKE %s", '%' . $wpdb->esc_like($search_query) . '%');
    }

    $query .= " ORDER BY t.name, p.post_title";
    
    // Fetch the results
    $cities = $wpdb->get_results($query);

    // Start buffering output
    ob_start();

    if (!empty($cities)) {
        foreach ($cities as $city) {
            $latitude = get_post_meta($city->ID, 'city_latitude', true);
            $longitude = get_post_meta($city->ID, 'city_longitude', true);


            // Ensure latitude and longitude are valid
            if (!empty($latitude) && !empty($longitude)) {
                // Fetch weather data using OpenWeatherMap API
                $api_key = '185dee83360ccc1641f8800f91b53f27'; // Replace with your actual API key
                $weather_data = wp_remote_get("https://api.openweathermap.org/data/2.5/weather?lat=" . $latitude . "&lon=" . $longitude . "&appid=" . $api_key . "&units=metric");
                
                // Handle API response
                if (!is_wp_error($weather_data) && wp_remote_retrieve_response_code($weather_data) == 200) {
                    $weather = json_decode(wp_remote_retrieve_body($weather_data), true);
                    
                    $temperature = $weather['main']['temp'] ?? 'N/A';
                    $temp_min = $weather['main']['temp_min'] ?? 'N/A';
                    $temp_max = $weather['main']['temp_max'] ?? 'N/A';
                    $pressure = $weather['main']['pressure'] ?? 'N/A';
                    $humidity = $weather['main']['humidity'] ?? 'N/A';
                    $sea_level = $weather['main']['sea_level'] ?? 'N/A';
                } else {
                    $temperature = 'N/A';
                    $temp_min = 'N/A';
                    $temp_max = 'N/A';
                    $pressure = 'N/A';
                    $humidity = 'N/A';
                    $sea_level = 'N/A';
                }
            } else {
                $temperature = 'N/A';
                $temp_min = 'N/A';
                $temp_max = 'N/A';
                $pressure = 'N/A';
                $humidity = 'N/A';
                $sea_level = 'N/A';
            }

            // Render the city row
            echo "<tr>
                    <td>" . esc_html($city->post_title) . "</td>
                    <td>" . esc_html($city->country ?? 'Unknown') . "</td>
                    <td>" . esc_html($temperature) . "</td>
                    <td>" . esc_html($temp_min) . "</td>
                    <td>" . esc_html($temp_max) . "</td>
                    <td>" . esc_html($pressure) . "</td>
                    <td>" . esc_html($humidity) . "</td>
                    <td>" . esc_html($sea_level) . "</td>
                </tr>";
        }
    } else {
        echo '<tr><td colspan="3" class="text-center">No city data found.</td></tr>';
    }

    // Get the output buffer content
    $html = ob_get_clean();

    // Return the HTML response
    wp_send_json_success(['html' => $html]);
}

// Register AJAX actions
add_action('wp_ajax_fetch_cities', 'fetch_cities_callback');
add_action('wp_ajax_nopriv_fetch_cities', 'fetch_cities_callback');