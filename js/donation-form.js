jQuery(document).ready(function($) {
  var donasiButton = $('.donasi'); // Menggunakan kelas .donasi sebagai selector
  var lightboxOverlay = $('#lightboxOverlay');
  var lightboxContent = $('#lightboxContent');
  var closeButton = $('#closeButton');
  var donationForm = $('#donationForm'); // Menambahkan pemilihan formulir donasi

  donasiButton.on('click', function(e) {
      e.preventDefault();
      lightboxOverlay.fadeIn();
      lightboxContent.fadeIn();
  });

  closeButton.on('click', function() {
      lightboxOverlay.fadeOut(function() {
          resetForm(); // Memanggil fungsi resetForm saat lightbox sepenuhnya tersembunyi
      });
      lightboxContent.fadeOut();
  });

  // lightboxOverlay.on('click', function() {
  //     lightboxOverlay.fadeOut(function() {
  //         resetForm(); // Memanggil fungsi resetForm saat lightbox sepenuhnya tersembunyi
  //     });
  //     lightboxContent.fadeOut();
  // });

  lightboxContent.on('click', function(event) {
      event.stopPropagation(); // Mencegah penutupan lightbox saat diklik di dalam lightbox
  });

  function resetForm() {
      // Melakukan reset formulir dengan ID #donationForm
      donationForm.trigger("reset");
  }

    $('#donationForm').on('submit', function(event) {
        event.preventDefault(); // Mencegah formulir untuk melakukan submit biasa

        // Ambil data formulir
        var formData = $(this).serialize();

        // Kirim data ke server menggunakan AJAX
        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url, // URL dari file WordPress AJAX handler
            data: {
                action: 'handle_donation_form_submission', // Nama aksi untuk dikirim ke handler AJAX
                formData: formData // Data formulir
            },
            success: function(response) {
                //console.log('Data yang dikirim dari formulir donasi:');
                console.log(response); // Tampilkan data yang diterima dari server di console.log
                //window.snap.pay(response);
                window.snap.pay(response, {
                    onSuccess: function(result){
                        /* You may add your own implementation here */
                        var snapToken = response; // Sesuaikan dengan nama field yang digunakan
                    
                        // Panggil fungsi AJAX untuk mengupdate status pembayaran
                        $.ajax({
                            type: 'POST',
                            url: ajax_object.ajax_url,
                            data: {
                                action: 'handle_payment_success', // Nama aksi untuk dikirim ke handler AJAX
                                snapToken: snapToken // Data snapToken untuk diupdate
                            },
                            success: function(response) {
                                console.log(response); // Tampilkan pesan dari server di console.log
                            }
                        });
                    
                        alert("payment success!"); console.log(result);
                    },
                    onPending: function(result){
                      /* You may add your own implementation here */
                      alert("wating your payment!"); console.log(result);
                    },
                    onError: function(result){
                      /* You may add your own implementation here */
                      alert("payment failed!"); console.log(result);
                    },
                    onClose: function(){
                      /* You may add your own implementation here */
                      // var snapToken = response; // Sesuaikan dengan nama field yang digunakan
                      // $.ajax({
                      //     type: 'POST',
                      //     url: ajax_object.ajax_url,
                      //     data: {
                      //         action: 'handle_donation_form_close', // Nama aksi untuk dikirim ke handler AJAX
                      //         snapToken: snapToken // Data snapToken untuk dihapus
                      //     },
                      //     success: function(response) {
                      //         console.log(response); // Tampilkan pesan dari server di console.log
                      //     }
                      // });
                      alert('you closed the popup without finishing the payment');
                  }
                  
                  })
            }
        });
    });

    // Ketika radio button diklik
    $('input[name="tipe_donasi"]').click(function() {
        // Periksa nilai radio button yang dipilih
        var selectedValue = $(this).val();

        // URL gambar latar belakang yang sesuai dengan nilai radio button
        var imageUrl = '';

        // Jika nilai radio button adalah "Donasi Kukang"
        if (selectedValue === 'Donasi Kukang') {
            imageUrl = 'https://internationalanimalrescue.or.id/wp-content/uploads/2023/05/IMG_1965.jpg';
        }
        // Jika nilai radio button adalah "Donasi Orangutan"
        else if (selectedValue === 'Donasi Orangutan') {
            imageUrl = 'https://internationalanimalrescue.or.id/wp-content/uploads/2023/05/Update-Donatur_Rocky_28022020_RUD_43.jpg';
        }

        // Ubah gambar latar belakang dengan URL baru
        $('#section-10-301485').css('background-image', 'url(' + imageUrl + ')');
    });
});
