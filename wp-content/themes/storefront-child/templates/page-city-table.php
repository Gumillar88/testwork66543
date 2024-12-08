<?php
/* 
** Template Name: City Table 
*/
get_header();

global $wpdb;

// Load existing search query for fallback
$search_query = isset($_GET['search']) ? sanitize_text_field($_GET['search']) : '';

?>

<div class="container my-5">
    <h1 class="text-center mb-4">List Cities</h1>

    <!-- Search Form -->
    <form id="city-search-form" class="mb-3">
        <div class="input-group">
            <input type="text" id="city-search-input" name="search" class="form-control" placeholder="Find Cities..."
                value="<?php echo esc_attr($search_query); ?>" />
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>

    <!-- Before Table Hook -->
    <?php do_action('before_city_table'); ?>

    <!-- City Table -->
    <table class="table table-striped">
        <thead class="table-bordered table-responsive-sm table-active">
            <tr>
                <th>City</th>
                <th>Country</th>
                <th>Temp (°C)</th>
                <th>Temp Min (°C)</th>
                <th>Temp Max (°C)</th>
                <th>Pressure</th>
                <th>Humidity</th>
                <th>Sea level</th>
            </tr>
        </thead>
        <tbody id="city-table-body">
            <!-- Data will be dynamically loaded via AJAX -->
        </tbody>
    </table>

    <!-- After Table Hook -->
    <?php do_action('after_city_table'); ?>
</div>

<script>
// Add nonce and AJAX URL to JS variables
const ajaxSearch = {
    url: "<?php echo admin_url('admin-ajax.php'); ?>",
    nonce: "<?php echo wp_create_nonce('ajax_city_search'); ?>",
};
</script>

<?php
get_footer();