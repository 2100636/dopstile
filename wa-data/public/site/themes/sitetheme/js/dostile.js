  $(document).ready(function(){


    if($('.home_slider')[0]){
      $('.home_slider').owlCarousel({
        loop: true,
        // nav: true,
        // autoplay: true,
        items: 1
      });
    }

    $('button.catalog').on('click', function() {
    	$('.catalog_menu').slideToggle();
    	// $('.slider').slideToggle();
    })

  });


