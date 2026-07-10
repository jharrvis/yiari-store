/*
 * YIARI Donasi Kukang Public JavaScript
 * Version: 3.1.1
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Initialize donation forms
        initializeDonationForms();
    });

    function initializeDonationForms() {
        // Initialize Indonesian form if present
        if ($('#donasiKukangForm').length > 0) {
            initializeIndonesianForm();
        }

        // Initialize English form if present
        if ($('#donasi-kukang-form-en').length > 0) {
            initializeEnglishForm();
        }
    }

    function initializeIndonesianForm() {
        console.log('Initializing Indonesian donation form...');

        // Form variables
        var subtotal = 0;
        var shippingCost = 0;
        var dollPrices = {};

        // Extract doll prices from the page
        $('.doll-card').each(function() {
            var dollName = $(this).find('div:first').text().toLowerCase();
            var priceText = $(this).find('div:nth-child(2)').text();
            var price = parseInt(priceText.replace(/[^\d]/g, '')) || 0;
            dollPrices[dollName] = price;
        });

        // Quantity control handlers removed - using inline onclick instead to avoid conflicts

        function calculateSubtotal() {
            subtotal = 0;
            $.each(dollPrices, function(dollName, price) {
                var qty = parseInt($('input[name="' + dollName + '_qty"]').val()) || 0;
                subtotal += qty * price;
            });

            $('#subtotalAmount').text(formatIDR(subtotal));
            calculateTotal();
        }

        function calculateTotal() {
            var total = subtotal + shippingCost;
            $('#totalAmount').text(formatIDR(total));
        }

        function formatIDR(amount) {
            return 'Rp ' + amount.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
        }

        // City search functionality
        var searchTimeout;
        $('#citySearch').on('input', function() {
            var query = $(this).val();
            clearTimeout(searchTimeout);

            if (query.length >= 3) {
                searchTimeout = setTimeout(function() {
                    searchCities(query);
                }, 300);
            } else {
                $('#citySearchResults').hide();
            }
        });

        function searchCities(query) {
            $.ajax({
                url: yiari_ajax.ajax_url,
                type: 'POST',
                data: {
                    action: 'get_biteship_cities',
                    query: query,
                    nonce: yiari_ajax.nonce
                },
                success: function(response) {
                    if (response.success && response.data.length > 0) {
                        var html = '';
                        $.each(response.data, function(index, city) {
                            html += '<div class="city-option" data-city-id="' + city.id + '" data-city-name="' + city.name + '">' + city.name + '</div>';
                        });
                        $('#citySearchResults').html(html).show();
                    } else {
                        $('#citySearchResults').hide();
                    }
                },
                error: function() {
                    console.log('Error searching cities');
                    $('#citySearchResults').hide();
                }
            });
        }

        // City selection
        $(document).on('click', '#citySearchResults .city-option', function() {
            var cityName = $(this).data('city-name');
            var cityId = $(this).data('city-id');

            $('#citySearch').val(cityName);
            $('input[name="city_id"]').val(cityId);
            $('input[name="city_name"]').val(cityName);
            $('#citySearchResults').hide();

            calculateShippingCost();
        });

        function calculateShippingCost() {
            var cityId = $('input[name="city_id"]').val();
            var postalCode = $('input[name="postal_code"]').val();

            if (cityId && postalCode && subtotal > 0) {
                var totalWeight = calculateTotalWeight();

                $.ajax({
                    url: yiari_ajax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'calculate_shipping_cost',
                        city_id: cityId,
                        postal_code: postalCode,
                        weight: totalWeight,
                        nonce: yiari_ajax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            shippingCost = parseInt(response.data.cost) || 0;
                            $('#shippingAmount').text(formatIDR(shippingCost));
                            calculateTotal();
                        }
                    },
                    error: function() {
                        console.log('Error calculating shipping cost');
                    }
                });
            }
        }

        function calculateTotalWeight() {
            var totalWeight = 0;
            $.each(dollPrices, function(dollName, price) {
                var qty = parseInt($('input[name="' + dollName + '_qty"]').val()) || 0;
                totalWeight += qty * 200; // Assume 200g per doll
            });
            $('#totalWeight').text(totalWeight + ' gram');
            return totalWeight;
        }

        // Form submission
        $('#donasiKukangForm').on('submit', function(e) {
            e.preventDefault();

            if (validateForm()) {
                submitDonation();
            }
        });

        function validateForm() {
            var errors = [];

            // Check required fields
            var requiredFields = {
                'customer_name': 'Nama lengkap',
                'email': 'Email',
                'phone': 'Nomor telepon',
                'address': 'Alamat',
                'city_name': 'Kota',
                'postal_code': 'Kode pos'
            };

            $.each(requiredFields, function(field, label) {
                var fieldValue = $('input[name="' + field + '"], textarea[name="' + field + '"]').val();
                if (!fieldValue || !fieldValue.trim()) {
                    errors.push(label + ' harus diisi');
                }
            });

            // Check if any dolls selected
            var hasDolls = false;
            $.each(dollPrices, function(dollName, price) {
                var qty = parseInt($('input[name="' + dollName + '_qty"]').val()) || 0;
                if (qty > 0) {
                    hasDolls = true;
                    return false;
                }
            });

            if (!hasDolls) {
                errors.push('Pilih minimal 1 boneka kukang untuk diadopsi');
            }

            if (errors.length > 0) {
                alert('Kesalahan:\n' + errors.join('\n'));
                return false;
            }

            return true;
        }

        function submitDonation() {
            var submitBtn = $('#donasiKukangForm button[type="submit"]');
            submitBtn.prop('disabled', true).text('Memproses...');

            $.ajax({
                url: yiari_ajax.ajax_url,
                type: 'POST',
                data: $('#donasiKukangForm').serialize() + '&action=process_donation&nonce=' + yiari_ajax.nonce,
                success: function(response) {
                    if (response.success && response.data.snap_token) {
                        // Open Midtrans Snap
                        window.snap.pay(response.data.snap_token, {
                            onSuccess: function(result) {
                                alert('Pembayaran berhasil!');
                                console.log(result);
                            },
                            onPending: function(result) {
                                alert('Pembayaran tertunda. Silakan selesaikan pembayaran Anda.');
                                console.log(result);
                            },
                            onError: function(result) {
                                alert('Pembayaran gagal!');
                                console.log(result);
                            },
                            onClose: function() {
                                console.log('Customer closed the popup without finishing the payment');
                            }
                        });
                    } else {
                        alert('Error: ' + (response.data ? response.data.message : 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Terjadi kesalahan jaringan. Silakan coba lagi.');
                },
                complete: function() {
                    submitBtn.prop('disabled', false).text('💝 Proses Donasi Sekarang');
                }
            });
        }

        // Postal code change handler
        $('input[name="postal_code"]').on('blur', function() {
            if ($(this).val().length >= 5) {
                calculateShippingCost();
            }
        });

        // Initialize calculations
        calculateSubtotal();
        calculateTotalWeight();
    }

    function initializeEnglishForm() {
        console.log('Initializing English donation form...');
        // English form initialization will be handled by the existing JavaScript in the form
    }

    // Hide city search results when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#citySearch, #citySearchResults').length) {
            $('#citySearchResults').hide();
        }
    });

})(jQuery);