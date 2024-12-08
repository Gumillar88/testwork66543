(function ($) {
    $(document).ready(function () {
        $('#city-search-form').on('submit', function (e) {
            e.preventDefault();

            const searchQuery = $('#city-search-input').val();

            // AJAX request
            $.ajax({
                url: ajaxSearch.url,
                method: 'POST',
                data: {
                    action: 'fetch_cities',
                    nonce: ajaxSearch.nonce,
                    search: searchQuery,
                },
                beforeSend: function () {
                    $('#city-table-body').html('<tr><td colspan="8" class="text-center">Loading...</td></tr>');
                },
                success: function (response) {
                    if (response.success) {
                        $('#city-table-body').html(response.data.html);
                    } else {
                        $('#city-table-body').html('<tr><td colspan="3" class="text-center">No city data found.</td></tr>');
                    }
                },
                error: function () {
                    $('#city-table-body').html('<tr><td colspan="3" class="text-center">An error occurred. Please try again.</td></tr>');
                },
            });
        });

        // Trigger search form submit on page load for fallback
        $('#city-search-form').trigger('submit');
    });
})(jQuery);
